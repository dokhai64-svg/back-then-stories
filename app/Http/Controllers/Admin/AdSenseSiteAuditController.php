<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

class AdSenseSiteAuditController extends Controller
{
    public function index()
    {
        return view(
            'admin.adsense-audit.index'
        );
    }

    public function run(): JsonResponse
    {
        $articles = Article::query()
            ->where(
                'status',
                'published'
            )
            ->with([
                'site',
                'category',
                'chapters',
            ])
            ->orderByDesc(
                'published_at'
            )
            ->orderByDesc(
                'id'
            )
            ->get();

        $duplicateTitles =
            $articles
                ->groupBy(
                    fn (Article $article) =>
                        $this->normalizeText(
                            (string) $article->title
                        )
                )
                ->filter(
                    fn ($group, $key) =>
                        $key !== ''
                        && $group->count() > 1
                );

        $bodyHashes = [];

        foreach ($articles as $article) {
            $reviewText =
                $this->articleReviewText(
                    $article
                );

            if (
                $this->wordCount(
                    $reviewText
                ) >= 80
            ) {
                $hash =
                    md5(
                        $this->normalizeText(
                            $reviewText
                        )
                    );

                $bodyHashes[$hash][] =
                    $article->id;
            }
        }

        $duplicateBodies =
            collect(
                $bodyHashes
            )
                ->filter(
                    fn ($ids) =>
                        count($ids) > 1
                );

        $reports =
            $articles
                ->map(
                    fn (Article $article) =>
                        $this->auditArticle(
                            $article,
                            $duplicateTitles,
                            $duplicateBodies
                        )
                )
                ->values();

        $siteChecks =
            $this->auditSite();

        $siteBlocks =
            collect(
                $siteChecks
            )
                ->where(
                    'level',
                    'block'
                )
                ->count();

        $siteWarnings =
            collect(
                $siteChecks
            )
                ->where(
                    'level',
                    'warn'
                )
                ->count();

        $aiCandidates =
            $reports
                ->filter(
                    fn (array $report) =>
                        $report[
                            'ai_review_needed'
                        ]
                )
                ->map(
                    function (
                        array $report
                    ) use (
                        $articles
                    ) {
                        /** @var Article|null $article */
                        $article =
                            $articles->firstWhere(
                                'id',
                                $report['id']
                            );

                        if (!$article) {
                            return null;
                        }

                        return [
                            'article_id' =>
                                $article->id,
                            'title' =>
                                (string) $article->title,
                            'body' =>
                                $this->articleReviewHtml(
                                    $article
                                ),
                            'youtube_count' =>
                                $report[
                                    'youtube_count'
                                ],
                            'image_count' =>
                                $report[
                                    'body_image_count'
                                ]
                                + (
                                    $article->featured_image
                                        ? 1
                                        : 0
                                ),
                        ];
                    }
                )
                ->filter()
                ->values();

        return response()->json([
            'published_count' =>
                $articles->count(),
            'ai_candidate_count' =>
                $aiCandidates->count(),
            'site_blocks' =>
                $siteBlocks,
            'site_warnings' =>
                $siteWarnings,
            'site_checks' =>
                $siteChecks,
            'articles' =>
                $reports,
            'ai_candidates' =>
                $aiCandidates,
            'manual_checks' => [
                'Quyền sử dụng ảnh/video/media phải được xác minh thủ công; hệ thống không thể chứng minh license.',
                'Không tự click quảng cáo và không khuyến khích người dùng click quảng cáo.',
                'Sau khi Google thực sự phân phối ads, kiểm tra lại bố cục Desktop/Mobile và Policy Center.',
                'Rà soát Google Publisher Policies / Restrictions mới nhất trước khi gửi Request Review.',
            ],
            'policy_links' => [
                [
                    'label' =>
                        'AdSense Program Policies',
                    'url' =>
                        'https://support.google.com/adsense/answer/48182',
                ],
                [
                    'label' =>
                        'Google Publisher Policies',
                    'url' =>
                        'https://support.google.com/publisherpolicies/answer/10502938',
                ],
                [
                    'label' =>
                        'Replicated Content',
                    'url' =>
                        'https://support.google.com/publisherpolicies/answer/11190248',
                ],
                [
                    'label' =>
                        'Ads vs Publisher Content',
                    'url' =>
                        'https://support.google.com/publisherpolicies/answer/11169917',
                ],
            ],
        ]);
    }

