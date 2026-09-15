<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GeminiArticleController extends Controller
{
    public function generate(Request $request)
    {
        $requestId = 'ai_' . Str::lower(Str::random(8));

        try {
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

            if (!is_string($articleBody)) {
                $articleBody = (string) $data['body'];
            }

            $articleBody = trim($articleBody);

            if (mb_strlen($articleBody) < 80) {
                return response()->json([
                    'message' =>
                        'The article body is too short for AI generation.',
                    'request_id' => $requestId,
                ], 422);
            }

            $articleBody = Str::limit(
                $articleBody,
                30000,
                ''
            );

            $fullPrompt =
                $this->editorialPrompt() .
                "\n\nARTICLE TITLE:\n" .
                trim((string) $data['title']) .
                "\n\nARTICLE BODY:\n" .
                $articleBody;

            /*
             * Production fallback order.
             *
             * The first models are the newest stable Flash models.
             * GEMINI_MODEL from Railway is also included, but it cannot
             * block the other fallbacks if it is unavailable.
             */
            $configuredModel = trim(
                (string) config(
                    'gemini.model',
                    'gemini-3.8-flash'
                )
            );

            $models = array_values(
                array_unique(
                    array_filter([
                        'gemini-3.8-flash',
                        'gemini-3.7-flash',
                        $configuredModel,
                        'gemini-3.6-flash',
                        'gemini-3.5-flash',
                        'gemini-3.5-flash-lite',
                        'gemini-3.1-flash-lite',
                        'gemini-2.5-flash-lite',
                    ])
                )
            );

            $failures = [];

            foreach ($models as $model) {
                /*
                 * Try the current Interactions API first.
                 * One quick retry is used only for transient failures.
                 */
                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    $result = $this->callInteractions(
                        $apiKey,
                        $model,
                        $fullPrompt
                    );

                    if ($result['ok']) {
                        return response()->json(
                            $this->successPayload(
                                $result['data'],
                                $model,
                                'interactions',
                                $requestId
                            )
                        );
                    }

                    $failures[] = [
                        'model' => $model,
                        'api' => 'interactions',
                        'status' => $result['status'],
                        'message' => $result['message'],
                    ];

                    $this->logFailure(
                        $requestId,
                        $model,
                        'interactions',
                        $attempt,
                        $result
                    );

                    if (!$result['transient']) {
                        break;
                    }

                    if ($attempt < 2) {
                        usleep(450000);
                    }
                }

                /*
                 * Secondary transport fallback.
                 * If Interactions has a temporary/API-specific problem,
                 * try GenerateContent for the same model before moving on.
                 */
                $legacy = $this->callGenerateContent(
                    $apiKey,
                    $model,
                    $fullPrompt
                );

                if ($legacy['ok']) {
                    return response()->json(
                        $this->successPayload(
                            $legacy['data'],
                            $model,
                            'generateContent',
                            $requestId
                        )
                    );
                }

                $failures[] = [
                    'model' => $model,
                    'api' => 'generateContent',
                    'status' => $legacy['status'],
                    'message' => $legacy['message'],
                ];

                $this->logFailure(
                    $requestId,
                    $model,
                    'generateContent',
                    1,
                    $legacy
                );

                /*
                 * Authentication/permission errors will fail for every
                 * model, so stop immediately and show a useful message.
                 */
                if (in_array(
                    $legacy['status'],
                    [401, 403],
                    true
                )) {
                    return response()->json([
                        'message' =>
                            'Gemini rejected the API key or project permission. '
                            . 'Check GEMINI_API_KEY in Railway.',
                        'request_id' => $requestId,
                    ], 502);
                }
            }

            $last = end($failures);

            $lastMessage =
                is_array($last)
                    ? trim((string) ($last['message'] ?? ''))
                    : '';

            if ($lastMessage === '') {
                $lastMessage =
                    'Gemini is temporarily unavailable.';
            }

            return response()->json([
                'message' =>
                    'Gemini could not complete the request after automatic fallback. '
                    . 'Last error: ' . Str::limit(
                        $lastMessage,
                        220,
                        ''
                    ),
                'request_id' => $requestId,
            ], 503);

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {
            report($e);

            logger()->error(
                'Gemini AI unexpected server error',
                [
                    'request_id' => $requestId,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'AI server error. Reference: ' . $requestId
                    . '. Check Railway logs for this reference.',
                'request_id' => $requestId,
            ], 500);
        }
    }

    private function callInteractions(
        string $apiKey,
        string $model,
        string $prompt
    ): array {
        try {
            $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(55)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/interactions',
                    [
                        'model' => $model,
                        'input' => $prompt,
                        'response_format' => [
                            'type' => 'text',
                            'mime_type' =>
                                'application/json',
                            'schema' =>
                                $this->responseSchema(),
                        ],
                    ]
                );

            if (!$response->successful()) {
                return $this->failedResult(
                    $response->status(),
                    $this->googleErrorMessage(
                        $response->json()
                    )
                );
            }

            $payload = $response->json();

            $outputText = '';

            foreach (($payload['steps'] ?? []) as $step) {
                if (
                    ($step['type'] ?? null) !==
                    'model_output'
                ) {
                    continue;
                }

                foreach (($step['content'] ?? []) as $content) {
                    if (
                        ($content['type'] ?? null) ===
                        'text' &&
                        isset($content['text']) &&
                        is_string($content['text'])
                    ) {
                        $outputText .= $content['text'];
                    }
                }
            }

            if (
                trim($outputText) === '' &&
                isset($payload['output_text']) &&
                is_string($payload['output_text'])
            ) {
                $outputText =
                    $payload['output_text'];
            }

            return $this->parseOutput(
                $outputText,
                200
            );

        } catch (Throwable $e) {
            return $this->failedResult(
                500,
                $e->getMessage()
            );
        }
    }

    private function callGenerateContent(
        string $apiKey,
        string $model,
        string $prompt
    ): array {
        try {
            $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(55)
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/'
                    . rawurlencode($model)
                    . ':generateContent',
                    [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    [
                                        'text' => $prompt,
                                    ],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseFormat' => [
                                'text' => [
                                    'mimeType' =>
                                        'application/json',
                                    'schema' =>
                                        $this->responseSchema(),
                                ],
                            ],
                        ],
                    ]
                );

            if (!$response->successful()) {
                return $this->failedResult(
                    $response->status(),
                    $this->googleErrorMessage(
                        $response->json()
                    )
                );
            }

            $payload = $response->json();
            $outputText = '';

            foreach (
                (
                    $payload['candidates'][0]
                    ['content']['parts']
                    ?? []
                ) as $part
            ) {
                if (
                    isset($part['text']) &&
                    is_string($part['text'])
                ) {
                    $outputText .= $part['text'];
                }
            }

            return $this->parseOutput(
                $outputText,
                200
            );

        } catch (Throwable $e) {
            return $this->failedResult(
                500,
                $e->getMessage()
            );
        }
    }

    private function parseOutput(
        string $outputText,
        int $successStatus = 200
    ): array {
        $outputText = trim($outputText);

        if ($outputText === '') {
            return $this->failedResult(
                502,
                'Gemini returned no usable text.'
            );
        }

        /*
         * Be tolerant if a model wraps JSON in a Markdown fence.
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
            return $this->failedResult(
                502,
                'Gemini returned invalid JSON.'
            );
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
            $opening === '' ||
            $seoTitle === '' ||
            $meta === ''
        ) {
            return $this->failedResult(
                502,
                'Gemini response was missing one or more fields.'
            );
        }

        return [
            'ok' => true,
            'status' => $successStatus,
            'message' => '',
            'transient' => false,
            'data' => [
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
            ],
        ];
    }

    private function failedResult(
        int $status,
        string $message
    ): array {
        $message = trim($message);

        if ($message === '') {
            $message =
                'Gemini request failed.';
        }

        $lower = mb_strtolower($message);

        $transient =
            in_array(
                $status,
                [408, 409, 429, 500, 502, 503, 504],
                true
            )
            || str_contains(
                $lower,
                'high demand'
            )
            || str_contains(
                $lower,
                'temporarily'
            )
            || str_contains(
                $lower,
                'overloaded'
            )
            || str_contains(
                $lower,
                'resource exhausted'
            )
            || str_contains(
                $lower,
                'resource_exhausted'
            )
            || str_contains(
                $lower,
                'unavailable'
            )
            || str_contains(
                $lower,
                'timeout'
            )
            || str_contains(
                $lower,
                'timed out'
            );

        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'transient' => $transient,
            'data' => [],
        ];
    }

    private function googleErrorMessage(
        mixed $payload
    ): string {
        if (!is_array($payload)) {
            return 'Gemini request failed.';
        }

        return trim(
            (string) (
                $payload['error']['message']
                ?? $payload['message']
                ?? 'Gemini request failed.'
            )
        );
    }

    private function successPayload(
        array $data,
        string $model,
        string $api,
        string $requestId
    ): array {
        return [
            'opening_excerpt' =>
                $data['opening_excerpt'],
            'seo_title' =>
                $data['seo_title'],
            'meta_description' =>
                $data['meta_description'],
            'ai_model' => $model,
            'ai_api' => $api,
            'request_id' => $requestId,
        ];
    }

    private function logFailure(
        string $requestId,
        string $model,
        string $api,
        int $attempt,
        array $result
    ): void {
        logger()->warning(
            'Gemini AI attempt failed',
            [
                'request_id' => $requestId,
                'model' => $model,
                'api' => $api,
                'attempt' => $attempt,
                'status' =>
                    $result['status'] ?? null,
                'transient' =>
                    $result['transient'] ?? false,
                'message' =>
                    Str::limit(
                        (string) (
                            $result['message']
                            ?? ''
                        ),
                        500,
                        ''
                    ),
            ]
        );
    }

    private function cleanText(
        mixed $value
    ): string {
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

    private function responseSchema(): array
    {
        return [
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
        ];
    }

    private function editorialPrompt(): string
    {
        return <<<'PROMPT'
You are the senior editorial SEO assistant for an American-English website about classic music, singers, entertainment history, and cultural memories.

SOURCE DISCIPLINE
Use ONLY facts explicitly supported by the supplied article title and article body.
Never invent or infer unsupported dates, chart positions, awards, quotes, causes, motives, relationships, recording details, or historical claims.
If a detail is uncertain or unsupported, omit it or use safer wording.

VOICE
Write natural contemporary American English.
Sound editorial, human, specific, and confident.
Avoid generic AI phrasing such as "discover," "delve into," "this article explores," "journey," "iconic legacy," or "timeless masterpiece" unless the source truly supports that wording.
Avoid clickbait, keyword stuffing, hashtags, emojis, and markdown.
Do not repeat the same opening phrase across all three fields.

CREATE EXACTLY THREE FIELDS

1. opening_excerpt
- 35 to 55 words.
- Usually 1–2 sentences.
- Lead with the strongest supported detail, tension, turning point, or historical context.
- Complement the title instead of simply repeating it.
- Give the reader a clear reason to continue.
- Do not sound like a Facebook caption.
- Do not end with "read more," "find out," or a generic question.

2. seo_title
- Descriptive, concise, specific, and unique to this page.
- Put the most important artist, song, or subject early when natural.
- Keep it roughly 40–70 characters when that can be done naturally; clarity is more important than hitting an exact count.
- Do not add the site name.
- Do not repeat keywords.
- Do not use vague boilerplate such as "The Story Behind..." unless the article is genuinely centered on that story.

3. meta_description
- A page-specific, human-readable summary.
- Aim for roughly 140–160 characters.
- State what makes this article worth reading using supported facts.
- Do not merely copy the title.
- No keyword lists, hype, hashtags, or calls to action such as "click here."

FINAL QUALITY CHECK
Before returning the result:
- Verify every factual statement is supported by the source text.
- Make the three fields distinct from one another.
- Prefer precise nouns and verbs over hype.
- Preserve correct names, song titles, and years exactly as supported by the source.
PROMPT;
    }
}
