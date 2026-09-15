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
                'message' => 'GEMINI_API_KEY is not configured in Railway.',
                'request_id' => $requestId,
            ], 500);
        }

        $sourceTitle = trim((string) $data['title']);

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
                'message' => 'The article body is too short for AI generation.',
                'request_id' => $requestId,
            ], 422);
        }

        /*
         * SEO generation does not need the entire article.
         * A smaller prompt is faster and safer under Railway's 30s limit.
         */
        $articleBodyForPrompt = Str::limit(
            $articleBody,
            18000,
            ''
        );

        $prompt = $this->editorialPrompt()
            . "\n\nSOURCE / WORKING TITLE:\n"
            . $sourceTitle
            . "\n\nSOURCE ARTICLE BODY:\n"
            . $articleBodyForPrompt;

        /*
         * Fast fallback chain.
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
                            'generation_config' => [
                                'thinking_level' => 'low',
                                'max_output_tokens' => 650,
                            ],
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

            } catch (ConnectionException $e) {
                $lastMessage =
                    'Connection timeout on ' . $model . '.';

                logger()->warning(
                    'Gemini timeout; trying fallback',
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
                    'Gemini exception; trying fallback',
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
                $message = trim(
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

                if (
                    in_array(
                        $response->status(),
                        [401, 403],
                        true
                    )
                ) {
                    return response()->json([
                        'message' =>
                            'Gemini rejected the API key or project permission. Check GEMINI_API_KEY in Railway.',
                        'request_id' => $requestId,
                    ], 502);
                }

                if ($response->status() === 400) {
                    return response()->json([
                        'message' =>
                            'Gemini request format error: '
                            . Str::limit($message, 240, ''),
                        'request_id' => $requestId,
                    ], 502);
                }

                continue;
            }

            $payload = $response->json();
            $outputText = $this->extractOutputText($payload);

            if ($outputText === '') {
                $lastMessage =
                    'Gemini returned no usable output.';
                continue;
            }

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
                continue;
            }

            /*
             * SERVER-SIDE ORIGINALITY GUARD
             *
             * The source/working title may have been copied from another site.
             * Reject output that is too close to that title or that reproduces
             * long exact word sequences from the source body.
             */
            $originality = $this->passesOriginalityGuard(
                $sourceTitle,
                $articleBodyForPrompt,
                $opening,
                $seoTitle,
                $meta
            );

            if (!$originality['ok']) {
                $lastMessage =
                    'Generated SEO was too similar to the source wording; trying another model.';

                logger()->warning(
                    'Gemini originality guard rejected output',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'reason' => $originality['reason'],
                    ]
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
                'request_id' => $requestId,
                'originality_check' => 'passed',
            ]);
        }

        return response()->json([
            'message' =>
                'AI fallback could not complete the request. '
                . Str::limit($lastMessage, 240, ''),
            'request_id' => $requestId,
        ], 503);
    }

    private function extractOutputText(array $payload): string
    {
        $outputText = '';

        if (
            isset($payload['output_text'])
            && is_string($payload['output_text'])
        ) {
            $outputText = $payload['output_text'];
        }

        if (trim($outputText) === '') {
            foreach (($payload['outputs'] ?? []) as $output) {
                if (
                    isset($output['text'])
                    && is_string($output['text'])
                ) {
                    $outputText .= $output['text'];
                }

                foreach (($output['content'] ?? []) as $content) {
                    if (
                        isset($content['text'])
                        && is_string($content['text'])
                    ) {
                        $outputText .= $content['text'];
                    }
                }
            }
        }

        if (trim($outputText) === '') {
            foreach (($payload['steps'] ?? []) as $step) {
                if (
                    ($step['type'] ?? null)
                    !== 'model_output'
                ) {
                    continue;
                }

                foreach (($step['content'] ?? []) as $content) {
                    if (
                        isset($content['text'])
                        && is_string($content['text'])
                    ) {
                        $outputText .= $content['text'];
                    }
                }
            }
        }

        return trim($outputText);
    }

    private function passesOriginalityGuard(
        string $sourceTitle,
        string $sourceBody,
        string $opening,
        string $seoTitle,
        string $meta
    ): array {
        $sourceTitleNorm =
            $this->normalizeForCompare($sourceTitle);

        $seoTitleNorm =
            $this->normalizeForCompare($seoTitle);

        if (
            $sourceTitleNorm !== ''
            && $seoTitleNorm !== ''
        ) {
            similar_text(
                $sourceTitleNorm,
                $seoTitleNorm,
                $titleSimilarity
            );

            if ($titleSimilarity >= 78.0) {
                return [
                    'ok' => false,
                    'reason' =>
                        'SEO title similarity '
                        . round($titleSimilarity, 1)
                        . '%',
                ];
            }
        }

        /*
         * Do not allow long exact sequences copied from the source body.
         * 8 words is conservative enough to allow names/facts while blocking
         * sentence-level copying.
         */
        if (
            $this->hasExactWordSequence(
                $opening,
                $sourceBody,
                8
            )
        ) {
            return [
                'ok' => false,
                'reason' =>
                    'Opening contains an 8-word exact source sequence.',
            ];
        }

        if (
            $this->hasExactWordSequence(
                $meta,
                $sourceBody,
                8
            )
        ) {
            return [
                'ok' => false,
                'reason' =>
                    'Meta contains an 8-word exact source sequence.',
            ];
        }

        /*
         * Avoid formulaic SEO phrases that often cause many pages to look
         * nearly identical even when the facts differ.
         */
        $genericStarts = [
            'discover how ',
            'discover why ',
            'learn how ',
            'learn why ',
            'the story behind ',
            'read about ',
            'find out how ',
            'find out why ',
            'everything you need to know ',
        ];

        $metaLower = mb_strtolower($meta);
        $openingLower = mb_strtolower($opening);

        foreach ($genericStarts as $phrase) {
            if (
                str_starts_with($metaLower, $phrase)
                || str_starts_with($openingLower, $phrase)
            ) {
                return [
                    'ok' => false,
                    'reason' =>
                        'Generic SEO opening phrase detected.',
                ];
            }
        }

        return [
            'ok' => true,
            'reason' => 'passed',
        ];
    }

    private function hasExactWordSequence(
        string $candidate,
        string $source,
        int $words = 8
    ): bool {
        $candidateWords = $this->words($candidate);
        $sourceNorm =
            ' ' . implode(' ', $this->words($source)) . ' ';

        if (count($candidateWords) < $words) {
            return false;
        }

        for (
            $i = 0;
            $i <= count($candidateWords) - $words;
            $i++
        ) {
            $phrase = implode(
                ' ',
                array_slice(
                    $candidateWords,
                    $i,
                    $words
                )
            );

            if (
                str_contains(
                    $sourceNorm,
                    ' ' . $phrase . ' '
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function words(string $text): array
    {
        $text = mb_strtolower(
            strip_tags($text)
        );

        $text = preg_replace(
            '/[^\p{L}\p{N}\']+/u',
            ' ',
            $text
        );

        $text = preg_replace(
            '/\s+/u',
            ' ',
            (string) $text
        );

        $text = trim((string) $text);

        return $text === ''
            ? []
            : explode(' ', $text);
    }

    private function normalizeForCompare(string $text): string
    {
        return implode(
            ' ',
            $this->words($text)
        );
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

IMPORTANT ORIGINALITY RULE
The supplied title and article body may come from another website.
Treat them ONLY as factual reference material.
Do NOT imitate or preserve the source website's headline structure, opening sentence, sentence order, promotional language, or distinctive phrasing.
Write all three requested fields from scratch in genuinely independent wording.

You may preserve proper names, song titles, dates, chart positions, place names, and other facts that must remain accurate.
Do not invent new facts.

SOURCE DISCIPLINE
Use ONLY facts explicitly supported by the supplied title and article body.
Never invent unsupported dates, chart positions, awards, quotes, causes, motives, relationships, recording details, or historical claims.
If a detail is uncertain or unsupported, omit it.

ORIGINALITY REQUIREMENTS
- Never copy a complete sentence from the source.
- Avoid reproducing long phrases from the source body.
- Do not simply shorten or lightly rearrange the source title.
- Use a different sentence structure and information order.
- The SEO title must feel independently written, not like a synonym swap.
- The meta description must summarize the article from a fresh angle.
- The opening excerpt must not mirror the source article's first paragraph.
- Avoid formulaic openings such as "Discover how," "Discover why," "Learn how," "The story behind," and "Find out why."

VOICE
Write natural contemporary American English.
Sound editorial, human, specific, and confident.
Avoid generic AI phrasing, keyword stuffing, hashtags, emojis, and markdown.

CREATE EXACTLY THREE FIELDS

1. opening_excerpt
- 35 to 55 words.
- Usually 1–2 sentences.
- Lead with a strong supported fact, tension, contrast, turning point, or historical context.
- Use a fresh angle and fresh sentence structure.
- Complement the page title rather than repeat it.

2. seo_title
- Descriptive, concise, specific, and independently worded.
- Put the main artist, song, or subject early when natural.
- Roughly 40–70 characters when natural.
- Do not add the site name.
- Do not keyword-stuff.
- Do not copy or lightly paraphrase the supplied working title.

3. meta_description
- A page-specific human-readable summary.
- Aim for roughly 140–160 characters.
- Use a different construction from both the source title and opening excerpt.
- Do not start with "Discover," "Learn," "Read," or "Find out."
- Do not copy wording from the source article.

FINAL CHECK
Before returning:
- Verify every factual statement is supported by the supplied text.
- Verify the SEO title is structurally different from the supplied title.
- Verify the opening and meta do not reuse long exact phrases from the supplied article.
- Make all three fields distinct from one another.
Return only the required structured fields.
PROMPT;
    }
}
