@extends('layouts.app')

@section('title', $article->seo_title ?: $article->title)
@section('meta', $article->meta_description ?: $article->excerpt)
@section('canonical', route('articles.show', $article->slug))
@section('og_type', 'article')

@if($article->featured_image)
    @section('og_image', asset('storage/' . $article->featured_image))
@endif

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article->title,
    'description' => $article->meta_description ?: $article->excerpt,
    'datePublished' => optional($article->published_at)->toIso8601String(),
    'dateModified' => optional($article->updated_at)->toIso8601String(),
    'author' => [
        '@type' => 'Person',
        'name' => $article->author?->name ?? 'Editorial Team',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $currentSite?->name ?? 'Back Then Stories',
    ],
    'image' => $article->featured_image
        ? [asset('storage/' . $article->featured_image)]
        : [],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<style>
.article-recommendations {
    margin-top: 46px;
    padding-top: 28px;
    border-top: 1px solid #d9d9d9;
}

.article-recommendations__title {
    margin: 0 0 18px;
    font-size: 22px;
    line-height: 1.2;
}

.article-recommendations__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
}

.article-recommendation-card {
    overflow: hidden;
    border: 1px solid #dedede;
    border-radius: 10px;
    background: #fff;
}

.article-recommendation-card a {
    color: inherit;
    text-decoration: none;
}

.article-recommendation-card__image,
.article-recommendation-card__placeholder {
    display: block;
    width: 100%;
    aspect-ratio: 16 / 10;
    object-fit: cover;
    background: #ececec;
}

.article-recommendation-card__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    color: #777;
    font-size: 11px;
    text-align: center;
}

.article-recommendation-card__body {
    padding: 11px 12px 13px;
}

.article-recommendation-card__meta {
    margin-bottom: 6px;
    color: #8a3527;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.article-recommendation-card__title {
    margin: 0;
    font-size: 15px;
    line-height: 1.25;
}

.article-recommendation-card__excerpt {
    margin: 7px 0 0;
    color: #666;
    font-size: 12px;
    line-height: 1.45;
}

.chapter-entry{
    margin:34px 0 8px;
    padding:22px;
    border:1px solid #dedede;
    border-radius:12px;
    background:#fafafa;
}
.chapter-entry h2{
    margin:0 0 10px;
    font-size:24px;
}
.chapter-start{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:42px;
    padding:0 18px;
    border-radius:8px;
    background:#8b3326;
    color:#fff !important;
    text-decoration:none !important;
    font-weight:700;
}
.chapter-list{
    margin:16px 0 0;
    padding-left:22px;
}
.chapter-list li{
    margin:7px 0;
}

@media (max-width: 900px) {
    .article-recommendations__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .article-recommendations__grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')

<article class="article">

    <div class="kicker">
        {{ $article->category?->name ?? 'Story' }}
    </div>

    <h1 style="font-size:44px;line-height:1.08">
        {{ $article->title }}
    </h1>

    @if($article->excerpt)
        <p class="deck">
            {{ $article->excerpt }}
        </p>
    @endif

    <div class="meta">
        {{ optional($article->published_at)->format('F j, Y') }}

        @if($article->author)
            · By {{ $article->author->name }}
        @endif
    </div>

    @if($article->featured_image)
        <img
            src="{{ asset('storage/' . $article->featured_image) }}"
            alt="{{ $article->title }}"
            style="width:100%;margin:24px 0;border-radius:10px"
        >
    @endif

    @include('partials.ad', ['key' => 'banner_top'])

    <div class="body">
        {!! $article->body !!}
    </div>

    @if(
        $article->content_mode === 'chapter'
        && $article->chapters->count()
    )
        @php
            $firstChapter =
                $article->chapters->first();
        @endphp

        <section class="chapter-entry">
            <h2>
                Continue this story
            </h2>

            <p>
                This story has
                {{ $article->chapters->count() }}
                chapter(s).
            </p>

            <a
                class="chapter-start"
                href="{{
                    route(
                        'articles.chapter',
                        [
                            'slug' =>
                                $article->slug,
                            'chapterNumber' =>
                                $firstChapter->chapter_number,
                        ]
                    )
                }}"
            >
                Start Chapter
                {{ $firstChapter->chapter_number }}
                →
            </a>

            <ol class="chapter-list">
                @foreach($article->chapters as $chapter)
                    <li>
                        <a
                            href="{{
                                route(
                                    'articles.chapter',
                                    [
                                        'slug' =>
                                            $article->slug,
                                        'chapterNumber' =>
                                            $chapter->chapter_number,
                                    ]
                                )
                            }}"
                        >
                            Chapter
                            {{ $chapter->chapter_number }}
                            @if($chapter->title)
                                —
                                {{ $chapter->title }}
                            @endif
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @include('partials.ad', ['key' => 'banner_mid'])

    @if($article->youtube_url)
        <p>
            <a
                href="{{ $article->youtube_url }}"
                target="_blank"
                rel="noopener nofollow"
            >
                Watch related video
            </a>
        </p>
    @endif

    @include('partials.ad', ['key' => 'banner_bot'])

    @if($related->count())
        <section
            class="article-recommendations"
            aria-labelledby="recommended-stories-title"
        >
            <h2
                id="recommended-stories-title"
                class="article-recommendations__title"
            >
                More Stories You May Like
            </h2>

            <div class="article-recommendations__grid">

                @foreach($related as $r)
                    <article class="article-recommendation-card">

                        <a
                            href="{{ route('articles.show', $r->slug) }}"
                            aria-label="{{ $r->title }}"
                        >
                            @if($r->featured_image)
                                <img
                                    class="article-recommendation-card__image"
                                    src="{{ asset('storage/' . $r->featured_image) }}"
                                    alt="{{ $r->title }}"
                                    loading="lazy"
                                >
                            @else
                                <div class="article-recommendation-card__placeholder">
                                    Back Then Stories
                                </div>
                            @endif
                        </a>

                        <div class="article-recommendation-card__body">

                            <div class="article-recommendation-card__meta">
                                {{ $r->category?->name ?? 'Story' }}

                                @if($r->published_at)
                                    · {{ $r->published_at->format('M j, Y') }}
                                @endif
                            </div>

                            <h3 class="article-recommendation-card__title">
                                <a href="{{ route('articles.show', $r->slug) }}">
                                    {{ $r->title }}
                                </a>
                            </h3>

                            @if($r->excerpt)
                                <p class="article-recommendation-card__excerpt">
                                    {{ \Illuminate\Support\Str::limit(
                                        strip_tags($r->excerpt),
                                        105
                                    ) }}
                                </p>
                            @endif

                        </div>

                    </article>
                @endforeach

            </div>
        </section>
    @endif

</article>

@endsection
