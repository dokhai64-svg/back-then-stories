@extends('layouts.app')

@push('head')
<style>
.home-grid{
    align-items:start;
}

.home-featured{
    position:relative;
    overflow:hidden;
    transition:transform .18s ease, box-shadow .18s ease;
}

.home-featured:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 28px rgba(0,0,0,.08);
}

.home-featured.no-image{
    border-top:4px solid var(--accent);
    background:
        radial-gradient(circle at 92% 12%, rgba(122,47,34,.08) 0 70px, transparent 71px),
        radial-gradient(circle at 86% 25%, rgba(122,47,34,.04) 0 120px, transparent 121px),
        #fff;
}

.home-featured.no-image .pad{
    padding:32px 34px 34px;
}

.home-featured.no-image h2{
    margin:10px 0 14px;
    font-size:34px;
    line-height:1.12;
    max-width:760px;
}

.home-featured.no-image .deck{
    margin:0;
    max-width:760px;
    font-size:18px;
    line-height:1.65;
}

.home-featured .kicker{
    display:inline-block;
    padding-bottom:6px;
    border-bottom:2px solid rgba(122,47,34,.25);
}

.home-side .story{
    padding:20px 0;
}

.home-side .story:first-child{
    border-top:0;
    padding-top:0;
}

.home-side h3{
    margin:8px 0 0;
    line-height:1.12;
}

.home-side a{
    transition:opacity .15s ease;
}

.home-side a:hover{
    opacity:.68;
}

@media(max-width:760px){
    .home-featured.no-image .pad{
        padding:24px;
    }

    .home-featured.no-image h2{
        font-size:29px;
    }

    .home-featured.no-image .deck{
        font-size:17px;
    }
}
</style>
@endpush

@section('content')
<main>
    <section class="hero">
        <div class="wrap">
            @if($featured)
                <div class="grid home-grid">
                    <article class="card home-featured {{ $featured->featured_image ? 'has-image' : 'no-image' }}">
                        @if($featured->featured_image)
                            <img
                                class="media"
                                src="{{ asset('storage/'.$featured->featured_image) }}"
                                alt="{{ $featured->title }}"
                            >
                        @endif

                        <div class="pad">
                            <div class="kicker">Featured Story</div>

                            <h2>
                                <a
                                    href="{{ route('articles.show', $featured->slug) }}"
                                    style="color:inherit;text-decoration:none"
                                >
                                    {{ $featured->title }}
                                </a>
                            </h2>

                            @if($featured->excerpt)
                                <p class="deck">{{ $featured->excerpt }}</p>
                            @endif
                        </div>
                    </article>

                    <aside class="home-side">
                        @foreach($latest->where('id', '!=', $featured->id)->take(4) as $a)
                            <div class="story">
                                <div class="kicker">{{ $a->category?->name ?? 'Story' }}</div>

                                <h3>
                                    <a
                                        href="{{ route('articles.show', $a->slug) }}"
                                        style="color:inherit;text-decoration:none"
                                    >
                                        {{ $a->title }}
                                    </a>
                                </h3>
                            </div>
                        @endforeach
                    </aside>
                </div>
            @else
                <div class="card">
                    <div class="pad">
                        <h2>Your publisher CMS is ready.</h2>
                        <p>Sign in at <strong>/admin</strong> and publish your first story.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
</main>
@endsection