    private function auditSite(): array
    {
        $checks = [];

        $add =
            static function (
                string $level,
                string $title,
                string $detail
            ) use (&$checks): void {
                $checks[] = [
                    'level' =>
                        $level,
                    'title' =>
                        $title,
                    'detail' =>
                        $detail,
                ];
            };

        $appUrl =
            trim(
                (string) config(
                    'app.url'
                )
            );

        $appHost =
            mb_strtolower(
                (string) parse_url(
                    $appUrl,
                    PHP_URL_HOST
                )
            );

        if (
            str_starts_with(
                $appUrl,
                'https://'
            )
        ) {
            $add(
                'pass',
                'APP_URL / HTTPS',
                $appUrl
            );
        } else {
            $add(
                'block',
                'APP_URL / HTTPS',
                'APP_URL chưa dùng HTTPS.'
            );
        }

        $activeSites =
            Site::query()
                ->where(
                    'active',
                    true
                )
                ->get();

        if (
            $activeSites->isEmpty()
        ) {
            $add(
                'block',
                'Site identity',
                'Không có site active trong CMS.'
            );
        } else {
            foreach (
                $activeSites as $site
            ) {
                $domain =
                    mb_strtolower(
                        trim(
                            (string) $site->domain
                        )
                    );

                if (
                    $domain !== ''
                    && $appHost !== ''
                    && $domain !== $appHost
                ) {
                    $add(
                        'warn',
                        'Domain',
                        'CMS domain '
                        . $domain
                        . ' khác APP_URL host '
                        . $appHost
                        . '.'
                    );
                } else {
                    $add(
                        'pass',
                        'Domain',
                        $domain
                        ?: $appHost
                    );
                }
            }
        }

        $routeChecks = [
            'home' =>
                'Homepage',
            'articles.show' =>
                'Public article route',
            'about' =>
                'About',
            'contact' =>
                'Contact',
            'privacy' =>
                'Privacy',
            'terms' =>
                'Terms',
            'editorial' =>
                'Editorial Policy',
        ];

        foreach (
            $routeChecks
            as $routeName => $label
        ) {
            if (
                Route::has(
                    $routeName
                )
            ) {
                $add(
                    'pass',
                    $label,
                    'Route tồn tại.'
                );
            } else {
                $add(
                    'block',
                    $label,
                    'Thiếu route '
                    . $routeName
                    . '.'
                );
            }
        }

        $viewChecks = [
            'pages.about' =>
                'About page',
            'pages.contact' =>
                'Contact page',
            'pages.privacy' =>
                'Privacy page',
            'pages.terms' =>
                'Terms page',
            'pages.editorial' =>
                'Editorial Policy page',
        ];

        foreach (
            $viewChecks
            as $viewName => $label
        ) {
            if (
                view()->exists(
                    $viewName
                )
            ) {
                $add(
                    'pass',
                    $label,
                    'Blade view tồn tại.'
                );
            } else {
                $add(
                    'block',
                    $label,
                    'Thiếu view '
                    . $viewName
                    . '.'
                );
            }
        }

        $privacyPath =
            resource_path(
                'views/pages/privacy.blade.php'
            );

        if (
            is_file(
                $privacyPath
            )
        ) {
            $privacy =
                mb_strtolower(
                    (string) file_get_contents(
                        $privacyPath
                    )
                );

            $hasGoogle =
                str_contains(
                    $privacy,
                    'google'
                );

            $hasCookie =
                str_contains(
                    $privacy,
                    'cookie'
                );

            $hasAdvertising =
                str_contains(
                    $privacy,
                    'adsense'
                )
                || str_contains(
                    $privacy,
                    'advertis'
                );

            if (
                $hasGoogle
                && $hasCookie
                && $hasAdvertising
            ) {
                $add(
                    'pass',
                    'Privacy advertising disclosure',
                    'Privacy page có Google, cookies và advertising/AdSense.'
                );
            } else {
                $add(
                    'warn',
                    'Privacy advertising disclosure',
                    'Kiểm tra lại phần Google advertising/cookies trong Privacy page.'
                );
            }
        }

        $adsTxtPath =
            public_path(
                'ads.txt'
            );

        if (
            is_file(
                $adsTxtPath
            )
        ) {
            $adsTxt =
                trim(
                    (string) file_get_contents(
                        $adsTxtPath
                    )
                );

            if (
                preg_match(
                    '/google\.com\s*,\s*pub-\d+\s*,\s*DIRECT\s*,\s*f08c47fec0942fa0/i',
                    $adsTxt
                )
            ) {
                $add(
                    'pass',
                    'ads.txt',
                    'Google DIRECT record được tìm thấy.'
                );
            } else {
                $add(
                    'warn',
                    'ads.txt',
                    'Có ads.txt nhưng chưa nhận diện Google DIRECT record chuẩn.'
                );
            }
        } else {
            $add(
                'warn',
                'ads.txt',
                'Không tìm thấy public/ads.txt.'
            );
        }

        $publicLayout =
            resource_path(
                'views/layouts/app.blade.php'
            );

        if (
            is_file(
                $publicLayout
            )
        ) {
            $layout =
                (string) file_get_contents(
                    $publicLayout
                );

            if (
                str_contains(
                    $layout,
                    'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js'
                )
                && str_contains(
                    $layout,
                    'ca-pub-'
                )
            ) {
                $add(
                    'pass',
                    'AdSense script',
                    'Tìm thấy AdSense script trong public layout.'
                );
            } else {
                $add(
                    'warn',
                    'AdSense script',
                    'Chưa nhận diện được AdSense script trong public layout.'
                );
            }
        }

        return $checks;
    }

