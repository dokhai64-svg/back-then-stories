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

/*
|--------------------------------------------------------------------------
| AdSense-safe visual separation
|--------------------------------------------------------------------------
|
| These margins are conservative site UX rules, not official Google pixel
| thresholds. Their purpose is to make ads visually distinct from article
| controls, video/player areas, chapter navigation and recommendation links.
|
*/
.policy-ad-zone {
    clear: both;
    width: 100%;
    margin: 52px 0;
    padding: 4px 0;
}

.policy-ad-zone--in-body {
    margin: 48px 0;
}

.policy-ad-zone--after-interactive {
    margin-top: 64px;
}

.policy-ad-zone--before-related {
    margin-bottom: 64px;
}

.article-related-video {
    margin: 42px 0 0;
}

.article-related-video a {
    display: inline-block;
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

    .policy-ad-zone,
    .policy-ad-zone--in-body {
        margin-top: 56px;
        margin-bottom: 56px;
    }

    .policy-ad-zone--after-interactive {
        margin-top: 68px;
    }

    .policy-ad-zone--before-related {
        margin-bottom: 68px;
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

    @php
        /*
        |--------------------------------------------------------------------------
        | Conservative automatic ad placement
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | These word/paragraph thresholds are INTERNAL SITE HEURISTICS.
        | They are not Google AdSense rules.
        |
        | Google policy focuses on:
        | - ads/promotional material not exceeding publisher-content,
        | - avoiding deceptive placements,
        | - avoiding accidental-click layouts near navigation/video/buttons.
        |
        | Site strategy:
        | - Short normal article: 1 bottom slot.
        | - Medium normal article: 1 in-body + 1 bottom slot.
        | - Long normal article: 2 in-body + 1 bottom slot.
        | - Chapter landing page: keep ads out of the immediate chapter-start
        |   navigation area; use a separated bottom slot only.
        */

        $bodyHtml =
            (string) (
                $article->body
                ?? ''
            );

        $plainBody =
            preg_replace(
                '/\s+/u',
                ' ',
                html_entity_decode(
                    strip_tags($bodyHtml),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )
            );

        preg_match_all(
            '/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u',
            (string) $plainBody,
            $wordMatches
        );

        $bodyWordCount =
            count(
                $wordMatches[0]
                ?? []
            );

        $bodyChunks =
            preg_split(
                '/(?<=<\/p>)/i',
                $bodyHtml,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

        if (
            !is_array($bodyChunks)
            || !$bodyChunks
        ) {
            $bodyChunks = [
                $bodyHtml,
            ];
        }

        $bodyChunkCount =
            count(
                $bodyChunks
            );

        $isChapterLanding =
            $article->content_mode === 'chapter'
            && $article->chapters->count();

        $chunkHasInteractiveMedia =
            static function (
                string $html
            ): bool {
                return (bool) preg_match(
                    '/<(iframe|video|audio|button|select|input|form)\b|youtube\.com|youtu\.be|vimeo\.com/i',
                    $html
                );
            };

        $pickSafeBoundary =
            static function (
                array $chunks,
                int $target,
                array $used = []
            ) use (
                $chunkHasInteractiveMedia
            ): ?int {
                $count = count($chunks);

                if ($count < 5) {
                    return null;
                }

                $min = 2;
                $max = $count - 2;

                $target =
                    max(
                        $min,
                        min(
                            $max,
                            $target
                        )
                    );

                $offsets = [
                    0,
                    1,
                    -1,
                    2,
                    -2,
                    3,
                    -3,
                    4,
                    -4,
                ];

                foreach ($offsets as $offset) {
                    $boundary =
                        $target
                        + $offset;

                    if (
                        $boundary < $min
                        || $boundary > $max
                    ) {
                        continue;
                    }

                    $tooCloseToUsed = false;

                    foreach ($used as $alreadyUsed) {
                        if (
                            abs(
                                $boundary
                                - $alreadyUsed
                            ) < 2
                        ) {
                            $tooCloseToUsed = true;
                            break;
                        }
                    }

                    if ($tooCloseToUsed) {
                        continue;
                    }

                    $before =
                        (string) (
                            $chunks[$boundary - 1]
                            ?? ''
                        );

                    $after =
                        (string) (
                            $chunks[$boundary]
                            ?? ''
                        );

                    if (
                        $chunkHasInteractiveMedia($before)
                        || $chunkHasInteractiveMedia($after)
                    ) {
                        continue;
                    }

                    return $boundary;
                }

                return null;
            };

        $inBodyAdSlots = [];

        if (
            !$isChapterLanding
            && $bodyChunkCount >= 6
            && $bodyWordCount >= 450
        ) {
            $firstTarget =
                (int) round(
                    $bodyChunkCount
                    * (
                        $bodyWordCount >= 900
                            ? 0.30
                            : 0.45
                    )
                );

            $firstBoundary =
                $pickSafeBoundary(
                    $bodyChunks,
                    $firstTarget
                );

            if ($firstBoundary) {
                $inBodyAdSlots[$firstBoundary] = 'banner_top';
            }

            if (
                $bodyWordCount >= 900
                && $bodyChunkCount >= 9
            ) {
                $secondTarget =
                    (int) round(
                        $bodyChunkCount
                        * 0.65
                    );

                $secondBoundary =
                    $pickSafeBoundary(
                        $bodyChunks,
                        $secondTarget,
                        array_keys($inBodyAdSlots)
                    );

                if ($secondBoundary) {
                    $inBodyAdSlots[$secondBoundary] = 'banner_mid';
                }
            }
        }

        $showBottomAd =
            $bodyWordCount >= 220
            || $isChapterLanding;
    @endphp

    <div class="body">
        @foreach($bodyChunks as $chunkIndex => $bodyChunk)

            {!! $bodyChunk !!}

            @php
                $boundary =
                    $chunkIndex + 1;

                $slotKey =
                    $inBodyAdSlots[$boundary]
                    ?? null;
            @endphp

            @if($slotKey)
                <div
                    class="policy-ad-zone policy-ad-zone--in-body"
                    data-ad-policy-zone="in-body"
                    aria-label="Advertisement"
                >
                    @include(
                        'partials.ad',
                        ['key' => $slotKey]
                    )
                </div>
            @endif

        @endforeach
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

    @if($article->youtube_url)
        <p class="article-related-video">
            <a
                href="{{ $article->youtube_url }}"
                target="_blank"
                rel="noopener nofollow"
            >
                Watch related video
            </a>
        </p>
    @endif

    @if($showBottomAd)
        <div
            class="policy-ad-zone policy-ad-zone--after-interactive policy-ad-zone--before-related"
            data-ad-policy-zone="article-bottom"
            aria-label="Advertisement"
        >
            @include(
                'partials.ad',
                ['key' => 'banner_bot']
            )
        </div>
    @endif

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
