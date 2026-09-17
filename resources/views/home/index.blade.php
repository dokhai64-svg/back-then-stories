@extends('layouts.app')

@push('head')
<style>
/* ===== Back Then Stories — Homepage V2 ===== */

.home-hero{
    padding:42px 0 26px;
}

.home-grid{
    display:grid;
    grid-template-columns:minmax(0,1.85fr) minmax(280px,.9fr);
    gap:34px;
    align-items:start;
}

.home-featured{
    position:relative;
    overflow:hidden;
    background:#fff;
    border:1px solid var(--line);
    border-radius:14px;
    transition:transform .18s ease, box-shadow .18s ease;
}

.home-featured:hover{
    transform:translateY(-2px);
    box-shadow:0 14px 34px rgba(0,0,0,.08);
}

.home-featured.has-image .pad{
    padding:24px 26px 28px;
}

.home-featured.no-image{
    border-top:4px solid var(--accent);
    background:
        radial-gradient(circle at 93% 14%, rgba(122,47,34,.055) 0 72px, transparent 73px),
        radial-gradient(circle at 87% 27%, rgba(122,47,34,.035) 0 128px, transparent 129px),
        #fff;
}

.home-featured.no-image .pad{
    padding:34px 36px 36px;
}

.home-featured .kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding-bottom:7px;
    border-bottom:2px solid rgba(122,47,34,.2);
}

.home-featured h2{
    margin:12px 0 14px;
    font-size:35px;
    line-height:1.12;
    letter-spacing:-.015em;
}

.home-featured .deck{
    margin:0;
    max-width:760px;
    color:#343434;
    font-size:18px;
    line-height:1.68;
}

.home-side{
    border-top:1px solid var(--line);
}

.home-side .story{
    padding:21px 0 22px;
    border-top:0;
    border-bottom:1px solid var(--line);
}

.home-side .story:last-child{
    border-bottom:0;
}

.home-side h3{
    margin:8px 0 0;
    font-size:20px;
    line-height:1.13;
}

.home-side a,
.latest-card a,
.home-featured a{
    transition:opacity .15s ease;
}

.home-side a:hover,
.latest-card a:hover,
.home-featured a:hover{
    opacity:.68;
}

.home-latest{
    padding:18px 0 46px;
}

.section-head{
    display:flex;
    align-items:end;
    justify-content:space-between;
    gap:18px;
    margin-bottom:18px;
    padding-bottom:12px;
    border-bottom:1px solid var(--line);
}

.section-head h2{
    margin:0;
    font-size:28px;
    line-height:1.1;
}

.section-head p{
    margin:0;
    color:var(--muted);
    font-size:13px;
}

.latest-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}

.latest-card{
    min-height:215px;
    display:flex;
    flex-direction:column;
    background:#fff;
    border:1px solid var(--line);
    border-radius:12px;
    padding:20px;
    transition:transform .18s ease, box-shadow .18s ease;
}

.latest-card:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 24px rgba(0,0,0,.06);
}

.latest-card .kicker{
    margin-bottom:10px;
}

.latest-card h3{
    margin:0 0 10px;
    font-size:21px;
    line-height:1.16;
}

.latest-card p{
    margin:0;
    color:#4a4a4a;
    font-size:14px;
    line-height:1.58;
}

.latest-card .read-more{
    margin-top:auto;
    padding-top:16px;
    font-size:12px;
    font-weight:800;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:var(--accent);
}

.empty-latest{
    grid-column:1/-1;
    padding:22px;
    border:1px solid var(--line);
    border-radius:12px;
    background:#fff;
    color:var(--muted);
}

@media(max-width:900px){
    .home-grid{
        grid-template-columns:1fr;
    }

    .home-side{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:0 22px;
    }

    .latest-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:760px){
    .home-hero{
        padding:24px 0 16px;
    }

    .home-featured.no-image .pad,
    .home-featured.has-image .pad{
        padding:24px;
    }

    .home-featured h2{
        font-size:29px;
    }

    .home-featured .deck{
        font-size:17px;
    }

    .home-side{
        grid-template-columns:1fr;
    }

    .latest-grid{
        grid-template-columns:1fr;
    }

    .latest-card{
        min-height:0;
    }

    .section-head{
        align-items:start;
        flex-direction:column;
    }
}
</style>
@endpush

@section('content')
<main>
    <section class="home-hero">
        <div class="wrap">
            @if($featured)
                <div class="home-grid">
                    <article class="home-featured {{ $featured->featured_image ? 'has-image' : 'no-image' }}">
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

    @if($featured)
        @php
            $moreStories = $latest
                ->where('id', '!=', $featured->id)
                ->skip(4)
                ->take(6);
        @endphp

        <section class="home-latest">
            <div class="wrap">
                <div class="section-head">
                    <h2>Latest Stories</h2>
                    <p>More stories, people and moments worth remembering.</p>
                </div>

                <div class="latest-grid">
                    @forelse($moreStories as $a)
                        <article class="latest-card">
                            <div class="kicker">{{ $a->category?->name ?? 'Story' }}</div>

                            <h3>
                                <a
                                    href="{{ route('articles.show', $a->slug) }}"
                                    style="color:inherit;text-decoration:none"
                                >
                                    {{ $a->title }}
                                </a>
                            </h3>

                            @if($a->excerpt)
                                <p>{{ \Illuminate\Support\Str::limit($a->excerpt, 145) }}</p>
                            @endif

                            <div class="read-more">
                                <a
                                    href="{{ route('articles.show', $a->slug) }}"
                                    style="color:inherit;text-decoration:none"
                                >
                                    Read story →
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="empty-latest">
                            More stories will appear here as new articles are published.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
