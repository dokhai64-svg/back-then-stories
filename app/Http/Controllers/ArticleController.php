<?php

namespace App\Http\Controllers;

use App\Models\{
    Article,
    ArticleAlias,
    ArticleChapter,
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

        $article =
            $this->resolveArticleBySlug(
                $slug,
                $site
            );

        $article->load([
            'site',
            'artist',
            'category',
            'author',
            'chapters',
        ]);

        $this->recordView($article);

        if (
            $article->content_mode === 'chapter'
            && $article->skip_intro
            && $article->chapters->count()
        ) {
            $firstChapter =
                $article->chapters->first();

            return redirect()->route(
                'articles.chapter',
                [
                    'slug' =>
                        $article->slug,
                    'chapterNumber' =>
                        $firstChapter
                            ->chapter_number,
                ]
            );
        }

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

    public function chapter(
        string $slug,
        int $chapterNumber
    ) {
        $site =
            app(SiteResolver::class)
                ->current();

        $article =
            $this->resolveArticleBySlug(
                $slug,
                $site
            );

        abort_unless(
            $article->content_mode
            === 'chapter',
            404
        );

        $article->load([
            'site',
            'artist',
            'category',
            'author',
            'chapters',
        ]);

        $chapter =
            $article->chapters
                ->firstWhere(
                    'chapter_number',
                    $chapterNumber
                );

        abort_unless(
            $chapter,
            404
        );

        $chapters =
            $article->chapters
                ->values();

        $position =
            $chapters->search(
                fn ($item) =>
                    (int) $item->id
                    === (int) $chapter->id
            );

        $previousChapter =
            $position !== false
            && $position > 0
                ? $chapters[
                    $position - 1
                ]
                : null;

        $nextChapter =
            $position !== false
            && $position
                < $chapters->count() - 1
                ? $chapters[
                    $position + 1
                ]
                : null;

        $this->recordChapterView(
            $chapter
        );

        $related =
            $this->recommendedArticles(
                $article,
                4
            );

        return view(
            'articles.chapter',
            compact(
                'article',
                'chapter',
                'chapters',
                'previousChapter',
                'nextChapter',
                'related'
            )
        );
    }

    private function resolveArticleBySlug(
        string $slug,
        $site
    ): Article {
        $query =
            Article::query()
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
            $query->first();

        if ($article) {
            return $article;
        }

        $aliasQuery =
            ArticleAlias::query()
                ->where(
                    'slug',
                    $slug
                );

        if ($site) {
            $aliasQuery->where(
                'site_id',
                $site->id
            );
        }

        $alias =
            $aliasQuery->firstOrFail();

        return Article::query()
            ->published()
            ->whereKey(
                $alias->article_id
            )
            ->when(
                $site,
                fn ($q) =>
                    $q->where(
                        'site_id',
                        $site->id
                    )
            )
            ->firstOrFail();
    }

    private function recordChapterView(
        ArticleChapter $chapter
    ): void {
        $sessionKey =
            'chapter_viewed_at_'
            . $chapter->id;

        $lastViewedAt =
            session()->get(
                $sessionKey
            );

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
                // Count normally.
            }
        }

        $chapter->increment(
            'views'
        );

        session()->put(
            $sessionKey,
            now()->toIso8601String()
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
