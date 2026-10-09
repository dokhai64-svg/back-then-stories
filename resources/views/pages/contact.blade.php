@extends('layouts.app')

@section('title', 'Contact | Back Then Stories')
@section('meta', 'Contact the Back Then Stories Editorial Team for editorial feedback, corrections, privacy requests, copyright concerns, advertising inquiries, and general questions.')

@section('content')
<main class="article">
    <h1>Contact</h1>

    <div class="body">
        <p>
            Thank you for visiting <strong>Back Then Stories</strong>. We welcome messages
            from readers, rights holders, advertisers, and others who would like to contact
            the publication.
        </p>

        <p>
            Messages are reviewed by the
            <strong>Back Then Stories Editorial Team</strong>.
        </p>

        <h2>Email</h2>
        <p>
            You can reach us at:
            <a href="mailto:dokhai64@gmail.com">dokhai64@gmail.com</a>
        </p>

        <h2>Editorial Feedback and Corrections</h2>
        <p>
            If you believe an article contains an important factual error, inaccurate date,
            incorrect name, quotation issue, historical detail, chart information, or other
            information that should be reviewed, please contact us.
        </p>

        <p>
            To help us review the matter efficiently, please include:
        </p>

        <ul>
            <li>the article title or URL;</li>
            <li>the specific information you believe is inaccurate;</li>
            <li>a brief explanation of the issue; and</li>
            <li>when available, a reliable source that supports the correction.</li>
        </ul>

        <p>
            We review correction requests carefully and update published content when a change
            is supported by reliable information.
        </p>

        <h2>Copyright and Media Concerns</h2>
        <p>
            If you are a copyright owner or an authorized representative and believe that
            an image, video, quotation, or other material appearing on Back Then Stories
            raises a copyright, licensing, or attribution concern, please contact us using
            the email address above.
        </p>

        <p>
            Please include the page URL, identify the material at issue, explain the nature
            of your concern, and provide sufficient information for us to review the request.
        </p>

        <h2>Privacy Requests</h2>
        <p>
            For questions or requests related to privacy, personal information, cookies,
            analytics, advertising technologies, or privacy choices, please use the email
            address above and include <strong>Privacy Request</strong> in the subject line.
        </p>

        <p>
            Additional information about how Back Then Stories handles privacy and advertising
            technologies is available in our
            <a href="{{ route('privacy') }}">Privacy Policy</a>.
        </p>

        <h2>Advertising and Business Inquiries</h2>
        <p>
            For advertising, partnerships, or other business-related inquiries, please use
            the email address above and include <strong>Business Inquiry</strong> in the
            subject line.
        </p>

        <p>
            Advertising or business relationships do not determine our editorial coverage.
        </p>

        <h2>Story Suggestions and General Feedback</h2>
        <p>
            Readers are welcome to suggest artists, songs, performances, cultural moments,
            or historical stories they would like to see covered on Back Then Stories.
        </p>

        <p>
            We also welcome general feedback about the website and our editorial content.
            While we cannot guarantee that every suggestion will become a published story,
            reader feedback can help inform future coverage.
        </p>

        <h2>Response Time</h2>
        <p>
            We review messages as soon as reasonably possible. Response times may vary
            depending on the nature and complexity of the request.
        </p>

        <h2>More About Back Then Stories</h2>
        <p>
            For more information about our publication, editorial approach, privacy practices,
            and content standards, please see our
            <a href="{{ route('about') }}">About page</a>,
            <a href="{{ route('privacy') }}">Privacy Policy</a>, and
            <a href="{{ route('editorial') }}">Editorial Policy</a>.
        </p>
    </div>
</main>
@endsection
