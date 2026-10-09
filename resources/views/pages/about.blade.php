@extends('layouts.app')

@section('title', 'About Us | Back Then Stories')
@section('meta', 'Learn about Back Then Stories, an independent editorial publication covering classic music, entertainment history, cultural moments, memorable people, and stories worth remembering.')

@section('content')
<main class="article">
    <h1>About Us</h1>

    <div class="body">
        <p>
            <strong>Back Then Stories</strong> is an independent editorial publication dedicated
            to memorable stories from music, entertainment, popular culture, history, and the
            people and moments that continue to connect generations.
        </p>

        <p>
            Our goal is to make the past engaging, understandable, and worth revisiting. We publish
            stories about artists, songs, performances, cultural turning points, remarkable lives,
            and other moments that have left a lasting impression.
        </p>

        <h2>Who We Are</h2>
        <p>
            Articles on Back Then Stories are published under the byline
            <strong>Back Then Stories Editorial Team</strong>. This byline represents the editorial
            work involved in researching, writing, reviewing, updating, and maintaining the content
            published on this website.
        </p>

        <p>
            Our editorial team focuses on presenting well-researched, readable stories that help
            readers rediscover the music, performers, cultural moments, and historical context that
            shaped earlier generations.
        </p>

        <h2>What We Cover</h2>
        <p>
            Our primary coverage includes classic music, rock, pop, soul, country, Motown, folk,
            jazz, film, television, entertainment history, nostalgia, influential performers,
            songwriters, recordings, concerts, and memorable cultural moments from past generations.
        </p>

        <p>
            We may also publish selected human-interest or cultural stories when they are closely
            connected to the broader historical and entertainment focus of Back Then Stories.
        </p>

        <h2>Our Editorial Approach</h2>
        <p>
            We aim to present stories in a clear, engaging, and responsible way. When an article
            includes factual claims, dates, names, quotations, chart positions, historical details,
            or other verifiable information, we seek to review important details against reliable
            available sources.
        </p>

        <p>
            Depending on the subject, our research may include official artist or organization
            websites, interviews, reputable news or music publications, historical archives,
            reference works, chart records, and other relevant source material.
        </p>

        <p>
            Some subjects we cover have been reported or discussed by many publications over time.
            Our goal is not to reproduce another publication's work, but to create an original,
            independently edited presentation that provides useful context and a clear reading
            experience for our audience.
        </p>

        <h2>Accuracy, Corrections, and Updates</h2>
        <p>
            We care about accuracy and editorial clarity. Historical accounts can sometimes differ,
            and new information may become available after an article is published.
        </p>

        <p>
            If we identify a meaningful factual error, we may correct or update the article. Readers
            who believe a story contains inaccurate information are encouraged to contact us and
            include the article URL, the information in question, and any relevant supporting source
            that may help us review the issue.
        </p>

        <p>
            Correction requests can be submitted through our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>

        <h2>Editorial Independence</h2>
        <p>
            Back Then Stories is an independent editorial publication. Unless clearly stated
            otherwise, references to artists, public figures, companies, record labels, films,
            television programs, products, organizations, or other properties do not imply
            endorsement, sponsorship, partnership, or official affiliation.
        </p>

        <p>
            Advertising relationships do not determine which stories we publish or the conclusions
            presented in our editorial content.
        </p>

        <h2>Images, Video, and External Media</h2>
        <p>
            Images, video, and other media may be used to support the editorial purpose of a story.
            Third-party media, including embedded video from services such as YouTube, remains
            subject to the terms, policies, and rights of the respective platform or rights holder.
        </p>

        <p>
            If you are a copyright owner or authorized representative and have a concern regarding
            material appearing on Back Then Stories, please contact us with enough information to
            identify the material and the page where it appears.
        </p>

        <h2>Advertising and Privacy</h2>
        <p>
            Back Then Stories may display advertising to help support the operation of the website.
            Advertising is kept separate from our editorial decision-making.
        </p>

        <p>
            Information about advertising technologies, cookies, analytics, and privacy choices is
            available in our
            <a href="{{ route('privacy') }}">Privacy Policy</a>.
        </p>

        <h2>Contact Back Then Stories</h2>
        <p>
            We welcome factual correction requests, editorial feedback, copyright or media inquiries,
            general questions, and story suggestions.
        </p>

        <p>
            To contact the Back Then Stories Editorial Team, please visit our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>
    </div>
</main>
@endsection
