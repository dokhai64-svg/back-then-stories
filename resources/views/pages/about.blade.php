@extends('layouts.app')

@section('title', 'About Us | Back Then Stories')
@section('meta', 'Learn about Back Then Stories, an editorial website sharing memorable stories about entertainment, culture, everyday life, people, history, and the moments that stay with us.')

@section('content')
<main class="article">
    <h1>About Us</h1>

    <div class="body">
        <p>
            <strong>Back Then Stories</strong> is an editorial website built around memorable
            stories — from entertainment and popular culture to everyday life, remarkable people,
            human experiences, history, and the moments that stay with us.
        </p>

        <p>
            We publish carefully edited articles designed to inform, entertain, and give readers
            useful context. Some stories look back at familiar names and cultural moments, while
            others explore real-life experiences, unusual events, inspiring journeys, personal
            turning points, and stories worth remembering.
        </p>

        <h2>What We Cover</h2>
        <p>
            Our coverage may include music, film, television, celebrities, culture, nostalgia,
            history, lifestyle, human-interest stories, relationships, family, personal journeys,
            remarkable events, and other subjects that can spark curiosity, emotion, or meaningful
            conversation.
        </p>

        <p>
            Back Then Stories is not limited to one category. As the publication grows, we may
            continue expanding into new topics that fit our broader mission: telling engaging,
            understandable, and worthwhile stories for a general audience.
        </p>

        <h2>Our Editorial Approach</h2>
        <p>
            We aim to present stories in a clear, accessible, and responsible way. When an article
            includes factual claims, dates, names, quotations, historical details, or other
            verifiable information, we seek to check important details against reliable available
            sources.
        </p>

        <p>
            Some topics may already be widely discussed elsewhere. Our goal is to create our own
            edited presentation for readers, add useful context where possible, and avoid simply
            reproducing material from another publication.
        </p>

        <h2>Corrections and Updates</h2>
        <p>
            Information can change, and historical or personal accounts can sometimes conflict.
            If we discover a meaningful factual error, we may correct or update the article.
            Readers who believe something needs correction can reach us through our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>

        <h2>Independence</h2>
        <p>
            Back Then Stories is an independent editorial publication. Unless an article clearly
            states otherwise, references to people, companies, brands, artists, films, television
            programs, products, organizations, or other properties do not imply endorsement,
            sponsorship, or official affiliation.
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
