@extends('layouts.app')

@section('title', 'About Us | Back Then Stories')
@section('meta', 'Learn about Back Then Stories, an editorial website covering classic music, television, film, performers, and cultural memories.')

@section('content')
<main class="article">
    <h1>About Us</h1>

    <div class="body">
        <p>
            <strong>Back Then Stories</strong> is an editorial website dedicated to the music,
            television, film, performers, and cultural moments that continue to live in people's
            memories.
        </p>

        <p>
            We publish carefully edited stories about classic entertainment and the people behind it.
            Our goal is to give readers clear, engaging context around memorable songs, artists,
            performances, television moments, films, and entertainment history.
        </p>

        <h2>What We Cover</h2>
        <p>
            Our coverage focuses primarily on classic popular culture, including music, singers,
            songwriters, actors, television personalities, films, performances, and significant
            moments from entertainment history.
        </p>

        <h2>Our Editorial Approach</h2>
        <p>
            We aim to present stories in an accessible and accurate way. When preparing articles,
            we seek to verify important names, dates, releases, chart history, quotations, and other
            factual details using reliable available sources.
        </p>

        <p>
            Some stories may revisit widely remembered events or topics that have been covered
            elsewhere over the years. Our articles are edited for our own audience and presentation,
            and we aim to add useful context rather than simply reproduce material from another
            publication.
        </p>

        <h2>Corrections and Updates</h2>
        <p>
            Entertainment history can contain conflicting accounts or incomplete records. If we
            discover a meaningful factual error, we may correct or update the article. Readers who
            believe something needs correction can reach us through our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>

        <h2>Independence</h2>
        <p>
            Back Then Stories is an editorial publication. Unless an article clearly states
            otherwise, references to artists, performers, companies, songs, films, television
            programs, trademarks, or other properties do not imply endorsement, sponsorship, or
            official affiliation.
        </p>

        <h2>Advertising</h2>
        <p>
            The Site may display advertising to help support its operation. Advertising does not
            determine our editorial coverage. Information about advertising technologies, cookies,
            analytics, and privacy choices is available in our
            <a href="{{ route('privacy') }}">Privacy Policy</a>.
        </p>

        <h2>Contact Us</h2>
        <p>
            For questions, feedback, correction requests, or other inquiries, please visit our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>
    </div>
</main>
@endsection
