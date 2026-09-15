<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{
    AdSlot,
    AdSlotAudit,
    Article,
    ArticleDailyView,
    Site
};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request
    ) {
        $sites =
            Site::orderBy('name')
                ->get();

        $siteId =
            $request->integer('site_id')
            ?: $sites->first()?->id;

        $site =
            $siteId
                ? $sites->firstWhere(
                    'id',
                    $siteId
                )
                : null;

        [$from, $to] =
            $this->dateRange($request);

        $articleQuery =
            Article::query()
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                );

        $publishedQuery =
            Article::query()
                ->where(
                    'status',
                    'published'
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                );

        $publishedCount =
            (clone $publishedQuery)
                ->count();

        $totalViews =
            (clone $publishedQuery)
                ->sum('views');

        $averageViews =
            $publishedCount > 0
                ? (int) round(
                    $totalViews
                    / $publishedCount
                )
                : 0;

        $totalAds =
            AdSlot::query()
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->count();

        $activeAds =
            AdSlot::query()
                ->where(
                    'enabled',
                    true
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->count();

        $adChanges7d =
            AdSlotAudit::query()
                ->where(
                    'created_at',
                    '>=',
                    now()->subDays(6)
                        ->startOfDay()
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->count();

        $changedAds7d =
            AdSlotAudit::query()
                ->where(
                    'created_at',
                    '>=',
                    now()->subDays(6)
                        ->startOfDay()
                )
                ->whereNotNull(
                    'ad_slot_id'
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->distinct(
                    'ad_slot_id'
                )
                ->count(
                    'ad_slot_id'
                );

        $summary = [
            'total_ads' =>
                $totalAds,
            'active_ads' =>
                $activeAds,
            'ad_changes_7d' =>
                $adChanges7d,
            'changed_ads_7d' =>
                $changedAds7d,
            'published_posts' =>
                $publishedCount,
            'total_post_views' =>
                (int) $totalViews,
            'average_views' =>
                $averageViews,
            'all_articles' =>
                (clone $articleQuery)
                    ->count(),
        ];

        $dailyViews =
            $this->dailySeries(
                $siteId,
                $from,
                $to,
                ArticleDailyView::class
            );

        $topViewed =
            $this->topViewedArticles(
                $siteId,
                $from,
                $to,
                6
            );

        $periodDays =
            max(
                1,
                $from->diffInDays($to)
                + 1
            );

        $previousTo =
            $from->copy()
                ->subDay();

        $previousFrom =
            $previousTo->copy()
                ->subDays(
                    $periodDays - 1
                );

        $trending =
            $this->trendingArticles(
                $siteId,
                $from,
                $to,
                $previousFrom,
                $previousTo,
                10
            );

        $adChangeSeries =
            $this->adAuditSeries(
                $siteId,
                now()->subDays(13)
                    ->startOfDay(),
                now()->endOfDay()
            );

        $mostEditedAds =
            DB::table('ad_slot_audits')
                ->leftJoin(
                    'ad_slots',
                    'ad_slots.id',
                    '=',
                    'ad_slot_audits.ad_slot_id'
                )
                ->select(
                    'ad_slot_audits.ad_slot_id',
                    'ad_slots.label'
                )
                ->selectRaw(
                    'COUNT(*) AS edit_count'
                )
                ->where(
                    'ad_slot_audits.created_at',
                    '>=',
                    now()->subDays(29)
                        ->startOfDay()
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'ad_slot_audits.site_id',
                            $siteId
                        )
                )
                ->groupBy(
                    'ad_slot_audits.ad_slot_id',
                    'ad_slots.label'
                )
                ->orderByDesc(
                    'edit_count'
                )
                ->limit(6)
                ->get();

        $recentAdChanges =
            AdSlotAudit::query()
                ->with([
                    'adSlot',
                    'user',
                ])
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->latest()
                ->take(8)
                ->get();

        $activity30 = [
            'created' =>
                Article::query()
                    ->when(
                        $siteId,
                        fn ($q) =>
                            $q->where(
                                'site_id',
                                $siteId
                            )
                    )
                    ->where(
                        'created_at',
                        '>=',
                        now()->subDays(29)
                            ->startOfDay()
                    )
                    ->count(),

            'published' =>
                Article::query()
                    ->where(
                        'status',
                        'published'
                    )
                    ->when(
                        $siteId,
                        fn ($q) =>
                            $q->where(
                                'site_id',
                                $siteId
                            )
                    )
                    ->where(
                        'published_at',
                        '>=',
                        now()->subDays(29)
                            ->startOfDay()
                    )
                    ->count(),

            'updated' =>
                Article::query()
                    ->when(
                        $siteId,
                        fn ($q) =>
                            $q->where(
                                'site_id',
                                $siteId
                            )
                    )
                    ->where(
                        'updated_at',
                        '>=',
                        now()->subDays(29)
                            ->startOfDay()
                    )
                    ->count(),
        ];

        return view(
            'admin.dashboard',
            compact(
                'sites',
                'siteId',
                'site',
                'from',
                'to',
                'summary',
                'dailyViews',
                'topViewed',
                'trending',
                'adChangeSeries',
                'mostEditedAds',
                'recentAdChanges',
                'activity30'
            )
        );
    }

    public function exportUrls(
        Request $request
    ) {
        $siteId =
            $request->integer(
                'site_id'
            );

        $articles =
            Article::query()
                ->where(
                    'status',
                    'published'
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->orderByDesc(
                    'views'
                )
                ->get([
                    'slug',
                ]);

        $contents =
            $articles
                ->map(
                    fn ($article) =>
                        route(
                            'articles.show',
                            [
                                'slug' =>
                                    $article->slug,
                            ]
                        )
                )
                ->implode("\n");

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'text/plain; charset=UTF-8',
                'Content-Disposition' =>
                    'attachment; filename="published-article-urls.txt"',
            ]
        );
    }

    private function dateRange(
        Request $request
    ): array {
        $defaultTo =
            now()->startOfDay();

        $defaultFrom =
            $defaultTo->copy()
                ->subDays(13);

        try {
            $from =
                $request->filled('from')
                    ? Carbon::parse(
                        $request->get('from')
                    )->startOfDay()
                    : $defaultFrom;
        } catch (\Throwable $e) {
            $from =
                $defaultFrom;
        }

        try {
            $to =
                $request->filled('to')
                    ? Carbon::parse(
                        $request->get('to')
                    )->startOfDay()
                    : $defaultTo;
        } catch (\Throwable $e) {
            $to =
                $defaultTo;
        }

        if ($from->greaterThan($to)) {
            [$from, $to] =
                [$to, $from];
        }

        if (
            $from->diffInDays($to)
            > 89
        ) {
            $from =
                $to->copy()
                    ->subDays(89);
        }

        return [
            $from,
            $to,
        ];
    }

    private function dailySeries(
        ?int $siteId,
        Carbon $from,
        Carbon $to,
        string $model
    ): array {
        $rows =
            $model::query()
                ->select('view_date')
                ->selectRaw(
                    'SUM(views) AS total'
                )
                ->whereBetween(
                    'view_date',
                    [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->groupBy(
                    'view_date'
                )
                ->orderBy(
                    'view_date'
                )
                ->get()
                ->keyBy(
                    fn ($row) =>
                        Carbon::parse(
                            $row->view_date
                        )->toDateString()
                );

        $series = [];

        $cursor =
            $from->copy();

        while (
            $cursor->lte($to)
        ) {
            $key =
                $cursor->toDateString();

            $series[] = [
                'date' =>
                    $key,
                'label' =>
                    mb_strtoupper(
                        $cursor->format('M d')
                    ),
                'value' =>
                    (int) (
                        $rows[$key]->total
                        ?? 0
                    ),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    private function topViewedArticles(
        ?int $siteId,
        Carbon $from,
        Carbon $to,
        int $limit
    ) {
        $rangeSub =
            DB::table(
                'article_daily_views'
            )
                ->selectRaw(
                    'COALESCE(SUM(views),0)'
                )
                ->whereColumn(
                    'article_id',
                    'articles.id'
                )
                ->whereBetween(
                    'view_date',
                    [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]
                );

        return Article::query()
            ->where(
                'status',
                'published'
            )
            ->when(
                $siteId,
                fn ($q) =>
                    $q->where(
                        'site_id',
                        $siteId
                    )
            )
            ->select(
                'articles.*'
            )
            ->selectSub(
                $rangeSub,
                'range_views'
            )
            ->orderByDesc(
                'views'
            )
            ->take($limit)
            ->get();
    }

    private function trendingArticles(
        ?int $siteId,
        Carbon $from,
        Carbon $to,
        Carbon $previousFrom,
        Carbon $previousTo,
        int $limit
    ) {
        $rangeSub =
            DB::table(
                'article_daily_views'
            )
                ->selectRaw(
                    'COALESCE(SUM(views),0)'
                )
                ->whereColumn(
                    'article_id',
                    'articles.id'
                )
                ->whereBetween(
                    'view_date',
                    [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]
                );

        $previousSub =
            DB::table(
                'article_daily_views'
            )
                ->selectRaw(
                    'COALESCE(SUM(views),0)'
                )
                ->whereColumn(
                    'article_id',
                    'articles.id'
                )
                ->whereBetween(
                    'view_date',
                    [
                        $previousFrom
                            ->toDateString(),
                        $previousTo
                            ->toDateString(),
                    ]
                );

        return Article::query()
            ->where(
                'status',
                'published'
            )
            ->when(
                $siteId,
                fn ($q) =>
                    $q->where(
                        'site_id',
                        $siteId
                    )
            )
            ->select(
                'articles.*'
            )
            ->selectSub(
                $rangeSub,
                'range_views'
            )
            ->selectSub(
                $previousSub,
                'previous_views'
            )
            ->orderByDesc(
                'range_views'
            )
            ->orderByDesc(
                'views'
            )
            ->take($limit)
            ->get();
    }

    private function adAuditSeries(
        ?int $siteId,
        Carbon $from,
        Carbon $to
    ): array {
        $rows =
            AdSlotAudit::query()
                ->selectRaw(
                    'DATE(created_at) AS audit_date, COUNT(*) AS total'
                )
                ->whereBetween(
                    'created_at',
                    [
                        $from,
                        $to,
                    ]
                )
                ->when(
                    $siteId,
                    fn ($q) =>
                        $q->where(
                            'site_id',
                            $siteId
                        )
                )
                ->groupBy(
                    DB::raw(
                        'DATE(created_at)'
                    )
                )
                ->orderBy(
                    DB::raw(
                        'DATE(created_at)'
                    )
                )
                ->get()
                ->keyBy(
                    fn ($row) =>
                        Carbon::parse(
                            $row->audit_date
                        )->toDateString()
                );

        $series = [];
        $cursor =
            $from->copy()
                ->startOfDay();

        $end =
            $to->copy()
                ->startOfDay();

        while ($cursor->lte($end)) {
            $key =
                $cursor->toDateString();

            $series[] = [
                'date' => $key,
                'label' =>
                    mb_strtoupper(
                        $cursor->format('M d')
                    ),
                'value' =>
                    (int) (
                        $rows[$key]->total
                        ?? 0
                    ),
            ];

            $cursor->addDay();
        }

        return $series;
    }
}
