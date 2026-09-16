@extends('layouts.app')

@section('title', 'Privacy Policy | Back Then Stories')
@section('meta', 'Privacy Policy for Back Then Stories, including information about cookies, analytics, advertising, and user choices.')

@section('content')
<main class="article">
    <h1>Privacy Policy</h1>
    <p class="meta">Last updated: September 16, 2026</p>

    <div class="body">
        <p>
            Back Then Stories ("we," "us," or "our") operates
            <strong>backthenstories.com</strong> (the "Site"). This Privacy Policy explains
            how information may be collected, used, and shared when you visit or interact
            with the Site.
        </p>

        <h2>Information We May Collect</h2>
        <p>
            When you use the Site, certain information may be collected automatically by
            our hosting, analytics, and advertising services. This may include your IP
            address, browser type, device type, operating system, referring pages, pages
            viewed, approximate location derived from your IP address, and information
            about how you interact with the Site.
        </p>
        <p>
            If you contact us directly, we may receive the information you choose to
            provide, such as your name, email address, and the content of your message.
        </p>

        <h2>Cookies and Similar Technologies</h2>
        <p>
            The Site may use cookies, web beacons, local storage, and similar technologies
            to operate the Site, remember preferences, measure traffic, understand how the
            Site is used, and support advertising.
        </p>
        <p>
            You can usually control or delete cookies through your browser settings.
            Disabling cookies may affect some features of the Site.
        </p>

        <h2>Google Analytics</h2>
        <p>
            We may use Google Analytics to help us understand how visitors use the Site.
            Google Analytics may collect information such as pages visited, session
            duration, browser and device information, and approximate geographic
            information. Google may process this information in accordance with its own
            privacy policies.
        </p>
        <p>
            You can learn more about how Google handles information from sites that use its
            services at
            <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener noreferrer">
                How Google uses information from sites or apps that use our services
            </a>.
        </p>

        <h2>Advertising and Google AdSense</h2>
        <p>
            We may use Google AdSense and other Google advertising services to display
            advertisements on the Site.
        </p>
        <p>
            Third-party vendors, including Google, use cookies to serve ads based on a
            user's prior visits to this Site or other websites. Google's use of advertising
            cookies enables Google and its partners to serve ads to users based on their
            visits to this Site and/or other sites on the Internet.
        </p>
        <p>
            Users may opt out of personalized advertising by visiting
            <a href="https://adssettings.google.com/" target="_blank" rel="noopener noreferrer">
                Google Ads Settings
            </a>.
            Users may also learn about additional opt-out choices at
            <a href="https://www.aboutads.info/choices/" target="_blank" rel="noopener noreferrer">
                YourAdChoices
            </a>.
        </p>
        <p>
            If we use additional third-party advertising vendors or ad networks in the
            future, those providers may also use cookies or similar technologies to serve,
            measure, and personalize advertising. We will update this Privacy Policy when
            appropriate to reflect material changes in the advertising services we use.
        </p>

        <h2>Consent for Visitors in the EEA, United Kingdom, and Switzerland</h2>
        <p>
            Where required, visitors in the European Economic Area, the United Kingdom,
            and Switzerland may be shown a consent message before certain advertising or
            analytics technologies are used. Consent choices may affect whether ads are
            personalized and how analytics data is processed.
        </p>

        <h2>How We Use Information</h2>
        <p>We may use information collected through the Site to:</p>
        <ul>
            <li>operate, maintain, and improve the Site;</li>
            <li>understand traffic and audience engagement;</li>
            <li>detect technical issues, abuse, or security problems;</li>
            <li>respond to messages or requests you send us;</li>
            <li>measure and display advertising; and</li>
            <li>comply with applicable legal obligations.</li>
        </ul>

        <h2>Third-Party Services</h2>
        <p>
            The Site may contain links to third-party websites and may use third-party
            services for hosting, analytics, advertising, media, or other functionality.
            Those third parties have their own privacy practices, and this Privacy Policy
            does not control how they handle information on their own services.
        </p>

        <h2>Data Retention</h2>
        <p>
            Information may be retained for as long as reasonably necessary for the
            purposes described in this policy, including site operation, analytics,
            security, legal compliance, and dispute resolution. Retention periods used by
            third-party services are governed by those providers' own policies and settings.
        </p>

        <h2>Your Privacy Choices and Rights</h2>
        <p>
            Depending on where you live, you may have rights relating to your personal
            information, such as requesting access, correction, deletion, or restriction
            of certain processing. You may also be able to withdraw consent where consent
            is the legal basis for processing.
        </p>
        <p>
            To ask a privacy-related question or make a request, please use our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>

        <h2>Changes to This Privacy Policy</h2>
        <p>
            We may update this Privacy Policy from time to time to reflect changes to the
            Site, our services, advertising partners, legal requirements, or privacy
            practices. The date at the top of this page indicates when this policy was last
            updated.
        </p>

        <h2>Contact</h2>
        <p>
            If you have questions about this Privacy Policy, please contact us through our
            <a href="{{ route('contact') }}">Contact page</a>.
        </p>
    </div>
</main>
@endsection
