@extends('layouts.app')

@section(
    'title',
    (
        $chapter->title
            ? $chapter->title . ' — '
            : 'Chapter '
                . $chapter->chapter_number
                . ' — '
    )
    . $article->title
)

@section(
    'meta',
    $article->meta_description
        ?: $article->excerpt
)

@section(
    'canonical',
    route(
        'articles.chapter',
        [
            'slug' =>
                $article->slug,
            'chapterNumber' =>
                $chapter->chapter_number,
        ]
    )
)

@section('og_type', 'article')

@if($article->featured_image)
    @section(
        'og_image',
        asset(
            'storage/'
            . $article->featured_image
        )
    )
@endif

@push('head')
<style>
.chapter-page-kicker{
    color:#8b3326;
    font-size:12px;
    font-weight:800;
    letter-spacing:.06em;
    text-transform:uppercase;
}
.chapter-page-title{
    margin:8px 0 8px;
    font-size:38px;
    line-height:1.1;
}
.chapter-parent-title{
    margin:0 0 18px;
    color:#666;
    font-size:16px;
}
.chapter-progress{
    margin:18px 0 22px;
    padding:10px 12px;
    border:1px solid #e2e2e2;
    border-radius:9px;
    background:#fafafa;
    color:#666;
    font-size:12px;
}
.chapter-nav{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin:30px 0;
}
.chapter-nav-link{
    display:block;
    min-height:74px;
    padding:13px 14px;
    border:1px solid #dcdcdc;
    border-radius:10px;
    color:inherit;
    text-decoration:none;
    background:#fff;
}
.chapter-nav-link.next{
    text-align:right;
}
.chapter-nav-link small{
    display:block;
    margin-bottom:5px;
    color:#8b3326;
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
}
.chapter-nav-link strong{
    font-size:14px;
}
.chapter-index{
    margin:28px 0;
    padding:18px;
    border:1px solid #e2e2e2;
    border-radius:10px;
}
.chapter-index h3{
    margin:0 0 10px;
}
.chapter-index ol{
    margin:0;
    padding-left:22px;
}
.chapter-index li{
    margin:6px 0;
}
.chapter-index .current{
    font-weight:800;
}
@media(max-width:620px){
    .chapter-page-title{
        font-size:31px;
    }
    .chapter-nav{
        grid-template-columns:1fr;
    }
    .chapter-nav-link.next{
        text-align:left;
    }
}
</style>
@endpush

@section('content')

<article class="article">

    <div class="chapter-page-kicker">
        Chapter
        {{ $chapter->chapter_number }}
        of
        {{ $chapters->count() }}
    </div>

    <h1 class="chapter-page-title">
        {{
            $chapter->title
            ?: 'Chapter '
                . $chapter->chapter_number
        }}
    </h1>

    <p class="chapter-parent-title">
        <a
            href="{{
                route(
                    'articles.show',
                    $article->slug
                )
            }}"
        >
            {{ $article->title }}
        </a>
    </p>

    <div class="chapter-progress">
        Page
        {{
            $chapters->search(
                fn ($item) =>
                    (int) $item->id
                    === (int) $chapter->id
            ) + 1
        }}
        of
        {{ $chapters->count() }}
        ·
        {{ number_format($chapter->views) }}
        chapter views
    </div>

    @include(
        'partials.ad',
        ['key' => 'banner_top']
    )

    <div class="body">
        {!! $chapter->body !!}
    </div>

    @include(
        'partials.ad',
        ['key' => 'banner_mid']
    )

    <nav
        class="chapter-nav"
        aria-label="Chapter navigation"
    >
        <div>
            @if($previousChapter)
                <a
                    class="chapter-nav-link"
                    href="{{
                        route(
                            'articles.chapter',
                            [
                                'slug' =>
                                    $article->slug,
                                'chapterNumber' =>
                                    $previousChapter
                                        ->chapter_number,
                            ]
                        )
                    }}"
                >
                    <small>
                        ← Previous
                    </small>

                    <strong>
                        Chapter
                        {{
                            $previousChapter
                                ->chapter_number
                        }}
                        @if($previousChapter->title)
                            —
                            {{
                                $previousChapter
                                    ->title
                            }}
                        @endif
                    </strong>
                </a>
            @endif
        </div>

        <div>
            @if($nextChapter)
                <a
                    class="chapter-nav-link next"
                    href="{{
                        route(
                            'articles.chapter',
                            [
                                'slug' =>
                                    $article->slug,
                                'chapterNumber' =>
                                    $nextChapter
                                        ->chapter_number,
                            ]
                        )
                    }}"
                >
                    <small>
                        Next →
                    </small>

                    <strong>
                        Chapter
                        {{
                            $nextChapter
                                ->chapter_number
                        }}
                        @if($nextChapter->title)
                            —
                            {{
                                $nextChapter
                                    ->title
                            }}
                        @endif
                    </strong>
                </a>
            @endif
        </div>
    </nav>

    @include(
        'partials.ad',
        ['key' => 'banner_bot']
    )

    <section class="chapter-index">
        <h3>All Chapters</h3>

        <ol>
            @foreach($chapters as $item)
                <li
                    class="{{
                        (int) $item->id
                        === (int) $chapter->id
                            ? 'current'
                            : ''
                    }}"
                >
                    @if(
                        (int) $item->id
                        === (int) $chapter->id
                    )
                        Chapter
                        {{ $item->chapter_number }}
                        @if($item->title)
                            —
                            {{ $item->title }}
                        @endif
                    @else
                        <a
                            href="{{
                                route(
                                    'articles.chapter',
                                    [
                                        'slug' =>
                                            $article->slug,
                                        'chapterNumber' =>
                                            $item
                                                ->chapter_number,
                                    ]
                                )
                            }}"
                        >
                            Chapter
                            {{ $item->chapter_number }}
                            @if($item->title)
                                —
                                {{ $item->title }}
                            @endif
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    @if($related->count())
        <h2>More Stories You May Like</h2>

        @foreach($related as $r)
            <div class="story">
                <h3>
                    <a
                        href="{{
                            route(
                                'articles.show',
                                $r->slug
                            )
                        }}"
                    >
                        {{ $r->title }}
                    </a>
                </h3>
            </div>
        @endforeach
    @endif

</article>

@endsection
