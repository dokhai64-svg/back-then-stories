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
            'mode' => [
                'nullable',
                'in:seo,rewrite,chapters',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'body' => [
                'required',
                'string',
                'max:60000',
            ],
            'chapter_count' => [
                'nullable',
                'integer',
                'min:2',
                'max:8',
            ],
        ]);

        if (($data['mode'] ?? 'seo') === 'rewrite') {
            return $this->rewriteBody(
                (string) $data['title'],
                (string) $data['body'],
                $requestId
            );
        }

        if (($data['mode'] ?? 'seo') === 'chapters') {
            return $this->analyzeChapters(
                (string) $data['title'],
                (string) $data['body'],
                (int) (
                    $data['chapter_count']
                    ?? 4
                ),
                $requestId
            );
        }

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

            /*
             * SEO TITLE POLICY
             *
             * Use the article's own title as the SEO title. The editorial title
             * is already the strongest page headline, so we keep both aligned
             * instead of generating a shorter/generic alternative.
             */
            $seoTitle = $sourceTitle;

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
                    $seoTitle,
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

    private function analyzeChapters(
        string $title,
        string $sourceHtml,
        int $chapterCount,
        string $requestId
    ) {
        $apiKey = trim(
            (string) config(
                'gemini.api_key'
            )
        );

        if ($apiKey === '') {
            return response()->json([
                'message' =>
                    'GEMINI_API_KEY is not configured in Railway.',
                'request_id' =>
                    $requestId,
            ], 500);
        }

        $sourceText =
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    strip_tags(
                        $sourceHtml
                    )
                )
            );

        if (
            mb_strlen(
                $sourceText
            ) < 350
        ) {
            return response()->json([
                'message' =>
                    'Add more source content before analyzing chapters.',
                'request_id' =>
                    $requestId,
            ], 422);
        }

        $chapterCount =
            max(
                2,
                min(
                    8,
                    $chapterCount
                )
            );

        $sourceForPrompt =
            Str::limit(
                $sourceHtml,
                42000,
                ''
            );

        $prompt =
            $this->chapterPrompt(
                $chapterCount
            )
            . "\n\nARTICLE TITLE:\n"
            . trim($title)
            . "\n\nSOURCE ARTICLE HTML:\n"
            . $sourceForPrompt;

        $models = [
            'gemini-flash-lite-latest',
            'gemini-3.5-flash-lite',
        ];

        $startedAt =
            microtime(true);

        $hardBudgetSeconds =
            25.0;

        $lastMessage =
            'Gemini is temporarily unavailable. Please try again.';

        foreach ($models as $model) {
            $remaining =
                $hardBudgetSeconds
                - (
                    microtime(true)
                    - $startedAt
                );

            if ($remaining < 5) {
                break;
            }

            $timeout =
                (int) max(
                    5,
                    min(
                        14,
                        floor(
                            $remaining - 2
                        )
                    )
                );

            try {
                $response =
                    Http::withHeaders([
                        'x-goog-api-key' =>
                            $apiKey,
                    ])
                        ->acceptJson()
                        ->asJson()
                        ->connectTimeout(2)
                        ->timeout($timeout)
                        ->post(
                            'https://generativelanguage.googleapis.com/v1beta/interactions',
                            [
                                'model' =>
                                    $model,
                                'input' =>
                                    $prompt,
                                'generation_config' => [
                                    'thinking_level' =>
                                        'low',
                                    'max_output_tokens' =>
                                        6000,
                                ],
                                'response_format' => [
                                    'type' =>
                                        'text',
                                    'mime_type' =>
                                        'application/json',
                                    'schema' => [
                                        'type' =>
                                            'object',
                                        'properties' => [
                                            'intro_html' => [
                                                'type' =>
                                                    'string',
                                            ],
                                            'chapters' => [
                                                'type' =>
                                                    'array',
                                                'items' => [
                                                    'type' =>
                                                        'object',
                                                    'properties' => [
                                                        'title' => [
                                                            'type' =>
                                                                'string',
                                                        ],
                                                        'body' => [
                                                            'type' =>
                                                                'string',
                                                        ],
                                                    ],
                                                    'required' => [
                                                        'title',
                                                        'body',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'intro_html',
                                            'chapters',
                                        ],
                                    ],
                                ],
                            ]
                        );

            } catch (ConnectionException $e) {
                $lastMessage =
                    'Connection timeout while analyzing chapters.';

                continue;

            } catch (Throwable $e) {
                $lastMessage =
                    'Gemini chapter analysis request failed.';

                logger()->warning(
                    'Gemini chapter analysis exception',
                    [
                        'request_id' =>
                            $requestId,
                        'model' =>
                            $model,
                        'message' =>
                            $e->getMessage(),
                    ]
                );

                continue;
            }

            if (!$response->successful()) {
                $lastMessage =
                    trim(
                        (string) (
                            $response->json(
                                'error.message'
                            )
                            ?: 'Gemini chapter analysis failed.'
                        )
                    );

                continue;
            }

            $outputText =
                $this->extractOutputText(
                    $response->json()
                );

            $outputText =
                preg_replace(
                    '/^```(?:json)?\s*|\s*```$/i',
                    '',
                    $outputText
                );

            $result =
                json_decode(
                    trim(
                        (string) $outputText
                    ),
                    true
                );

            if (
                !is_array($result)
                || !is_array(
                    $result['chapters']
                    ?? null
                )
            ) {
                $lastMessage =
                    'Gemini returned invalid chapter JSON.';

                continue;
            }

            $chapters = [];

            foreach (
                array_slice(
                    $result['chapters'],
                    0,
                    8
                )
                as $chapter
            ) {
                if (!is_array($chapter)) {
                    continue;
                }

                $chapterTitle =
                    $this->cleanText(
                        $chapter['title']
                        ?? ''
                    );

                $chapterBody =
                    $this->sanitizeRewrittenHtml(
                        (string) (
                            $chapter['body']
                            ?? ''
                        )
                    );

                if (
                    mb_strlen(
                        strip_tags(
                            $chapterBody
                        )
                    ) < 80
                ) {
                    continue;
                }

                $chapters[] = [
                    'title' =>
                        Str::limit(
                            $chapterTitle,
                            180,
                            ''
                        ),
                    'body' =>
                        $chapterBody,
                ];
            }

            if (count($chapters) < 2) {
                $lastMessage =
                    'Gemini produced too few usable chapters.';

                continue;
            }

            $intro =
                $this->sanitizeRewrittenHtml(
                    (string) (
                        $result['intro_html']
                        ?? ''
                    )
                );

            return response()->json([
                'intro_html' =>
                    $intro,
                'chapters' =>
                    $chapters,
                'ai_model' =>
                    $model,
                'request_id' =>
                    $requestId,
            ]);
        }

        return response()->json([
            'message' =>
                'AI could not finish chapter analysis within the server time limit. '
                . Str::limit(
                    $lastMessage,
                    220,
                    ''
                ),
            'request_id' =>
                $requestId,
        ], 503);
    }

    private function chapterPrompt(
        int $chapterCount
    ): string {
        return <<<PROMPT
You are an editorial story-structure assistant for an American-English classic music and entertainment history website.

Your job is to turn the supplied article into a strong multi-page reading experience.

Create exactly {$chapterCount} meaningful chapters.

FACT SAFETY
- Use only facts supported by the source.
- Do not invent quotes, dates, motives, awards, chart positions, relationships, or events.
- Preserve names, song titles, places, dates, and factual identifiers accurately.
- If a claim is uncertain from the source, omit it.

STRUCTURE
- Create a short INTRO / DESCRIPTION that sets up the story without giving away every payoff.
- Then divide the substantive story into {$chapterCount} chapters.
- Each chapter must advance the story. Do not repeat the intro.
- Avoid thin pages. Balance the available source material across the chapters.
- Give every chapter a specific curiosity-driven title.
- Keep chronology and cause/effect clear when the source supports it.
- End chapters naturally; do not add fake cliffhangers.

WRITING
- Natural contemporary American English.
- Rewrite sentences independently rather than copying complete source sentences.
- No generic AI phrasing.
- No hashtags.
- No calls to action.
- No source-site mentions.
- No invented commentary.

HTML
- intro_html and each chapter body must contain clean article HTML.
- Use <p> for paragraphs.
- Use <h2>, <h3>, <blockquote>, <ul>, <ol>, <li>, <strong>, and <em> only when useful.
- Never output <html>, <head>, <body>, <script>, style tags, or markdown.

Return JSON only:
{
  "intro_html": "...",
  "chapters": [
    {"title": "...", "body": "..."}
  ]
}
PROMPT;
    }

    private function rewriteBody(
        string $title,
        string $sourceHtml,
        string $requestId
    ) {
        $apiKey = trim(
            (string) config('gemini.api_key')
        );

        if ($apiKey === '') {
            return response()->json([
                'message' =>
                    'GEMINI_API_KEY is not configured in Railway.',
                'request_id' => $requestId,
            ], 500);
        }

        $sourceText = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($sourceHtml)
            )
        );

        if (mb_strlen($sourceText) < 200) {
            return response()->json([
                'message' =>
                    'The article body is too short to rewrite.',
                'request_id' => $requestId,
            ], 422);
        }

        /*
         * Protect existing media so AI cannot alter image/video URLs.
         * The placeholders are restored after the rewrite.
         */
        [$protectedHtml, $mediaBlocks] =
            $this->protectMediaBlocks($sourceHtml);

        $protectedHtml = Str::limit(
            $protectedHtml,
            42000,
            ''
        );

        $sourceWordCount = count(
            $this->words($sourceHtml)
        );

        $prompt =
            $this->rewritePrompt()
            . "\n\nARTICLE TITLE:\n"
            . trim($title)
            . "\n\nSOURCE WORD COUNT:\n"
            . $sourceWordCount
            . "\n\nSOURCE BODY HTML:\n"
            . $protectedHtml;

        /*
         * Keep this inside Railway/PHP's 30-second request limit.
         * Whole-article rewriting needs more output time than SEO,
         * so use only fast models and a strict total budget.
         */
        $models = [
            'gemini-flash-lite-latest',
            'gemini-3.5-flash-lite',
        ];

        $startedAt = microtime(true);
        $hardBudgetSeconds = 25.0;

        $lastMessage =
            'Gemini is temporarily unavailable. Please try again.';

        foreach ($models as $model) {
            $elapsed =
                microtime(true) - $startedAt;

            $remaining =
                $hardBudgetSeconds - $elapsed;

            if ($remaining < 5) {
                break;
            }

            $timeout =
                (int) max(
                    5,
                    min(14, floor($remaining - 2))
                );

            try {
                $response = Http::withHeaders([
                        'x-goog-api-key' => $apiKey,
                    ])
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout(2)
                    ->timeout($timeout)
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/interactions',
                        [
                            'model' => $model,
                            'input' => $prompt,
                            'generation_config' => [
                                'thinking_level' => 'low',
                                'max_output_tokens' => 6500,
                            ],
                            'response_format' => [
                                'type' => 'text',
                                'mime_type' =>
                                    'application/json',
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'rewritten_body' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'required' => [
                                        'rewritten_body',
                                    ],
                                ],
                            ],
                        ]
                    );

            } catch (ConnectionException $e) {
                $lastMessage =
                    'Connection timeout while rewriting with '
                    . $model
                    . '.';

                logger()->warning(
                    'Gemini rewrite timeout; trying fallback',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'message' => $e->getMessage(),
                    ]
                );

                continue;

            } catch (Throwable $e) {
                $lastMessage =
                    'Gemini rewrite request error on '
                    . $model
                    . '.';

                logger()->warning(
                    'Gemini rewrite exception; trying fallback',
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
                        ?: 'Gemini rewrite request failed.'
                    )
                );

                $lastMessage = $message;

                logger()->warning(
                    'Gemini rewrite model request failed',
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
                            'Gemini rewrite request format error: '
                            . Str::limit(
                                $message,
                                240,
                                ''
                            ),
                        'request_id' => $requestId,
                    ], 502);
                }

                continue;
            }

            $payload = $response->json();
            $outputText =
                $this->extractOutputText($payload);

            if ($outputText === '') {
                $lastMessage =
                    'Gemini returned no rewritten article.';
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
                    'Gemini returned invalid JSON while rewriting.';
                continue;
            }

            $rewritten = trim(
                (string) (
                    $result['rewritten_body']
                    ?? ''
                )
            );

            if ($rewritten === '') {
                $lastMessage =
                    'Gemini returned an empty rewritten article.';
                continue;
            }

            $rewritten =
                $this->sanitizeRewrittenHtml(
                    $rewritten
                );

            $rewritten =
                $this->restoreMediaBlocks(
                    $rewritten,
                    $mediaBlocks
                );

            $quality =
                $this->passesRewriteGuard(
                    $sourceHtml,
                    $rewritten
                );

            if (!$quality['ok']) {
                $lastMessage =
                    'Rewrite originality/quality check failed: '
                    . $quality['reason'];

                logger()->warning(
                    'Gemini rewrite guard rejected output',
                    [
                        'request_id' => $requestId,
                        'model' => $model,
                        'reason' => $quality['reason'],
                    ]
                );

                continue;
            }

            return response()->json([
                'rewritten_body' => $rewritten,
                'ai_model' => $model,
                'request_id' => $requestId,
                'originality_check' => 'passed',
            ]);
        }

        return response()->json([
            'message' =>
                'AI could not finish the rewrite within the server time limit. '
                . Str::limit(
                    $lastMessage,
                    220,
                    ''
                ),
            'request_id' => $requestId,
        ], 503);
    }

    private function protectMediaBlocks(
        string $html
    ): array {
        $media = [];

        $protected = preg_replace_callback(
            '/<iframe\b[^>]*>.*?<\/iframe>|<img\b[^>]*>/is',
            function ($match) use (&$media) {
                $index = count($media);
                $token =
                    '[[MEDIA_' . $index . ']]';

                $media[$token] =
                    $match[0];

                return $token;
            },
            $html
        );

        return [
            is_string($protected)
                ? $protected
                : $html,
            $media,
        ];
    }

    private function restoreMediaBlocks(
        string $html,
        array $mediaBlocks
    ): string {
        foreach ($mediaBlocks as $token => $block) {
            $html = str_replace(
                $token,
                $block,
                $html
            );
        }

        return $html;
    }

    private function sanitizeRewrittenHtml(
        string $html
    ): string {
        /*
         * AI may format the response, but it is still untrusted output.
         * Keep only simple editorial tags.
         * Original IMG/IFRAME blocks are restored separately afterward.
         */
        $html = strip_tags(
            $html,
            '<p><h2><h3><blockquote><ul><ol><li><strong><b><em><i><u><br>'
        );

        $html = preg_replace(
            '/\s+on[a-z]+\s*=\s*(["\']).*?\1/is',
            '',
            $html
        );

        return trim(
            is_string($html)
                ? $html
                : ''
        );
    }

    private function passesRewriteGuard(
        string $sourceHtml,
        string $rewrittenHtml
    ): array {
        $sourceWords =
            $this->words($sourceHtml);

        $rewrittenWords =
            $this->words($rewrittenHtml);

        $sourceCount =
            count($sourceWords);

        $rewrittenCount =
            count($rewrittenWords);

        if ($sourceCount < 40) {
            return [
                'ok' => false,
                'reason' =>
                    'source article is too short',
            ];
        }

        $ratio =
            $rewrittenCount / max(1, $sourceCount);

        if ($ratio < 0.70) {
            return [
                'ok' => false,
                'reason' =>
                    'rewritten article became too short',
            ];
        }

        /*
         * Editorial rewrites may be longer than a thin source when the
         * same supported facts can be reorganized and explained with
         * stronger context. Cap expansion to prevent filler.
         */
        if ($ratio > 2.60 || $rewrittenCount > 1500) {
            return [
                'ok' => false,
                'reason' =>
                    'rewritten article became excessively long',
            ];
        }

        $sourceLookup = [];

        $gramSize = 10;

        for (
            $i = 0;
            $i <= $sourceCount - $gramSize;
            $i++
        ) {
            $gram = implode(
                ' ',
                array_slice(
                    $sourceWords,
                    $i,
                    $gramSize
                )
            );

            $sourceLookup[$gram] = true;
        }

        $rewrittenGramCount =
            max(
                0,
                $rewrittenCount - $gramSize + 1
            );

        if ($rewrittenGramCount > 0) {
            $matches = 0;

            for (
                $i = 0;
                $i <= $rewrittenCount - $gramSize;
                $i++
            ) {
                $gram = implode(
                    ' ',
                    array_slice(
                        $rewrittenWords,
                        $i,
                        $gramSize
                    )
                );

                if (isset($sourceLookup[$gram])) {
                    $matches++;
                }
            }

            $overlap =
                $matches / $rewrittenGramCount;

            if ($overlap > 0.08) {
                return [
                    'ok' => false,
                    'reason' =>
                        'too many exact 10-word sequences remained',
                ];
            }
        }

        return [
            'ok' => true,
            'reason' => 'passed',
        ];
    }

    private function rewritePrompt(): string
    {
        return <<<'PROMPT'
You are the senior American-English editorial writer for Back Then Stories, an independent publication focused on classic music, singers, entertainment history, cultural memory, and meaningful stories from past generations.

The supplied article may come from another website. Treat it ONLY as source material containing facts and events. Do not perform a light paraphrase. Your task is to create a genuinely independent editorial article with its own structure, pacing, emphasis, and narrative logic.

CORE EDITORIAL GOAL
Turn source material into a useful, substantial reader experience rather than a synonym-swapped rewrite.
The finished article should feel like it was independently researched and edited from the facts available in the source.

FACTUAL SAFETY — NON-NEGOTIABLE
- Use only facts supported by the supplied source.
- Preserve proper names, song titles, album titles, film titles, dates, chart positions, places, and other factual identifiers when supported.
- Never invent quotes, motives, relationships, dates, chart positions, awards, sales figures, recording details, causes, or historical claims.
- If the source is uncertain, keep the rewritten wording equally cautious.
- Do not turn "recorded" into "released," "released" into "recorded," or otherwise change factual meaning.
- Do not claim to have verified a fact externally.
- If the source does not support a detail, omit it.

EDITORIAL TRANSFORMATION
- Rebuild the article from scratch instead of following the source paragraph by paragraph.
- Identify the strongest supported angle: a turning point, contradiction, career shift, creative decision, historical context, or consequence.
- Open with that angle rather than copying the source opening.
- Reorder facts when doing so improves clarity or storytelling and does not distort chronology.
- Group related facts into meaningful sections.
- Explain why supported events matter using only context that can reasonably be derived from the supplied facts.
- Connect cause and effect only when the source supports that relationship.
- Add useful synthesis, transitions, and context, but never add unsupported factual claims.
- Avoid repeating the same fact in multiple ways merely to increase length.
- Do not pad the article with generic biography, vague praise, or filler.

ORIGINALITY
- Rewrite all prose from scratch.
- Change sentence structure, paragraph structure, transitions, emphasis, and information order where useful.
- Never copy a complete sentence from the source.
- Avoid long exact phrases from the source except proper names, titles, dates, and unavoidable factual terms.
- Do not merely replace words with synonyms.
- Do not imitate the source site's headline rhythm, promotional language, paragraph sequence, or CTA style.
- Do not mention the source website.
- Do not add citations or source attributions unless the supplied source itself requires attribution for a specific claim.

DEPTH AND LENGTH
Use SOURCE WORD COUNT only as a planning signal, never as a quota.
- If the source is under about 450 words, do NOT force the rewrite to 1,000 words. Produce the strongest complete article the supported facts allow, usually about 600–900 words when the material can sustain it.
- If the source is about 450–800 words, aim for roughly 800–1,100 words when the facts support that depth.
- If the source is over about 800 words, aim for roughly 1,000–1,300 words when natural.
- A shorter factual article is better than a longer padded one.
- Never invent detail just to hit a word target.

STRUCTURE
Use a coherent editorial arc appropriate to the story. A strong article may include:
- a specific opening hook based on a supported fact or turning point;
- concise background needed to understand the event;
- the central conflict, decision, performance, recording, or career moment;
- what happened next;
- why the moment remains historically or culturally meaningful.

Do NOT force the exact same structure on every article. Vary the organization based on the story.

STYLE
- Natural contemporary American English.
- Human, specific, confident, and readable.
- Prefer medium-length paragraphs suitable for mobile reading.
- Use occasional short paragraphs only when they improve emphasis.
- Avoid a page made entirely of one-sentence paragraphs.
- Avoid exaggerated praise unless supported by facts.
- Avoid generic AI language such as "delve into," "in the tapestry of," "timeless legacy," "this article explores," "journey through," or "stands as a testament."
- No keyword stuffing.
- No hashtags.
- No emojis.
- No fake quotations.
- No call to action.
- Do not end with a generic question to the reader.

HEADINGS
- Use <h2> only when a meaningful section break improves readability.
- Keep headings specific to the story, not generic labels such as "Early Life," "The Legacy," or "Conclusion" unless genuinely appropriate.
- Do not overuse headings.

MEDIA PRESERVATION
Any token in the form [[MEDIA_0]], [[MEDIA_1]], etc. represents an existing image or video.
- Preserve every MEDIA token EXACTLY once.
- Keep it near the most relevant surrounding material.
- Never alter, remove, duplicate, rename, or wrap a MEDIA token in invented markup.

HTML OUTPUT
- Return clean article-body HTML only inside the required JSON field.
- Use <p> for normal paragraphs.
- Use <h2> or <h3> only when useful.
- You may use <blockquote>, <ul>, <ol>, <li>, <strong>, and <em> when justified.
- Do not output <html>, <head>, <body>, <script>, style tags, CSS, markdown, title text, SEO fields, or commentary.

FINAL QUALITY CHECK
Before returning:
1. Every factual statement is supported by the supplied source.
2. The article is not a sentence-by-sentence or paragraph-by-paragraph paraphrase.
3. The article has a distinct editorial angle and independent structure.
4. The article adds reader value through organization, context, synthesis, and clarity rather than invented facts.
5. No unsupported quote, statistic, date, motive, relationship, or historical claim has been added.
6. No repetitive filler was added just to increase word count.
7. Every MEDIA token remains unchanged and appears exactly once.
8. The result reads like an independent Back Then Stories article, not a rewritten copy.
9. Return exactly one JSON field: rewritten_body.
PROMPT;
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
        /*
         * SEO title intentionally matches the article title.
         * Originality checks therefore focus on the opening excerpt and meta
         * description, where copied source phrasing would still be a risk.
         */

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
- Keep the supplied page title unchanged for seo_title.
- Use a different sentence structure and information order.
- The SEO title must be exactly the supplied SOURCE / WORKING TITLE.
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
- Return the supplied SOURCE / WORKING TITLE exactly as provided.
- Do not shorten it.
- Do not rewrite it.
- Do not add the site name.

3. meta_description
- A page-specific human-readable summary.
- Aim for roughly 140–160 characters.
- Use a different construction from both the source title and opening excerpt.
- Do not start with "Discover," "Learn," "Read," or "Find out."
- Do not copy wording from the source article.

FINAL CHECK
Before returning:
- Verify every factual statement is supported by the supplied text.
- Verify the SEO title exactly matches the supplied SOURCE / WORKING TITLE.
- Verify the opening and meta do not reuse long exact phrases from the supplied article.
- Make all three fields distinct from one another.
Return only the required structured fields.
PROMPT;
    }
}
