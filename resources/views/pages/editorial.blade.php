@extends('layouts.app')

@section('title', 'Editorial Policy | Back Then Stories')
@section('meta', 'Editorial Policy for Back Then Stories, including standards for accuracy, sourcing, corrections, independence, AI-assisted tools, advertising, and reader feedback.')

@section('content')
<main class="article">
    <h1>Editorial Policy</h1>
    <p class="meta">Last updated: September 17, 2026</p>

    <div class="body">
        <p>
            <strong>Back Then Stories</strong> publishes editorial content for a general
            audience across entertainment, culture, nostalgia, history, lifestyle,
            human-interest stories, personal journeys, remarkable events, and other topics
            that may be informative, memorable, or worth discussing.
        </p>

        <p>
            This Editorial Policy explains the standards we aim to follow when researching,
            writing, editing, updating, and publishing content on
            <strong>backthenstories.com</strong>.
        </p>

        <h2>Accuracy and Verification</h2>
        <p>
            We aim to publish information that is clear, accurate, and appropriately
            contextualized. When an article contains factual claims, we seek to verify
            important details such as names, dates, locations, releases, chart history,
            public records, quotations, and other verifiable information using reliable
            available sources.
        </p>

        <p>
            Some topics, especially historical events, personal recollections, or older
            entertainment stories, may have incomplete or conflicting accounts. When
            uncertainty is material to the story, we aim to avoid presenting disputed or
            uncertain information as established fact.
        </p>

        <h2>Sources and Attribution</h2>
        <p>
            Depending on the topic, our research may draw from official records, primary
            sources, reputable news organizations, published interviews, books, recognized
            industry databases, archival material, public statements, and other credible
            references.
        </p>

        <p>
            We aim to distinguish our own editorial presentation from material originating
            with third parties. When attribution is appropriate, we seek to identify the
            source rather than presenting another publisher's reporting as our own.
        </p>

        <h2>Original Editorial Presentation</h2>
        <p>
            Back Then Stories may cover subjects that have been widely reported or discussed
            elsewhere. Our goal is to create our own edited presentation for our audience,
            organize the information in a useful way, and add context where appropriate.
            We do not aim to simply reproduce substantial portions of another publication's
            work.
        </p>

        <h2>Headlines and Images</h2>
        <p>
            Headlines and images may be designed to attract reader interest, but they should
            not intentionally misrepresent the central facts of the article. Featured images,
            captions, and other visual material should be relevant to the story and used in a
            manner consistent with applicable rights, licenses, permissions, or legal
            exceptions.
        </p>

        <h2>Corrections and Updates</h2>
        <p>
            If we identify a meaningful factual error, we may correct or update the article.
            Minor spelling, grammar, formatting, or clarity edits may be made without a
            separate correction notice.
        </p>

        <p>
            Readers who believe an article contains a significant factual error can contact
            us through our <a href="{{ route('contact') }}">Contact page</a>. Please include
            the article URL, the specific issue, and, when possible, a reliable supporting
            source.
        </p>

        <h2>Opinion, Commentary, and Interpretation</h2>
        <p>
            Some articles may include analysis, interpretation, commentary, or descriptions
            of public reaction. Where relevant, we aim to distinguish factual reporting from
            interpretation or opinion and avoid presenting subjective judgments as objective
            facts.
        </p>

        <h2>AI-Assisted and Editorial Tools</h2>
        <p>
            We may use software tools, including AI-assisted tools, to support tasks such as
            research organization, drafting assistance, rewriting, translation, formatting,
            or editing. These tools do not replace our responsibility for the material we
            publish.
        </p>

        <p>
            Content intended for publication should be reviewed and edited before it is
            published, and important factual claims should be checked when reasonably
            possible. We do not treat AI-generated output by itself as an authoritative
            source.
        </p>

        <h2>Editorial Independence</h2>
        <p>
            Editorial decisions are intended to be made independently from advertising
            considerations. The presence of an advertiser, advertising network, affiliate
            relationship, or business partner does not give that party control over our
            ordinary editorial coverage unless a piece is clearly identified as sponsored
            or otherwise commercially influenced.
        </p>

        <h2>Advertising and Sponsored Material</h2>
        <p>
            The Site may display advertisements to support its operation. Advertising should
            be distinguishable from ordinary editorial content. If we publish sponsored,
            paid, or promotional material in the future, we aim to label it in a manner that
            makes the commercial relationship clear to readers.
        </p>

        <h2>Conflicts of Interest</h2>
        <p>
            When a financial, personal, or business relationship could reasonably affect how
            readers understand a story, we aim to disclose that relationship when it is
            material to the content.
        </p>

        <h2>Respect, Privacy, and Sensitive Topics</h2>
        <p>
            We aim to treat people fairly and avoid publishing unnecessary private or
            sensitive personal information. Stories involving tragedy, health, family
            matters, allegations, or other sensitive subjects should be handled with
            appropriate care and context.
        </p>

        <h2>Copyright and Content Concerns</h2>
        <p>
            We respect intellectual property rights. Rights holders who believe material on
            the Site raises a copyright, licensing, or attribution concern can contact us
            through our <a href="{{ route('contact') }}">Contact page</a> and provide the
            relevant page URL and details of the concern.
        </p>

        <h2>Reader Feedback</h2>
        <p>
            Reader feedback can help us improve the Site. For corrections, editorial
            questions, copyright concerns, or other content-related inquiries, please visit
            our <a href="{{ route('contact') }}">Contact page</a>.
        </p>

        <p>
            For additional information about the Site, please also review our
            <a href="{{ route('about') }}">About page</a>,
            <a href="{{ route('privacy') }}">Privacy Policy</a>, and
            <a href="{{ route('terms') }}">Terms of Use</a>.
        </p>
    </div>
</main>
@endsection
