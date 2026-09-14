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
You are an editorial assistant for an American-English website about classic music, singers, entertainment history, and cultural memories.

Use ONLY facts explicitly supported by the supplied article title and article body. Never invent dates, quotes, chart positions, awards, motives, relationships, causes, or historical details.

Create exactly three fields:

1. opening_excerpt
- Natural contemporary American English.
- 35 to 55 words.
- An engaging article deck/opening summary, not a sensational Facebook caption.
- Give the reader a clear reason to continue without revealing unnecessary details.
- No hashtags, emojis, or markdown.

2. seo_title
- Maximum 60 characters when practical.
- Clear, accurate, natural, and search-friendly.
- Preserve important artist/song names when supported.
- No clickbait or unsupported claims.

3. meta_description
- Aim for 140 to 160 characters.
- Never exceed 160 characters.
- Accurate, readable, and enticing.
- No hashtags, emojis, or unsupported claims.

If the source text does not support a specific fact, use safer general wording instead of guessing.
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
                (string) ($result['opening_excerpt'] ?? '')
            );

            $seoTitle = trim(
                (string) ($result['seo_title'] ?? '')
            );

            $meta = trim(
                (string) ($result['meta_description'] ?? '')
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
                    Str::limit($seoTitle, 255, ''),

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