    private function auditArticle(
        Article $article,
        $duplicateTitles,
        $duplicateBodies
    ): array {
        $checks = [];

        $add =
            static function (
                string $level,
                string $title,
                string $detail
            ) use (&$checks): void {
                $checks[] = [
                    'level' =>
                        $level,
                    'title' =>
                        $title,
                    'detail' =>
                        $detail,
                ];
            };

        $body =
            (string) $article->body;

        $plain =
            $this->plainText(
                $body
            );

        $words =
            $this->wordCount(
                $plain
            );

        $placeholderTerms = [
            'lorem ipsum',
            'replace this',
            'coming soon',
            'under construction',
            'your publisher cms is ready',
            '[placeholder]',
        ];

        $combined =
            mb_strtolower(
                implode(
                    ' ',
                    [
                        (string) $article->title,
                        (string) $article->excerpt,
                        $plain,
                        (string) $article->seo_title,
                        (string) $article->meta_description,
                    ]
                )
            );

        $foundPlaceholders =
            array_values(
                array_filter(
                    $placeholderTerms,
                    fn ($term) =>
                        str_contains(
                            $combined,
                            $term
                        )
                )
            );

        if (
            trim(
                (string) $article->title
            ) === ''
        ) {
            $add(
                'block',
                'Title',
                'Thiếu title.'
            );
        }

        /*
         * Internal editorial heuristics only.
         * Google does not publish an official minimum word count.
         */
        if (
            $words < 120
        ) {
            $add(
                'block',
                'Publisher content',
                $words
                . ' từ. Bài quá thiếu nội dung để coi là hoàn chỉnh.'
            );
        } elseif (
            $words < 400
        ) {
            $add(
                'warn',
                'Publisher content',
                $words
                . ' từ. Cần xem lại giá trị biên tập; đây không phải minimum word count của Google.'
            );
        } else {
            $add(
                'pass',
                'Publisher content',
                $words
                . ' từ.'
            );
        }

        if ($foundPlaceholders) {
            $add(
                'block',
                'Placeholder',
                'Phát hiện: '
                . implode(
                    ', ',
                    $foundPlaceholders
                )
            );
        }

        $youtubeCount =
            preg_match_all(
                '/youtube(?:-nocookie)?\.com\/embed|youtube\.com\/watch|youtu\.be\//i',
                $body
            );

        if (
            $youtubeCount > 0
            && $words < 300
        ) {
            $add(
                'warn',
                'YouTube / embed',
                $youtubeCount
                . ' YouTube reference/embed nhưng publisher text tương đối ít.'
            );
        } elseif (
            $youtubeCount > 0
        ) {
            $add(
                'pass',
                'YouTube / embed',
                $youtubeCount
                . ' YouTube reference/embed; bài vẫn có publisher text.'
            );
        }

        $imageSources =
            $this->extractImageSources(
                $body
            );

        $externalImages =
            array_values(
                array_filter(
                    $imageSources,
                    fn ($src) =>
                        preg_match(
                            '/^https?:\/\//i',
                            $src
                        )
                )
            );

        $base64Images =
            array_values(
                array_filter(
                    $imageSources,
                    fn ($src) =>
                        str_starts_with(
                            mb_strtolower(
                                $src
                            ),
                            'data:image/'
                        )
                )
            );

        if ($externalImages) {
            $add(
                'warn',
                'External images',
                count(
                    $externalImages
                )
                . ' ảnh external. Kiểm tra quyền sử dụng và hotlink.'
            );
        }

        if ($base64Images) {
            $add(
                'warn',
                'Base64 images',
                count(
                    $base64Images
                )
                . ' ảnh base64. Nên chuyển vào Media Library/storage.'
            );
        }

        if (
            !$article->featured_image
            && !$imageSources
        ) {
            $add(
                'pass',
                'Images',
                'Không có ảnh. Ảnh không bắt buộc.'
            );
        }

        $normalizedTitle =
            $this->normalizeText(
                (string) $article->title
            );

        $duplicateTitle =
            $normalizedTitle !== ''
            && $duplicateTitles->has(
                $normalizedTitle
            );

        if ($duplicateTitle) {
            $ids =
                $duplicateTitles
                    ->get(
                        $normalizedTitle
                    )
                    ->pluck('id')
                    ->implode(', ');

            $add(
                'warn',
                'Duplicate title',
                'Title trùng với article ID: '
                . $ids
                . '.'
            );
        }

        $exactDuplicateBody =
            false;

        $reviewText =
            $this->articleReviewText(
                $article
            );

        if (
            $this->wordCount(
                $reviewText
            ) >= 80
        ) {
            $bodyHash =
                md5(
                    $this->normalizeText(
                        $reviewText
                    )
                );

            if (
                $duplicateBodies->has(
                    $bodyHash
                )
            ) {
                $exactDuplicateBody =
                    true;

                $ids =
                    implode(
                        ', ',
                        $duplicateBodies
                            ->get(
                                $bodyHash
                            )
                    );

                $add(
                    'block',
                    'Exact duplicate body',
                    'Nội dung bài/chapter trùng hoàn toàn với article ID: '
                    . $ids
                    . '.'
                );
            }
        }

        if (
            trim(
                (string) $article->seo_title
            ) === ''
        ) {
            $add(
                'warn',
                'SEO title',
                'Chưa có SEO title.'
            );
        }

        if (
            trim(
                (string) $article->meta_description
            ) === ''
        ) {
            $add(
                'warn',
                'Meta description',
                'Chưa có meta description.'
            );
        }

        if (
            !$article->category_id
        ) {
            $add(
                'warn',
                'Category',
                'Chưa có category.'
            );
        }

        $chapterWarnings =
            0;

        if (
            $article->content_mode === 'chapter'
            && $article->chapters->count()
        ) {
            foreach (
                $article->chapters
                as $chapter
            ) {
                $chapterWords =
                    $this->wordCount(
                        $this->plainText(
                            (string) $chapter->body
                        )
                    );

                if (
                    $chapterWords < 120
                ) {
                    $chapterWarnings++;

                    $add(
                        'warn',
                        'Chapter '
                        . $chapter->chapter_number,
                        $chapterWords
                        . ' từ; chapter tương đối ngắn.'
                    );
                }
            }
        }

        $blockCount =
            collect(
                $checks
            )
                ->where(
                    'level',
                    'block'
                )
                ->count();

        $warningCount =
            collect(
                $checks
            )
                ->where(
                    'level',
                    'warn'
                )
                ->count();

        $status =
            $blockCount > 0
                ? 'BLOCK'
                : (
                    $warningCount > 0
                        ? 'NEED_REVIEW'
                        : 'READY'
                );

        /*
         * Rule-based scan cannot truly judge originality/replicated-content.
         * Only send plausible content-risk cases to Gemini.
         */
        $aiReviewNeeded =
            $blockCount === 0
            && (
                $words < 500
                || $youtubeCount > 0
                || $duplicateTitle
                || $chapterWarnings > 0
                || $warningCount > 0
            );

        return [
            'id' =>
                $article->id,
            'title' =>
                $article->title,
            'url' =>
                route(
                    'articles.show',
                    [
                        'slug' =>
                            $article->slug,
                    ]
                ),
            'category' =>
                $article->category?->name,
            'words' =>
                $words,
            'youtube_count' =>
                $youtubeCount,
            'body_image_count' =>
                count(
                    $imageSources
                ),
            'chapter_count' =>
                $article->chapters->count(),
            'status' =>
                $status,
            'block_count' =>
                $blockCount,
            'warning_count' =>
                $warningCount,
            'ai_review_needed' =>
                $aiReviewNeeded,
            'checks' =>
                $checks,
        ];
    }

