<?php

namespace App\Http\Controllers;

use App\Models\{
    Article,
    ArticleDailyView
};
use App\Services\SiteResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

        /*
         * Build a useful recommendation set without repeating
         * the article currently being read.
         *
         * Priority:
         * 1) Same category
         * 2) Same artist
         * 3) Popular / recent articles from the same site
         */
        $related =
            $this->recommendedArticles(
                $article,
                8
            );

        return view(
            'articles.show',
            compact(
                'article',
                'related'
            )
        );
    }

    private function recommendedArticles(
        Article $article,
        int $limit = 8
    ): Collection {
        $items = collect();

        if ($article->category_id) {
            $sameCategory =
                Article::query()
                    ->with([
                        'category',
                        'artist',
                    ])
                    ->published()
                    ->where(
                        'site_id',
                        $article->site_id
                    )
                    ->where(
                        'category_id',
                        $article->category_id
                    )
                    ->whereKeyNot(
                        $article->id
                    )
                    ->orderByDesc('views')
                    ->orderByDesc('published_at')
                    ->take($limit)
                    ->get();

            $items =
                $items->concat(
                    $sameCategory
                );
        }

        if (
            $items->count() < $limit &&
            $article->artist_id
        ) {
            $needed =
                $limit - $items->count();

            $sameArtist =
                Article::query()
                    ->with([
                        'category',
                        'artist',
                    ])
                    ->published()
                    ->where(
                        'site_id',
                        $article->site_id
                    )
                    ->where(
                        'artist_id',
                        $article->artist_id
                    )
                    ->whereKeyNot(
                        $article->id
                    )
                    ->whereNotIn(
                        'id',
                        $items->pluck('id')
                    )
                    ->orderByDesc('views')
                    ->orderByDesc('published_at')
                    ->take($needed)
                    ->get();

            $items =
                $items->concat(
                    $sameArtist
                );
        }

        if ($items->count() < $limit) {
            $needed =
                $limit - $items->count();

            $fallback =
                Article::query()
                    ->with([
                        'category',
                        'artist',
                    ])
                    ->published()
                    ->where(
                        'site_id',
                        $article->site_id
                    )
                    ->whereKeyNot(
                        $article->id
                    )
                    ->whereNotIn(
                        'id',
                        $items->pluck('id')
                    )
                    ->orderByDesc('views')
                    ->orderByDesc('published_at')
                    ->take($needed)
                    ->get();

            $items =
                $items->concat(
                    $fallback
                );
        }

        return $items
            ->unique('id')
            ->take($limit)
            ->values();
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
