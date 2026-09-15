<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GeminiArticleController extends Controller
{
    public function generate(Request $request)
    {
        $requestId = 'ai_' . Str::lower(Str::random(8));

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:60000'],
        ]);

        $apiKey = trim((string) config('gemini.api_key'));

        if ($apiKey === '') {
            return response()->json([
                'message' =>
                    'GEMINI_API_KEY is not configured in Railway.',
                'request_id' => $requestId,
            ], 500);
        }

        $articleBody = preg_replace(
            '/\s+/u',
            ' ',
            (string) $data['body']
        );

        $articleBody = trim(
            is_string($articleBody)
                ? $articleBody
                : (string) $data['body']
        );

        if (mb_strlen($articleBody) < 80) {
            return response()->json([
                'message' =>
                    'The article body is too short for AI generation.',
                'request_id' => $requestId,
            ], 422);
        }

        /*
         * SEO/opening generation does not need the full article body.
         * Keeping the prompt smaller reduces latency and timeout risk.
         */
        $articleBody = Str::limit(
            $articleBody,
            18000,
            ''
        );

        $prompt = $this->editorialPrompt()
            . "\n\nARTICLE TITLE:\n"
            . trim((string) $data['title'])
            . "\n\nARTICLE BODY:\n"
            . $articleBody;

        /*
         * Fast models first.
         * IMPORTANT: every model has its OWN try/catch.
         * If one request times out, fallback continues to the next model.
         */
        $models = [
            'gemini-flash-lite-latest',
            'gemini-3.5-flash-lite',
            'gemini-3.6-flash',
        ];

        $startedAt = microtime(true);
        $hardBudgetSeconds = 25.0;
        $lastMessage =
            'Gemini is temporarily unavailable. Please try again.';

        foreach ($models as $model) {
            if (
                (microtime(true) - $startedAt)
                >= $hardBudgetSeconds
            ) {
                $lastMessage =
                    'AI request reached the server time budget.';
                break;
            }

            try {
                $response = Http::withHeaders([
                        'x-goog-api-key' => $apiKey,
                    ])
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(2)
                    ->timeout(7)
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/interactions',
                        [
                            'model' => $model,
                            'input' => $prompt,

                            /*
                             * Low thinking is enough for three short
                             * editorial fields and reduces latency.
                             */
                            'generation_config' => [
                                'thinking_level' => 'low',
                                'max_output_tokens' => 600,
                            ],

                            'response_format' => [
                                'type' => 'text',
                                'mime_type' =>
                                    'application/json',
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'opening_excerpt' => [
                                            'type' => 'string',
                                        ],
                                        'seo_title' => [
                                            'type' => 'string',
                                        ],
                                        'meta_description' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'opening_excerpt',
                                        'seo_title',
                                        'meta_description',
                                    ],
                                ],
                            ],
                        ]
                    );

            } catch (ConnectionException $e) {
                /*
                 * THIS is the bug in the previous version:
                 * a timeout escaped the model loop and killed all fallback.
                 * Now it is logged and the next model is tried.
                 */
                $lastMessage =
                    'Connection timeout on ' . $model . '.';

                logger()->warning(
                    'Gemini connection timeout; trying fallback',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'message' => $e->getMessage(),
                    ]
                );

                continue;

            } catch (Throwable $e) {
                $lastMessage =
                    'Gemini request error on ' . $model . '.';

                logger()->warning(
                    'Gemini request exception; trying fallback',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]
                );

                continue;
            }

            if (!$response->successful()) {
                $message =
                    trim(
                        (string) (
                            $response->json('error.message')
                            ?: 'Gemini request failed.'
                        )
                    );

                $lastMessage = $message;

                logger()->warning(
                    'Gemini model request failed',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'status' => $response->status(),
                        'message' => $message,
                    ]
                );

                /*
                 * Bad key / permission cannot be fixed by another model.
                 */
                if (
                    in_array(
                        $response->status(),
                        [401, 403],
                        true
                    )
                ) {
                    return response()->json([
                        'message' =>
                            'Gemini rejected the API key or project permission. '
                            . 'Check GEMINI_API_KEY in Railway.',
                        'request_id' => $requestId,
                    ], 502);
                }

                /*
                 * Bad request means our request format is rejected.
                 * Return Google's real message so it is diagnosable.
                 */
                if ($response->status() === 400) {
                    return response()->json([
                        'message' =>
                            'Gemini request format error: '
                            . Str::limit($message, 240, ''),
                        'request_id' => $requestId,
                    ], 502);
                }

                /*
                 * 404 can be model-specific, so try the next model.
                 * Capacity/rate/server errors also fall through to fallback.
                 */
                continue;
            }

            $payload = $response->json();

            /*
             * Current Interactions API exposes output_text directly.
             * Keep older shapes as fallbacks for compatibility.
             */
            $outputText = '';

            if (
                isset($payload['output_text'])
                && is_string($payload['output_text'])
            ) {
                $outputText =
                    $payload['output_text'];
            }

            if (trim($outputText) === '') {
                foreach (
                    ($payload['outputs'] ?? [])
                    as $output
                ) {
                    if (
                        isset($output['text'])
                        && is_string($output['text'])
                    ) {
                        $outputText .= $output['text'];
                    }

                    foreach (
                        ($output['content'] ?? [])
                        as $content
                    ) {
                        if (
                            isset($content['text'])
                            && is_string($content['text'])
                        ) {
                            $outputText .=
                                $content['text'];
                        }
                    }
                }
            }

            if (trim($outputText) === '') {
                foreach (
                    ($payload['steps'] ?? [])
                    as $step
                ) {
                    if (
                        ($step['type'] ?? null)
                        !== 'model_output'
                    ) {
                        continue;
                    }

                    foreach (
                        ($step['content'] ?? [])
                        as $content
                    ) {
                        if (
                            isset($content['text'])
                            && is_string($content['text'])
                        ) {
                            $outputText .=
                                $content['text'];
                        }
                    }
                }
            }

            $outputText = trim($outputText);

            if ($outputText === '') {
                $lastMessage =
                    'Gemini returned no usable output.';

                logger()->warning(
                    'Gemini returned no output text',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'interaction_id' =>
                            $payload['id'] ?? null,
                        'status' =>
                            $payload['status'] ?? null,
                    ]
                );

                continue;
            }

            /*
             * Tolerate a Markdown code fence even though structured
             * output normally returns clean JSON.
             */
            $outputText = preg_replace(
                '/^```(?:json)?\s*|\s*```$/i',
                '',
                $outputText
            );

            $result = json_decode(
                trim((string) $outputText),
                true
            );

            if (!is_array($result)) {
                $lastMessage =
                    'Gemini returned invalid JSON.';

                logger()->warning(
                    'Gemini JSON parse failed',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'response_preview' =>
                            mb_substr(
                                $outputText,
                                0,
                                500
                            ),
                    ]
                );

                continue;
            }

            $opening = $this->cleanText(
                $result['opening_excerpt'] ?? ''
            );

            $seoTitle = $this->cleanText(
                $result['seo_title'] ?? ''
            );

            $meta = $this->cleanText(
                $result['meta_description'] ?? ''
            );

            if (
                $opening === ''
                || $seoTitle === ''
                || $meta === ''
            ) {
                $lastMessage =
                    'Gemini response was missing one or more fields.';

                logger()->warning(
                    'Gemini response missing fields',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                    ]
                );

                continue;
            }

            return response()->json([
                'opening_excerpt' =>
                    Str::limit(
                        $opening,
                        780,
                        ''
                    ),
                'seo_title' =>
                    Str::limit(
                        $seoTitle,
                        180,
                        ''
                    ),
                'meta_description' =>
                    Str::limit(
                        $meta,
                        160,
                        ''
                    ),
                'ai_model' => $model,
                'request_id' => $requestId,
            ]);
        }

        return response()->json([
            'message' =>
                'AI fallback could not complete the request. '
                . Str::limit(
                    $lastMessage,
                    240,
                    ''
                ),
            'request_id' => $requestId,
        ], 503);
    }

    private function cleanText(mixed $value): string
    {
        $value = preg_replace(
            '/\s+/u',
            ' ',
            (string) $value
        );

        return trim(
            is_string($value)
                ? $value
                : ''
        );
    }

    private function editorialPrompt(): string
    {
        return <<<'PROMPT'
You are the senior editorial SEO assistant for an American-English website about classic music, singers, entertainment history, and cultural memories.

SOURCE DISCIPLINE
Use ONLY facts explicitly supported by the supplied article title and article body.
Never invent unsupported dates, chart positions, awards, quotes, causes, motives, relationships, recording details, or historical claims.
If a detail is uncertain or unsupported, omit it.

VOICE
Write natural contemporary American English.
Sound editorial, human, specific, and confident.
Avoid generic AI phrasing, keyword stuffing, hashtags, emojis, and markdown.

CREATE EXACTLY THREE FIELDS

1. opening_excerpt
- 35 to 55 words.
- Usually 1–2 sentences.
- Lead with the strongest supported detail, tension, turning point, or historical context.
- Complement the title instead of simply repeating it.
- Give the reader a clear reason to continue.

2. seo_title
- Descriptive, concise, specific, and unique.
- Put the main artist, song, or subject early when natural.
- Roughly 40–70 characters when natural.
- Do not add the site name.
- Do not repeat keywords.

3. meta_description
- A page-specific human-readable summary.
- Aim for roughly 140–160 characters.
- State what makes the article worth reading using supported facts.
- Do not simply copy the title.

FINAL CHECK
Verify every factual statement is supported by the supplied text.
Return only the required structured fields.
PROMPT;
    }
}