    private function articleReviewText(
        Article $article
    ): string {
        $parts = [
            $this->plainText(
                (string) $article->body
            ),
        ];

        foreach (
            $article->chapters
            as $chapter
        ) {
            $parts[] =
                trim(
                    (string) $chapter->title
                );

            $parts[] =
                $this->plainText(
                    (string) $chapter->body
                );
        }

        return trim(
            implode(
                "\n",
                array_filter(
                    $parts
                )
            )
        );
    }

    private function articleReviewHtml(
        Article $article
    ): string {
        $html =
            (string) $article->body;

        foreach (
            $article->chapters
            as $chapter
        ) {
            $html .=
                '<h2>'
                . e(
                    (string) (
                        $chapter->title
                        ?: (
                            'Chapter '
                            . $chapter->chapter_number
                        )
                    )
                )
                . '</h2>';

            $html .=
                (string) $chapter->body;
        }

        return $html;
    }

    private function extractImageSources(
        string $html
    ): array {
        preg_match_all(
            '/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i',
            $html,
            $matches
        );

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        'trim',
                        $matches[1]
                        ?? []
                    )
                )
            )
        );
    }

    private function plainText(
        string $html
    ): string {
        $text =
            html_entity_decode(
                strip_tags(
                    $html
                ),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            )
        );
    }

    private function wordCount(
        string $text
    ): int {
        if (
            trim(
                $text
            ) === ''
        ) {
            return 0;
        }

        preg_match_all(
            '/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u',
            $text,
            $matches
        );

        return count(
            $matches[0]
            ?? []
        );
    }

    private function normalizeText(
        string $text
    ): string {
        return mb_strtolower(
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $text
                )
            )
        );
    }
}
