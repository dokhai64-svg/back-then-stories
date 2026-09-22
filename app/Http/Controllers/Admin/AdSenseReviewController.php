<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AdSenseReviewController extends Controller
{
    public function review(Request $request)
    {
        /*
         * The policy review can take longer than PHP's default 30 seconds.
         * Extend only this request; normal CMS requests are unchanged.
         */
        @ini_set('max_execution_time', '90');
        @set_time_limit(90);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:60000'],
            'source_text' => ['nullable', 'string', 'max:60000'],
            'youtube_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'image_count' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $apiKey = config('gemini.api_key');

        if (!$apiKey) {
            return response()->json([
                'message' => 'GEMINI_API_KEY is not configured in Railway.',
            ], 500);
        }

        $bodyText = $this->cleanText($data['body']);
        $sourceText = $this->cleanText($data['source_text'] ?? '');

        if (mb_strlen($bodyText) < 120) {
            return response()->json([
                'message' => 'Add a fuller article body before running AI AdSense Review.',
            ], 422);
        }

        $bodyText = Str::limit($bodyText, 16000, '');
        $sourceText = Str::limit($sourceText, 8000, '');

        $youtubeCount = (int) ($data['youtube_count'] ?? 0);
        $imageCount = (int) ($data['image_count'] ?? 0);

        $prompt = <<<'PROMPT'
You are a conservative editorial reviewer helping a publisher reduce Google AdSense policy risk.

Do not say Google will approve or reject the site.
Do not invent a minimum word count.
Do not claim copyright ownership, licensing, or plagiarism unless evidence is supplied.

Review the article for:
- genuine publisher value vs thin/replicated/light-rewrite content
- over-reliance on embedded video
- unfinished/low-value presentation
- factual claims that deserve manual verification
- media/copyright items that require human review
- practical fixes before publishing

Use these statuses only:
PASS = no major issue is apparent.
NEED_REVIEW = material human review is needed.
HIGH_RISK = strong signs of low-value/replicated/light-rewrite content or another serious unresolved risk.

LANGUAGE:
- Write ALL human-readable review text in natural Vietnamese.
- Keep the machine status field `overall` exactly as PASS, NEED_REVIEW, or HIGH_RISK.
- Do not translate the `overall` enum values.

Keep every text field brief: 1-2 sentences.
Arrays: maximum 5 short items.
If no source/transcript is supplied, say direct source comparison is unavailable.

Return JSON only with exactly:
overall
original_value
replicated_content_risk
source_comparison
youtube_embed_assessment
fact_check_items
media_rights_items
policy_flags
required_fixes
review_note
PROMPT;

        $fullPrompt =
            $prompt .
            "\n\nARTICLE TITLE:\n" . $data['title'] .
            "\n\nARTICLE BODY:\n" . $bodyText .
            "\n\nYOUTUBE EMBED COUNT:\n" . $youtubeCount .
            "\n\nARTICLE IMAGE COUNT:\n" . $imageCount .
            "\n\nSOURCE / TRANSCRIPT FOR COMPARISON:\n" .
            ($sourceText !== '' ? $sourceText : '[NOT PROVIDED]');

        $schema = [
            'type' => 'object',
            'properties' => [
                'overall' => [
                    'type' => 'string',
                    'enum' => ['PASS', 'NEED_REVIEW', 'HIGH_RISK'],
                ],
                'original_value' => ['type' => 'string'],
                'replicated_content_risk' => ['type' => 'string'],
                'source_comparison' => ['type' => 'string'],
                'youtube_embed_assessment' => ['type' => 'string'],
                'fact_check_items' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'media_rights_items' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'policy_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'required_fixes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'review_note' => ['type' => 'string'],
            ],
            'required' => [
                'overall',
                'original_value',
                'replicated_content_risk',
                'source_comparison',
                'youtube_embed_assessment',
                'fact_check_items',
                'media_rights_items',
                'policy_flags',
                'required_fixes',
                'review_note',
            ],
        ];

        /*
         * V3.3 CAPACITY FAILOVER
         *
         * Use the same Gemini Flash model family already used by the CMS.
         * Try the configured model first, then bounded fallbacks only when
         * Google reports temporary capacity/rate/service problems.
         *
         * Each attempt has a short timeout so the whole one-click review
         * stays inside this request's 90-second execution budget.
         */
        $models = array_values(
            array_unique(
                array_filter([
                    config(
                        'gemini.model',
                        'gemini-3.8-flash'
                    ),
                    'gemini-3.8-flash',
                    'gemini-3.7-flash',
                    'gemini-3.6-flash',
                    'gemini-3.5-flash',
                    'gemini-3.5-flash-lite',
                                ])
            )
        );

        $lastMessage =
            'Gemini is temporarily unavailable. Please try again.';

        $startedAt = microtime(true);

        try {
            foreach ($models as $model) {
                /*
                 * Keep enough headroom for Laravel to format and return the
                 * final response even if several Gemini models are busy.
                 */
                if (
                    (microtime(true) - $startedAt)
                    > 78
                ) {
                    break;
                }

                try {
                    $response = Http::withHeaders([
                            'x-goog-api-key' =>
                                $apiKey,
                        ])
                        ->acceptJson()
                        ->asJson()
                        ->connectTimeout(4)
                        ->timeout(18)
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
                                                'text' =>
                                                    $fullPrompt,
                                            ],
                                        ],
                                    ],
                                ],
                                'generationConfig' => [
                                    'responseMimeType' =>
                                        'application/json',
                                    'responseSchema' =>
                                        $schema,
                                    'temperature' => 0.1,
                                    'maxOutputTokens' => 1600,
                                ],
                            ]
                        );

                } catch (ConnectionException $e) {
                    $lastMessage =
                        'Gemini model '
                        . $model
                        . ' timed out.';

                    logger()->warning(
                        'Gemini AdSense V3.3 model timeout',
                        [
                            'model' =>
                                $model,
                            'message' =>
                                $e->getMessage(),
                        ]
                    );

                    continue;
                }

                if (!$response->successful()) {
                    $message =
                        $response->json(
                            'error.message'
                        )
                        ?: 'Gemini request failed.';

                    $lastMessage =
                        (string) $message;

                    $lowerMessage =
                        mb_strtolower(
                            (string) $message
                        );

                    $isUnavailableModel =
                        $response->status() === 404
                        || str_contains(
                            $lowerMessage,
                            'no longer available'
                        )
                        || str_contains(
                            $lowerMessage,
                            'not available to new users'
                        )
                        || str_contains(
                            $lowerMessage,
                            'not found'
                        );

                    $isTransient =
                        in_array(
                            $response->status(),
                            [
                                429,
                                500,
                                502,
                                503,
                                504,
                            ],
                            true
                        )
                        || str_contains(
                            $lowerMessage,
                            'high demand'
                        )
                        || str_contains(
                            $lowerMessage,
                            'temporarily'
                        )
                        || str_contains(
                            $lowerMessage,
                            'overloaded'
                        )
                        || str_contains(
                            $lowerMessage,
                            'resource exhausted'
                        )
                        || str_contains(
                            $lowerMessage,
                            'resource_exhausted'
                        )
                        || str_contains(
                            $lowerMessage,
                            'unavailable'
                        );

                    logger()->warning(
                        'Gemini AdSense V3.3 model failed',
                        [
                            'model' =>
                                $model,
                            'status' =>
                                $response->status(),
                            'transient' =>
                                $isTransient,
                            'message' =>
                                $message,
                        ]
                    );

                    /*
                     * Busy/rate/service issue: automatically try the next
                     * Flash model. Authentication or malformed requests are
                     * returned immediately because another model will not
                     * fix them.
                     */
                    if ($isUnavailableModel) {
                        logger()->info(
                            'Gemini AdSense model unavailable; skipping fallback',
                            [
                                'model' => $model,
                                'status' => $response->status(),
                                'message' => $message,
                            ]
                        );

                        continue;
                    }

                    if ($isTransient) {
                        $retryAfter =
                            trim(
                                (string) $response->header(
                                    'Retry-After'
                                )
                            );

                        $delayMs =
                            ctype_digit($retryAfter)
                                ? min(
                                    2500,
                                    max(
                                        500,
                                        ((int) $retryAfter) * 1000
                                    )
                                )
                                : 700;

                        usleep(
                            $delayMs * 1000
                        );

                        continue;
                    }

                    return response()->json([
                        'message' =>
                            'Đánh giá Gemini thất bại: '
                            . Str::limit(
                                (string) $message,
                                260,
                                ''
                            ),
                    ], 502);
                }

                $payload =
                    $response->json();

                $outputText = '';

                foreach (
                    (
                        $payload['candidates'][0]
                        ['content']['parts']
                        ?? []
                    )
                    as $part
                ) {
                    if (
                        isset($part['text'])
                        && is_string(
                            $part['text']
                        )
                    ) {
                        $outputText .=
                            $part['text'];
                    }
                }

                $outputText =
                    trim(
                        $outputText
                    );

                if ($outputText === '') {
                    $lastMessage =
                        'Gemini không trả về nội dung đánh giá chính sách có thể sử dụng.';

                    logger()->warning(
                        'Gemini AdSense V3.3 returned no text',
                        [
                            'model' =>
                                $model,
                        ]
                    );

                    continue;
                }

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

                if (!is_array($result)) {
                    $lastMessage =
                        'Gemini trả về phản hồi đánh giá không hợp lệ.';

                    logger()->warning(
                        'Gemini AdSense V3.3 JSON parse failed',
                        [
                            'model' =>
                                $model,
                            'preview' =>
                                mb_substr(
                                    $outputText,
                                    0,
                                    500
                                ),
                        ]
                    );

                    continue;
                }

                $overall =
                    strtoupper(
                        trim(
                            (string) (
                                $result['overall']
                                ?? ''
                            )
                        )
                    );

                if (
                    !in_array(
                        $overall,
                        [
                            'PASS',
                            'NEED_REVIEW',
                            'HIGH_RISK',
                        ],
                        true
                    )
                ) {
                    $lastMessage =
                        'Gemini trả về trạng thái đánh giá không hợp lệ.';

                    continue;
                }

                return response()->json([
                    'overall' =>
                        $overall,
                    'original_value' =>
                        $this->cleanLine(
                            $result['original_value']
                            ?? ''
                        ),
                    'replicated_content_risk' =>
                        $this->cleanLine(
                            $result['replicated_content_risk']
                            ?? ''
                        ),
                    'source_comparison' =>
                        $this->cleanLine(
                            $result['source_comparison']
                            ?? ''
                        ),
                    'youtube_embed_assessment' =>
                        $this->cleanLine(
                            $result['youtube_embed_assessment']
                            ?? ''
                        ),
                    'fact_check_items' =>
                        $this->cleanArray(
                            $result['fact_check_items']
                            ?? []
                        ),
                    'media_rights_items' =>
                        $this->cleanArray(
                            $result['media_rights_items']
                            ?? []
                        ),
                    'policy_flags' =>
                        $this->cleanArray(
                            $result['policy_flags']
                            ?? []
                        ),
                    'required_fixes' =>
                        $this->cleanArray(
                            $result['required_fixes']
                            ?? []
                        ),
                    'review_note' =>
                        $this->cleanLine(
                            $result['review_note']
                            ?? ''
                        ),
                    'ai_model' =>
                        $model,
                    'ai_api' =>
                        'generateContent',
                ]);
            }

            return response()->json([
                'message' =>
                    'Không model Gemini khả dụng nào hoàn tất được lượt kiểm tra hiện tại. Hãy chạy lại sau ít phút. Phản hồi cuối: '
                    . Str::limit(
                        $lastMessage,
                        220,
                        ''
                    ),
            ], 503);

        } catch (Throwable $e) {
            report($e);

            logger()->error(
                'Gemini AdSense V3.3 server error',
                [
                    'exception' =>
                        get_class($e),
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Đánh giá AI AdSense thất bại. Hãy thử lại.',
            ], 500);
        }
    }

    private function cleanText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    private function cleanLine(mixed $value): string
    {
        return Str::limit(
            trim(preg_replace('/\s+/u', ' ', (string) $value)),
            1600,
            ''
        );
    }

    private function cleanArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => $this->cleanLine($item),
            array_slice($value, 0, 5)
        )));
    }

}
