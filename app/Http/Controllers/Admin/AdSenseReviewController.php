<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AdSenseReviewController extends Controller
{
    public function review(Request $request)
    {
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

        $bodyText = Str::limit($bodyText, 30000, '');
        $sourceText = Str::limit($sourceText, 24000, '');

        $youtubeCount = (int) ($data['youtube_count'] ?? 0);
        $imageCount = (int) ($data['image_count'] ?? 0);

        $prompt = <<<'PROMPT'
You are a conservative editorial policy reviewer for a publisher preparing an article for Google AdSense.

IMPORTANT LIMITS
- You are NOT Google and must never say an article or site is "AdSense approved."
- You cannot guarantee approval or rejection.
- Do not invent a Google minimum word count.
- Do not treat AI output as proof of copyright ownership or licensing.
- Do not claim plagiarism unless a supplied SOURCE/TRANSCRIPT provides evidence for comparison.
- If no SOURCE/TRANSCRIPT is supplied, evaluate only internal warning signs and say comparison is unavailable.

POLICY CONCEPTS TO APPLY
1. Google-served ads are not allowed on screens with embedded or copied content from others without additional commentary, curation, or otherwise adding value.
2. Examples of replicated-content risk include mirroring, framing, scraping, rewriting without added value, automatically generated content without manual review/curation, slight modification or synonym substitution, and sites dedicated to embedded media without substantial added value.
3. Pages should have real publisher-content and should not be unfinished, under construction, empty, or low value.
4. Advertising or paid promotional material must not exceed publisher-content on a monetized screen.
5. Intellectual-property rights must be respected. You cannot verify image/video rights from article text alone.
6. Manual editorial review matters. AI-generated or AI-rewritten material should not be treated as automatically safe.

REVIEW GOAL
Judge whether this article appears to provide genuine publisher value and identify issues a human should fix or review before publishing.

OUTPUT RULES
Return JSON only.

overall:
- PASS = no major replicated-content/value problem is apparent from the supplied material.
- NEED_REVIEW = one or more material issues require human review, but the article is not clearly high-risk.
- HIGH_RISK = supplied evidence strongly indicates copied/lightly rewritten/transcript-like/low-value content or serious unresolved policy risk.

For every field:
- Be concise and specific.
- Point to the actual issue.
- Do not fabricate facts.
- Keep copyright/media conclusions manual unless explicit licensing evidence is supplied.

SOURCE COMPARISON
If SOURCE/TRANSCRIPT is supplied:
- Compare structure, sequencing, phrasing patterns, and substantive value added.
- Do NOT penalize factual overlap by itself.
- Distinguish facts from copied expression.
- Call out light rewrite when the article follows the source too closely without meaningful added value.

If SOURCE/TRANSCRIPT is not supplied:
- State that direct originality comparison could not be performed.

Return exactly these fields:
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

        $models = array_values(array_unique(array_filter([
            config('gemini.model'),
            'gemini-3.8-flash',
            'gemini-3.6-flash',
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
        ])));

        $lastMessage = 'Gemini is temporarily unavailable. Please try again.';

        try {
            foreach ($models as $model) {
                $response = Http::withHeaders([
                        'x-goog-api-key' => $apiKey,
                    ])
                    ->acceptJson()
                    ->asJson()
                    ->timeout(90)
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/interactions',
                        [
                            'model' => $model,
                            'input' => $fullPrompt,
                            'response_format' => [
                                'type' => 'text',
                                'mime_type' => 'application/json',
                                'schema' => $schema,
                            ],
                        ]
                    );

                if (!$response->successful()) {
                    $message = $response->json('error.message') ?: 'Gemini request failed.';
                    $lastMessage = $message;

                    logger()->warning('Gemini AdSense review request failed', [
                        'model' => $model,
                        'status' => $response->status(),
                        'message' => $message,
                    ]);

                    $lower = mb_strtolower($message);

                    $isTransient =
                        in_array($response->status(), [429, 500, 502, 503, 504], true) ||
                        str_contains($lower, 'high demand') ||
                        str_contains($lower, 'temporarily') ||
                        str_contains($lower, 'overloaded') ||
                        str_contains($lower, 'resource exhausted') ||
                        str_contains($lower, 'resource_exhausted') ||
                        str_contains($lower, 'unavailable');

                    if ($isTransient) {
                        continue;
                    }

                    return response()->json([
                        'message' => $message,
                    ], 502);
                }

                $payload = $response->json();
                $outputText = $this->extractOutputText($payload);

                if (trim($outputText) === '') {
                    $lastMessage = 'Gemini returned no usable text.';
                    continue;
                }

                $result = json_decode(trim($outputText), true);

                if (!is_array($result)) {
                    $lastMessage = 'Gemini returned an invalid policy-review response.';
                    continue;
                }

                $overall = strtoupper(trim((string) ($result['overall'] ?? '')));

                if (!in_array($overall, ['PASS', 'NEED_REVIEW', 'HIGH_RISK'], true)) {
                    $lastMessage = 'Gemini returned an invalid review status.';
                    continue;
                }

                return response()->json([
                    'overall' => $overall,
                    'original_value' => $this->cleanLine($result['original_value'] ?? ''),
                    'replicated_content_risk' => $this->cleanLine($result['replicated_content_risk'] ?? ''),
                    'source_comparison' => $this->cleanLine($result['source_comparison'] ?? ''),
                    'youtube_embed_assessment' => $this->cleanLine($result['youtube_embed_assessment'] ?? ''),
                    'fact_check_items' => $this->cleanArray($result['fact_check_items'] ?? []),
                    'media_rights_items' => $this->cleanArray($result['media_rights_items'] ?? []),
                    'policy_flags' => $this->cleanArray($result['policy_flags'] ?? []),
                    'required_fixes' => $this->cleanArray($result['required_fixes'] ?? []),
                    'review_note' => $this->cleanLine($result['review_note'] ?? ''),
                    'ai_model' => $model,
                ]);
            }

            return response()->json([
                'message' => $lastMessage,
            ], 503);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'AI AdSense review failed. Please try again.',
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
            array_slice($value, 0, 20)
        )));
    }

    private function extractOutputText(array $payload): string
    {
        $outputText = '';

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

        return $outputText;
    }
}
