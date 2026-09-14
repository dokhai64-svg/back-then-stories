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
        $model = config('gemini.model', 'gemini-3.6-flash');

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
                'message' => 'The article body is too short for AI generation.',
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

        try {
            /*
             * Use the current Gemini Interactions API instead of the
             * legacy GenerateContent endpoint.
             */
            $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(60)
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

                return response()->json([
                    'message' => $message,
                ], 502);
            }

            $payload = $response->json();
            $outputText = '';

            /*
             * Current Interactions API response:
             * steps[] -> model_output -> content[] -> text
             */
            foreach (($payload['steps'] ?? []) as $step) {
                if (($step['type'] ?? null) !== 'model_output') {
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

            /*
             * Compatibility fallback for older Interactions responses.
             */
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
                logger()->warning('Gemini returned no text', [
                    'interaction_id' => $payload['id'] ?? null,
                    'payload_keys' => array_keys($payload),
                ]);

                return response()->json([
                    'message' => 'Gemini returned no usable text.',
                ], 502);
            }

            $result = json_decode(trim($outputText), true);

            if (!is_array($result)) {
                logger()->warning('Gemini JSON parse failed', [
                    'interaction_id' => $payload['id'] ?? null,
                    'response_preview' => mb_substr(
                        trim($outputText),
                        0,
                        500
                    ),
                ]);

                return response()->json([
                    'message' => 'Gemini returned an invalid response.',
                ], 502);
            }

            $opening = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    (string) ($result['opening_excerpt'] ?? '')
                )
            );

            $seoTitle = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    (string) ($result['seo_title'] ?? '')
                )
            );

            $meta = trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    (string) ($result['meta_description'] ?? '')
                )
            );

            if (
                $opening === '' ||
                $seoTitle === '' ||
                $meta === ''
            ) {
                return response()->json([
                    'message' =>
                        'Gemini response was missing one or more fields.',
                ], 502);
            }

            return response()->json([
                'opening_excerpt' =>
                    Str::limit($opening, 780, ''),

                'seo_title' =>
                    Str::limit($seoTitle, 180, ''),

                'meta_description' =>
                    Str::limit($meta, 160, ''),
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' =>
                    'AI generation failed. Please try again.',
            ], 500);
        }
    }
}
