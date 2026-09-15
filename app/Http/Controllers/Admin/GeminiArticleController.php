<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GeminiArticleController extends Controller
{
    public function generate(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:60000'],
        ]);

        $apiKey = config('gemini.api_key');

        if (!$apiKey) {
            return response()->json([
                'message' => 'GEMINI_API_KEY is not configured in Railway.',
            ], 500);
        }

        $articleBody = trim(
            preg_replace('/\s+/u', ' ', $data['body'])
        );

        if (mb_strlen($articleBody) < 80) {
            return response()->json([
                'message' =>
                    'The article body is too short for AI generation.',
            ], 422);
        }

        $articleBody = Str::limit($articleBody, 30000, '');

        $prompt = <<<'PROMPT'
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

        $fullPrompt =
            $prompt .
            "\n\nARTICLE TITLE:\n" . $data['title'] .
            "\n\nARTICLE BODY:\n" . $articleBody;

        /*
         * FAST FALLBACK FOR RAILWAY / PHP 30s LIMIT
         *
         * The server currently has max_execution_time=30 seconds.
         * Each Gemini call is therefore capped at 7 seconds and we use
         * only three models. In the worst case the AI request stays
         * below the PHP limit instead of dying inside Guzzle/cURL.
         *
         * 3.5 Flash is used first for quality.
         * Flash-Lite models are the low-latency fallbacks.
         */
        $models = [
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
            'gemini-3.1-flash-lite',
        ];

        $startedAt = microtime(true);
        $hardBudgetSeconds = 24.0;

        $lastMessage =
            'Gemini is temporarily unavailable. Please try again.';

        try {
            foreach ($models as $model) {
                if ((microtime(true) - $startedAt) > $hardBudgetSeconds) {
                    break;
                }

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
                            'input' => $fullPrompt,

                            'response_format' => [
                                'type' => 'text',
                                'mime_type' => 'application/json',
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

                if (!$response->successful()) {
                    $message =
                        $response->json('error.message') ?:
                        'Gemini request failed.';

                    $lastMessage = $message;

                    logger()->warning(
                        'Gemini model request failed',
                        [
                            'model' => $model,
                            'status' => $response->status(),
                            'message' => $message,
                        ]
                    );

                    /*
                     * Only fail over automatically for capacity/rate/
                     * transient service problems.
                     *
                     * Authentication and malformed request errors should
                     * be shown immediately because another model will not
                     * fix them.
                     */
                    $lowerMessage = mb_strtolower($message);

                    $isTransient =
                        in_array(
                            $response->status(),
                            [429, 500, 502, 503, 504],
                            true
                        ) ||
                        str_contains(
                            $lowerMessage,
                            'high demand'
                        ) ||
                        str_contains(
                            $lowerMessage,
                            'temporarily'
                        ) ||
                        str_contains(
                            $lowerMessage,
                            'overloaded'
                        ) ||
                        str_contains(
                            $lowerMessage,
                            'resource exhausted'
                        ) ||
                        str_contains(
                            $lowerMessage,
                            'resource_exhausted'
                        ) ||
                        str_contains(
                            $lowerMessage,
                            'unavailable'
                        );

                    if ($isTransient) {
                        continue;
                    }

                    return response()->json([
                        'message' => $message,
                    ], 502);
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
                            ($content['type'] ?? null) === 'text' &&
                            isset($content['text']) &&
                            is_string($content['text'])
                        ) {
                            $outputText .= $content['text'];
                        }
                    }
                }

                if (trim($outputText) === '') {
                    foreach (($payload['outputs'] ?? []) as $output) {
                        if (
                            ($output['type'] ?? null) === 'text' &&
                            isset($output['text']) &&
                            is_string($output['text'])
                        ) {
                            $outputText .= $output['text'];
                        }
                    }
                }

                if (trim($outputText) === '') {
                    $lastMessage =
                        'Gemini returned no usable text.';

                    logger()->warning(
                        'Gemini returned no text',
                        [
                            'model' => $model,
                            'interaction_id' =>
                                $payload['id'] ?? null,
                        ]
                    );

                    continue;
                }

                $result = json_decode(
                    trim($outputText),
                    true
                );

                if (!is_array($result)) {
                    $lastMessage =
                        'Gemini returned an invalid response.';

                    logger()->warning(
                        'Gemini JSON parse failed',
                        [
                            'model' => $model,
                            'interaction_id' =>
                                $payload['id'] ?? null,
                            'response_preview' =>
                                mb_substr(
                                    trim($outputText),
                                    0,
                                    500
                                ),
                        ]
                    );

                    continue;
                }

                $opening = trim(
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        (string) (
                            $result['opening_excerpt'] ?? ''
                        )
                    )
                );

                $seoTitle = trim(
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        (string) (
                            $result['seo_title'] ?? ''
                        )
                    )
                );

                $meta = trim(
                    preg_replace(
                        '/\s+/u',
                        ' ',
                        (string) (
                            $result['meta_description'] ?? ''
                        )
                    )
                );

                if (
                    $opening === '' ||
                    $seoTitle === '' ||
                    $meta === ''
                ) {
                    $lastMessage =
                        'Gemini response was missing one or more fields.';

                    logger()->warning(
                        'Gemini response missing fields',
                        ['model' => $model]
                    );

                    continue;
                }

                return response()->json([
                    'opening_excerpt' =>
                        Str::limit($opening, 780, ''),

                    'seo_title' =>
                        Str::limit($seoTitle, 180, ''),

                    'meta_description' =>
                        Str::limit($meta, 160, ''),

                    'ai_model' => $model,
                ]);
            }

            return response()->json([
                'message' => $lastMessage,
            ], 503);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' =>
                    'AI generation failed. Please try again.',
            ], 500);
        }
    }
}
