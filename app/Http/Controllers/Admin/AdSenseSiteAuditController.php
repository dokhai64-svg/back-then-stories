<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AdSenseSiteAuditController extends Controller
{
    public function index()
    {
        return view('admin.adsense-audit.index');
    }

    public function run(Request $request): JsonResponse
    {
        $articles = Article::query()
            ->where('status', 'published')
            ->with(['site','category','chapters'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        $duplicateTitles = $articles
            ->groupBy(fn ($a) => $this->normalizeText((string) $a->title))
            ->filter(fn ($g, $k) => $k !== '' && $g->count() > 1);

        $bodyMap = [];
        foreach ($articles as $article) {
            $plain = $this->plainText((string) $article->body);
            if ($this->wordCount($plain) < 80) continue;
            $bodyMap[md5($this->normalizeText($plain))][] = (int) $article->id;
        }

        $duplicateBodies = collect($bodyMap)
            ->filter(fn ($ids) => count($ids) > 1);

        $reports = [];
        $aiCandidates = [];

        foreach ($articles as $article) {
            $report = $this->auditArticle($article, $duplicateTitles, $duplicateBodies);
            $reports[] = $report;

            if ($report['ai_review_needed']) {
                $aiCandidates[] = [
                    'article_id' => $article->id,
                    'title' => $article->title,
                    'body' => (string) $article->body,
                    'youtube_count' => $report['youtube_count'],
                    'image_count' => $report['body_image_count'],
                    'rule_status' => $report['status'],
                    'rule_reasons' => $report['ai_reasons'],
                ];
            }
        }

        $siteChecks = $this->auditSite();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'published_count' => $articles->count(),
            'site_checks' => $siteChecks,
            'site_blocks' => collect($siteChecks)->where('level','block')->count(),
            'site_warnings' => collect($siteChecks)->where('level','warn')->count(),
            'articles' => $reports,
            'ai_candidates' => $aiCandidates,
            'ai_candidate_count' => count($aiCandidates),
            'notes' => [
                'Rule-based scan chạy trên toàn bộ Published Articles.',
                'Gemini chỉ quét những bài có tín hiệu rủi ro nội dung.',
                'AI không thể chứng minh quyền sử dụng media hoặc xác định chắc chắn bài copy từ internet khi không có source.',
                'Word-count chỉ là heuristic nội bộ, không phải luật Google.',
            ],
        ]);
    }

    private function auditSite(): array
    {
        $checks = [];
        $add = function ($level,$title,$detail) use (&$checks) {
            $checks[] = compact('level','title','detail');
        };

        $appUrl = trim((string) config('app.url'));
        $host = mb_strtolower((string) parse_url($appUrl, PHP_URL_HOST));

        $add(
            str_starts_with($appUrl,'https://') ? 'pass' : 'block',
            'APP_URL / HTTPS',
            $appUrl ?: 'APP_URL trống.'
        );

        $sites = Site::query()->where('active',true)->get();
        if ($sites->isEmpty()) {
            $add('block','Site identity','Không có site active.');
        } else {
            foreach ($sites as $site) {
                $domain = mb_strtolower(trim((string) $site->domain));
                $add(
                    $domain && $host && $domain !== $host ? 'warn' : 'pass',
                    'Domain',
                    $domain ?: $host
                );
            }
        }

        foreach ([
            'home'=>'Homepage',
            'about'=>'About',
            'contact'=>'Contact',
            'privacy'=>'Privacy',
            'terms'=>'Terms',
            'editorial'=>'Editorial Policy',
            'articles.show'=>'Public article route',
        ] as $name => $label) {
            $add(Route::has($name) ? 'pass' : 'block', $label, Route::has($name) ? 'Route tồn tại.' : "Thiếu route {$name}.");
        }

        foreach ([
            'pages.about'=>'About page',
            'pages.contact'=>'Contact page',
            'pages.privacy'=>'Privacy page',
            'pages.terms'=>'Terms page',
            'pages.editorial'=>'Editorial Policy page',
        ] as $name => $label) {
            $add(view()->exists($name) ? 'pass' : 'block', $label, view()->exists($name) ? 'Blade view tồn tại.' : "Thiếu view {$name}.");
        }

        $adsPath = public_path('ads.txt');
        if (is_file($adsPath)) {
            $ads = trim((string) file_get_contents($adsPath));
            $ok = preg_match('/google\.com\s*,\s*pub-\d+\s*,\s*DIRECT\s*,\s*f08c47fec0942fa0/i',$ads);
            $add($ok ? 'pass' : 'warn','ads.txt',$ok ? 'Google DIRECT record được tìm thấy.' : 'Có ads.txt nhưng chưa nhận diện record Google chuẩn.');
        } else {
            $add('warn','ads.txt','Không tìm thấy public/ads.txt.');
        }

        $layout = resource_path('views/layouts/app.blade.php');
        if (is_file($layout)) {
            $html = (string) file_get_contents($layout);
            $ok = str_contains($html,'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js') && str_contains($html,'ca-pub-');
            $add($ok ? 'pass' : 'warn','AdSense verification script',$ok ? 'Tìm thấy AdSense script.' : 'Chưa nhận diện AdSense script.');
        }

        return $checks;
    }

    private function auditArticle(Article $article, $duplicateTitles, $duplicateBodies): array
    {
        $checks = [];
        $aiReasons = [];
        $add = function ($level,$title,$detail) use (&$checks) {
            $checks[] = compact('level','title','detail');
        };

        $body = (string) $article->body;
        $plain = $this->plainText($body);
        $words = $this->wordCount($plain);

        if (trim((string) $article->title) === '') {
            $add('block','Title','Thiếu title.');
        }

        if ($words < 120) {
            $add('block','Publisher content',"{$words} từ. Nội dung quá ít.");
            $aiReasons[] = 'publisher content quá ít';
        } elseif ($words < 400) {
            $add('warn','Publisher content',"{$words} từ. Đây là heuristic nội bộ.");
            $aiReasons[] = 'publisher content tương đối ngắn';
        } else {
            $add('pass','Publisher content',"{$words} từ.");
        }

        $combined = mb_strtolower(implode(' ',[
            (string) $article->title,
            (string) $article->excerpt,
            $plain,
            (string) $article->seo_title,
            (string) $article->meta_description,
        ]));

        foreach (['lorem ipsum','replace this','coming soon','under construction','[placeholder]'] as $term) {
            if (str_contains($combined,$term)) {
                $add('block','Placeholder',"Phát hiện: {$term}");
            }
        }

        $youtubeCount = preg_match_all('/youtube(?:-nocookie)?\.com\/embed|youtube\.com\/watch|youtu\.be\//i',$body);
        if ($youtubeCount > 0) {
            $add($words < 500 ? 'warn' : 'pass','YouTube / embed',"{$youtubeCount} YouTube reference/embed.");
            $aiReasons[] = 'có YouTube/embed cần kiểm tra value-added';
        }

        $imageSources = $this->extractImageSources($body);
        $external = array_values(array_filter($imageSources, fn ($src) => preg_match('/^https?:\/\//i',$src)));
        $base64 = array_values(array_filter($imageSources, fn ($src) => str_starts_with(mb_strtolower($src),'data:image/')));

        if ($external) $add('warn','External images',count($external).' ảnh external; cần kiểm tra quyền/hotlink.');
        if ($base64) $add('warn','Base64 images',count($base64).' ảnh base64; nên đưa vào Media Library.');
        if (!$article->featured_image && !$imageSources) $add('pass','Images','Không có ảnh; ảnh không bắt buộc.');

        $titleKey = $this->normalizeText((string) $article->title);
        if ($titleKey !== '' && $duplicateTitles->has($titleKey)) {
            $ids = $duplicateTitles->get($titleKey)->pluck('id')->implode(', ');
            $add('warn','Duplicate title',"Trùng với article ID: {$ids}.");
            $aiReasons[] = 'title trùng với bài Published khác';
        }

        if ($words >= 80) {
            $hash = md5($this->normalizeText($plain));
            if ($duplicateBodies->has($hash)) {
                $ids = implode(', ',$duplicateBodies->get($hash));
                $add('block','Exact duplicate body',"Body trùng hoàn toàn với article ID: {$ids}.");
                $aiReasons[] = 'body trùng hoàn toàn với bài Published khác';
            }
        }

        if (trim((string) $article->seo_title) === '') $add('warn','SEO title','Chưa có SEO title.');
        if (trim((string) $article->meta_description) === '') $add('warn','Meta description','Chưa có meta description.');
        if (!$article->category_id) $add('warn','Category','Chưa có category.');

        if ($article->content_mode === 'chapter' && $article->chapters->count()) {
            if ($words < 120) {
                $add('warn','Chapter landing','Trang mở đầu chapter có ít publisher text.');
                $aiReasons[] = 'chapter landing có ít publisher text';
            }
            foreach ($article->chapters as $chapter) {
                $cw = $this->wordCount($this->plainText((string) $chapter->body));
                if ($cw < 120) $add('warn',"Chapter {$chapter->chapter_number}","{$cw} từ; chapter tương đối ngắn.");
            }
        }

        $blocks = collect($checks)->where('level','block')->count();
        $warnings = collect($checks)->where('level','warn')->count();
        $status = $blocks > 0 ? 'BLOCK' : ($warnings > 0 ? 'NEED_REVIEW' : 'READY');

        return [
            'id'=>$article->id,
            'title'=>$article->title,
            'url'=>route('articles.show',['slug'=>$article->slug]),
            'category'=>$article->category?->name,
            'words'=>$words,
            'youtube_count'=>$youtubeCount,
            'body_image_count'=>count($imageSources),
            'status'=>$status,
            'block_count'=>$blocks,
            'warning_count'=>$warnings,
            'checks'=>$checks,
            'ai_review_needed'=>count(array_unique($aiReasons)) > 0,
            'ai_reasons'=>array_values(array_unique($aiReasons)),
        ];
    }

    private function extractImageSources(string $html): array
    {
        preg_match_all('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i',$html,$m);
        return array_values(array_unique(array_filter(array_map('trim',$m[1] ?? []))));
    }

    private function plainText(string $html): string
    {
        return trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($html),ENT_QUOTES | ENT_HTML5,'UTF-8')));
    }

    private function wordCount(string $text): int
    {
        if (trim($text) === '') return 0;
        preg_match_all('/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u',$text,$m);
        return count($m[0] ?? []);
    }

    private function normalizeText(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u',' ',$text)));
    }
}
