<?php

namespace App\Http\Controllers;

use App\Models\{
    Article,
    ArticleDailyView
};
use App\Services\SiteResolver;
use Illuminate\Support\Carbon;
use Throwable;

class ArticleController extends Controller
{
    public function show(string $slug)
    {
        $site =
            app(SiteResolver::class)
                ->current();

        $query = Article::query()
            ->with([
                'site',
                'artist',
                'category',
                'author',
            ])
            ->published()
            ->where(
                'slug',
                $slug
            );

        if ($site) {
            $query->where(
                'site_id',
                $site->id
            );
        }

        $article =
            $query->firstOrFail();

        $this->recordView($article);

        $related = Article::query()
            ->published()
            ->where(
                'site_id',
                $article->site_id
            )
            ->whereKeyNot(
                $article->id
            )
            ->when(
                $article->category_id,
                fn ($q) =>
                    $q->where(
                        'category_id',
                        $article->category_id
                    )
            )
            ->latest('published_at')
            ->take(4)
            ->get();

        return view(
            'articles.show',
            compact(
                'article',
                'related'
            )
        );
    }

    private function recordView(
        Article $article
    ): void {
        /*
         * Count one public view per browser session per article
         * every 30 minutes. This stops simple refresh spam while
         * keeping the counter lightweight and privacy-friendly.
         */
        $sessionKey =
            'article_viewed_at_'
            . $article->id;

        $lastViewedAt =
            session()->get($sessionKey);

        if ($lastViewedAt) {
            try {
                $last =
                    Carbon::parse(
                        $lastViewedAt
                    );

                if (
                    $last->diffInMinutes(
                        now()
                    ) < 30
                ) {
                    return;
                }
            } catch (Throwable $e) {
                // Invalid old session value: count normally.
            }
        }

        $article->increment('views');

        /*
         * Daily history powers the analytics dashboard.
         * Tracking begins from the day this upgrade is deployed.
         */
        $daily =
            ArticleDailyView::firstOrCreate(
                [
                    'article_id' =>
                        $article->id,
                    'view_date' =>
                        now()->toDateString(),
                ],
                [
                    'site_id' =>
                        $article->site_id,
                    'views' => 0,
                ]
            );

        $daily->increment('views');

        session()->put(
            $sessionKey,
            now()->toIso8601String()
        );
    }
}
