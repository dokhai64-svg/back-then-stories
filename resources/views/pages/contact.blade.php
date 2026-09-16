@extends('layouts.app')

@section('title', 'Contact | Back Then Stories')
@section('meta', 'Contact Back Then Stories for general questions, editorial feedback, corrections, privacy requests, advertising inquiries, or copyright concerns.')

@section('content')
<main class="article">
    <h1>Contact</h1>

    <div class="body">
        <p>
            Thanks for visiting <strong>Back Then Stories</strong>. We welcome questions,
            feedback, correction requests, privacy inquiries, advertising inquiries, and
            other messages related to the Site.
        </p>

        <h2>Email</h2>
        <p>
            You can reach us at:
            <a href="mailto:dokhai64@gmail.com">dokhai64@gmail.com</a>
        </p>

        <h2>Editorial Feedback and Corrections</h2>
        <p>
            If you believe an article contains an important factual error, please include
            the article title or URL and a brief explanation of the issue. When possible,
            include a reliable source that supports the correction.
        </p>

        <h2>Privacy Requests</h2>
        <p>
            For questions or requests related to privacy, personal information, cookies,
            analytics, or advertising technologies, please use the email address above and
            include <strong>Privacy Request</strong> in the subject line.
        </p>

        <h2>Advertising and Business Inquiries</h2>
        <p>
            For advertising, partnerships, or other business-related inquiries, please use
            the email address above and include <strong>Business Inquiry</strong> in the
            subject line.
        </p>

        <h2>Copyright and Content Concerns</h2>
        <p>
            If you are a rights holder and believe material on Back Then Stories raises a
            copyright or attribution concern, please send us the page URL, identify the
            material at issue, and provide enough information for us to review the request.
        </p>

        <h2>Response Time</h2>
        <p>
            We review messages as soon as reasonably possible. Response times may vary
            depending on the nature of the request.
        </p>

        <p>
            For more information about how the Site operates, please see our
            <a href="{{ route('about') }}">About page</a>,
            <a href="{{ route('privacy') }}">Privacy Policy</a>, and
            <a href="{{ route('editorial') }}">Editorial Policy</a>.
        </p>
    </div>
</main>
@endsection
