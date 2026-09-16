@extends('layouts.app')

@section('title', 'Terms of Use | Back Then Stories')
@section('meta', 'Terms of Use for Back Then Stories, covering use of the website, editorial content, intellectual property, third-party links, advertising, and limitations of liability.')

@section('content')
<main class="article">
    <h1>Terms of Use</h1>
    <p class="meta">Last updated: September 16, 2026</p>

    <div class="body">
        <p>
            Welcome to <strong>Back Then Stories</strong>. These Terms of Use ("Terms")
            govern your access to and use of <strong>backthenstories.com</strong> (the "Site").
            By using the Site, you agree to these Terms. If you do not agree, please do not
            use the Site.
        </p>

        <h2>About the Site</h2>
        <p>
            Back Then Stories is an editorial publication featuring articles and stories
            about entertainment, culture, nostalgia, history, lifestyle, human-interest
            topics, personal journeys, remarkable events, and other subjects of general
            interest.
        </p>

        <h2>Editorial Content</h2>
        <p>
            We aim to provide clear, engaging, and responsibly edited content. Although we
            seek to verify important factual details, we do not guarantee that every article
            will always be complete, current, or free from error.
        </p>
        <p>
            Historical accounts, public records, interviews, memories, and other source
            materials may sometimes conflict. We may update or correct content when new or
            more reliable information becomes available.
        </p>

        <h2>Informational Purpose</h2>
        <p>
            Content on the Site is provided for general informational and entertainment
            purposes. It should not be treated as professional legal, financial, medical,
            or other specialized advice.
        </p>

        <h2>Intellectual Property</h2>
        <p>
            Unless otherwise stated, the Site's original written content, editorial
            presentation, branding, layout, and other original materials are owned by or
            licensed to Back Then Stories and are protected by applicable intellectual
            property laws.
        </p>
        <p>
            You may view and share links to our pages for personal, non-commercial use.
            You may not copy, republish, reproduce, distribute, scrape, sell, or create
            derivative works from substantial portions of our original content without
            permission, except where permitted by law.
        </p>

        <h2>Third-Party Materials and References</h2>
        <p>
            Articles may reference people, companies, brands, songs, films, television
            programs, publications, photographs, videos, trademarks, or other third-party
            materials. Ownership of those third-party materials remains with their
            respective rights holders.
        </p>
        <p>
            References to third-party names, brands, or properties do not imply endorsement,
            sponsorship, or official affiliation unless explicitly stated.
        </p>

        <h2>External Links</h2>
        <p>
            The Site may link to third-party websites or services. We do not control those
            websites and are not responsible for their availability, content, security,
            privacy practices, or terms. Visiting third-party sites is at your own discretion.
        </p>

        <h2>Advertising</h2>
        <p>
            The Site may display advertisements or use third-party advertising services.
            Advertisers and advertising providers may use cookies or similar technologies
            subject to their own policies. For more information, please review our
            <a href="{{ route('privacy') }}">Privacy Policy</a>.
        </p>
        <p>
            The presence of an advertisement does not constitute our endorsement of the
            advertised product, service, company, or claim.
        </p>

        <h2>Acceptable Use</h2>
        <p>You agree not to:</p>
        <ul>
            <li>use the Site for unlawful, fraudulent, or abusive purposes;</li>
            <li>attempt to interfere with the Site's operation, security, or availability;</li>
            <li>introduce malicious code, automated attacks, or harmful software;</li>
            <li>attempt to gain unauthorized access to administrative areas, accounts, or systems;</li>
            <li>use automated tools to extract substantial amounts of Site content in a way that harms the Site or violates applicable law; or</li>
            <li>misrepresent your relationship with Back Then Stories.</li>
        </ul>

        <h2>Corrections and Content Concerns</h2>
        <p>
            If you believe an article contains a significant factual error, copyright
            concern, attribution issue, or other problem, please contact us through our
            <a href="{{ route('contact') }}">Contact page</a>. We may review, update, or
            remove material when appropriate.
        </p>

        <h2>Site Availability and Changes</h2>
        <p>
            We may modify, suspend, remove, or discontinue any part of the Site at any time.
            We do not guarantee uninterrupted or error-free access to the Site.
        </p>

        <h2>Disclaimer of Warranties</h2>
        <p>
            To the extent permitted by applicable law, the Site and its content are provided
            on an "as is" and "as available" basis without warranties of any kind, whether
            express or implied.
        </p>

        <h2>Limitation of Liability</h2>
        <p>
            To the extent permitted by applicable law, Back Then Stories and its operators
            will not be liable for indirect, incidental, special, consequential, or similar
            losses arising from your use of, or inability to use, the Site or from reliance
            on Site content.
        </p>

        <h2>Privacy</h2>
        <p>
            Your use of the Site is also subject to our
            <a href="{{ route('privacy') }}">Privacy Policy</a>, which explains how
            information may be collected and used.
        </p>

        <h2>Changes to These Terms</h2>
        <p>
            We may update these Terms from time to time. The date at the top of this page
            indicates when the Terms were last updated. Continued use of the Site after an
            update means you accept the revised Terms.
        </p>

        <h2>Contact</h2>
        <p>
            If you have questions about these Terms, please contact us through our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>
    </div>
</main>
@endsection
