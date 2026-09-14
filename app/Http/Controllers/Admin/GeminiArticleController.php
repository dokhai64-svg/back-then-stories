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
        $model = config('gemini.model', 'gemini-2.5-flash');

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

IMPORTANT OUTPUT RULE:
Return ONLY one valid JSON object and nothing else.
Do not use markdown fences.
Use exactly these keys:
{
  "opening_excerpt": "...",
  "seo_title": "...",
  "meta_description": "..."
}
PROMPT;

        $fullPrompt =
            $prompt .
            "\n\nARTICLE TITLE:\n" . $data['title'] .
            "\n\nARTICLE BODY:\n" . $articleBody;

        try {
            $url =
                'https://generativelanguage.googleapis.com/v1beta/models/' .
                rawurlencode($model) .
                ':generateContent';

            $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $fullPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 700,
                    ],
                ]);

            if (!$response->successful()) {
                $message =
                    $response->json('error.message') ?:
                    'Gemini request failed.';

                return response()->json([
                    'message' => $message,
                ], 502);
            }

            $outputText =
                $response->json(
                    'candidates.0.content.parts.0.text'
                );

            if (!$outputText) {
                return response()->json([
                    'message' => 'Gemini returned no usable text.',
                ], 502);
            }

            $cleanOutput = trim($outputText);

            // Remove markdown fences if Gemini adds them anyway.
            $cleanOutput = preg_replace(
                '/^```(?:json)?\s*/i',
                '',
                $cleanOutput
            );

            $cleanOutput = preg_replace(
                '/\s*```$/',
                '',
                $cleanOutput
            );

            $cleanOutput = trim($cleanOutput);

            $result = json_decode($cleanOutput, true);

            // Fallback: extract the first JSON object from extra text.
            if (!is_array($result)) {
                $firstBrace = strpos($cleanOutput, '{');
                $lastBrace = strrpos($cleanOutput, '}');

                if (
                    $firstBrace !== false &&
                    $lastBrace !== false &&
                    $lastBrace > $firstBrace
                ) {
                    $jsonOnly = substr(
                        $cleanOutput,
                        $firstBrace,
                        $lastBrace - $firstBrace + 1
                    );

                    $result = json_decode($jsonOnly, true);
                }
            }

            if (!is_array($result)) {
                return response()->json([
                    'message' => 'Gemini returned an invalid response.',
                ], 502);
            }

            return response()->json([
                'opening_excerpt' => Str::limit(
                    trim((string) ($result['opening_excerpt'] ?? '')),
                    780,
                    ''
                ),
                'seo_title' => Str::limit(
                    trim((string) ($result['seo_title'] ?? '')),
                    255,
                    ''
                ),
                'meta_description' => Str::limit(
                    trim((string) ($result['meta_description'] ?? '')),
                    160,
                    ''
                ),
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
