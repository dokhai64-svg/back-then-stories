<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\SiteResolver;

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

        /*
         * Simple page-view counter.
         * It counts a successful public article request.
         */
        $article->increment('views');

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
}
