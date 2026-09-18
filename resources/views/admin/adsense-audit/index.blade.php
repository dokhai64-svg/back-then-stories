@extends('admin.layout')

@section('title', 'AdSense Site-wide Audit')

@section('content')

<style>
.audit-wrap{
    max-width:1180px;
    margin:0 auto;
}
.audit-hero,
.audit-card{
    border:1px solid #dde3ea;
    border-radius:14px;
    background:#fff;
}
.audit-hero{
    padding:20px;
    margin-bottom:18px;
}
.audit-title{
    margin:0;
    font-size:24px;
    line-height:1.2;
}
.audit-sub{
    margin:8px 0 0;
    color:#64748b;
    font-size:13px;
    line-height:1.5;
}
.audit-run{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:44px;
    margin-top:16px;
    padding:0 18px;
    border:0;
    border-radius:9px;
    background:#111;
    color:#fff;
    font-weight:800;
    cursor:pointer;
}
.audit-summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:18px;
}
.audit-stat{
    padding:16px;
    border:1px solid #dde3ea;
    border-radius:12px;
    background:#fff;
}
.audit-stat b{
    display:block;
    margin-top:5px;
    font-size:24px;
}
.audit-badge{
    display:inline-flex;
    align-items:center;
    padding:6px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
}
.audit-badge.ready,
.audit-level-pass{
    color:#166534;
    background:#e9f8ef;
}
.audit-badge.review,
.audit-level-warn{
    color:#854d0e;
    background:#fff7d6;
}
.audit-badge.block,
.audit-level-block{
    color:#991b1b;
    background:#feecec;
}
.audit-level-info{
    color:#475569;
    background:#eef2f7;
}
.audit-card{
    padding:18px;
    margin-bottom:18px;
}
.audit-card h2{
    margin:0 0 14px;
    font-size:18px;
}
.audit-checks{
    display:grid;
    gap:8px;
}
.audit-check{
    display:grid;
    grid-template-columns:94px 1fr;
    gap:10px;
    align-items:start;
    padding:9px 10px;
    border:1px solid #edf0f4;
    border-radius:9px;
}
.audit-level{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:25px;
    padding:0 8px;
    border-radius:999px;
    font-size:10px;
    font-weight:900;
}
.audit-check strong{
    display:block;
    font-size:12px;
}
.audit-check p{
    margin:3px 0 0;
    color:#596579;
    font-size:11px;
    line-height:1.45;
}
.audit-table-wrap{
    overflow:auto;
}
.audit-table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
}
.audit-table th,
.audit-table td{
    padding:10px 9px;
    border-bottom:1px solid #edf0f4;
    text-align:left;
    vertical-align:top;
}
.audit-table th{
    color:#64748b;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.audit-table a{
    color:#2563eb;
    text-decoration:none;
}
.audit-details{
    margin-top:8px;
}
.audit-details summary{
    cursor:pointer;
    font-weight:700;
    font-size:11px;
}
.audit-article-checks{
    display:grid;
    gap:6px;
    margin-top:8px;
}
.audit-article-check{
    padding:7px 8px;
    border-radius:7px;
    background:#f8fafc;
    font-size:10px;
    line-height:1.4;
}
.audit-manual li{
    margin:7px 0;
}
.audit-policy-links{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}
.audit-policy-links a{
    display:inline-flex;
    padding:7px 9px;
    border:1px solid #dbe3ec;
    border-radius:8px;
    color:#2563eb;
    text-decoration:none;
    font-size:11px;
}
@media(max-width:800px){
    .audit-summary{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}
</style>

<div class="audit-wrap">

    <section class="audit-hero">
        <h1 class="audit-title">
            AdSense Site-wide Audit V4
        </h1>

        <p class="audit-sub">
            Quét toàn bộ Published Articles và các thành phần quan trọng của website trước khi gửi Google AdSense review.
            Đây là công cụ kiểm tra nội bộ, không phải chứng nhận Google sẽ duyệt.
        </p>

        <form
            method="POST"
            action="{{ route('admin.adsense-audit.run') }}"
        >
            @csrf

            <button
                type="submit"
                class="audit-run"
            >
                🔍 CHẠY SITE-WIDE ADSENSE AUDIT
            </button>
        </form>
    </section>

    @if($report)

        @php
            $badgeClass =
                $report['status'] === 'READY'
                    ? 'ready'
                    : (
                        $report['status'] === 'BLOCK'
                            ? 'block'
                            : 'review'
                    );

            $badgeText =
                $report['status'] === 'READY'
                    ? 'SẴN SÀNG REVIEW'
                    : (
                        $report['status'] === 'BLOCK'
                            ? 'CẦN SỬA TRƯỚC'
                            : 'CẦN XEM LẠI'
                    );
        @endphp

        <section class="audit-card">
            <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
                <div>
                    <h2 style="margin-bottom:4px">
                        Kết quả toàn site
                    </h2>
                    <div style="font-size:11px;color:#64748b">
                        Tạo lúc {{ $report['generated_at']->format('Y-m-d H:i:s') }}
                    </div>
                </div>

                <span class="audit-badge {{ $badgeClass }}">
                    {{ $badgeText }}
                </span>
            </div>
        </section>

        <div class="audit-summary">
            <div class="audit-stat">
                Published
                <b>{{ $report['published_count'] }}</b>
            </div>

            <div class="audit-stat">
                READY
                <b>{{ $report['summary']['ready_articles'] }}</b>
            </div>

            <div class="audit-stat">
                NEED REVIEW
                <b>{{ $report['summary']['review_articles'] }}</b>
            </div>

            <div class="audit-stat">
                BLOCK
                <b>{{ $report['summary']['blocked_articles'] }}</b>
            </div>
        </div>

        <section class="audit-card">
            <h2>Kiểm tra toàn site</h2>

            <div class="audit-checks">
                @foreach($report['site_checks'] as $check)
                    @php
                        $levelText =
                            $check['level'] === 'pass'
                                ? 'PASS'
                                : (
                                    $check['level'] === 'block'
                                        ? 'BLOCK'
                                        : (
                                            $check['level'] === 'warn'
                                                ? 'REVIEW'
                                                : 'INFO'
                                        )
                                );
                    @endphp

                    <div class="audit-check">
                        <span class="audit-level audit-level-{{ $check['level'] }}">
                            {{ $levelText }}
                        </span>

                        <div>
                            <strong>{{ $check['title'] }}</strong>
                            <p>{{ $check['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="audit-card">
            <h2>Published Articles</h2>

            <div class="audit-table-wrap">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Article</th>
                            <th>Words</th>
                            <th>YouTube</th>
                            <th>Images</th>
                            <th>Issues</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($report['articles'] as $articleReport)
                            <tr>
                                <td>
                                    @php
                                        $articleClass =
                                            $articleReport['status'] === 'READY'
                                                ? 'ready'
                                                : (
                                                    $articleReport['status'] === 'BLOCK'
                                                        ? 'block'
                                                        : 'review'
                                                );
                                    @endphp

                                    <span class="audit-badge {{ $articleClass }}">
                                        {{ $articleReport['status'] }}
                                    </span>
                                </td>

                                <td style="min-width:300px">
                                    <strong>
                                        {{ $articleReport['title'] }}
                                    </strong>

                                    <div style="margin-top:4px;color:#64748b;font-size:10px">
                                        ID {{ $articleReport['id'] }}
                                        @if($articleReport['category'])
                                            · {{ $articleReport['category'] }}
                                        @endif
                                        @if($articleReport['chapter_count'])
                                            · {{ $articleReport['chapter_count'] }} chapter(s)
                                        @endif
                                    </div>

                                    <div style="margin-top:5px">
                                        <a
                                            href="{{ $articleReport['url'] }}"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Mở bài ↗
                                        </a>
                                    </div>

                                    <details class="audit-details">
                                        <summary>
                                            Xem chi tiết kiểm tra
                                        </summary>

                                        <div class="audit-article-checks">
                                            @foreach($articleReport['checks'] as $check)
                                                <div class="audit-article-check">
                                                    <b>
                                                        {{ strtoupper($check['level']) }}
                                                        · {{ $check['title'] }}
                                                    </b>
                                                    <br>
                                                    {{ $check['detail'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>

                                <td>
                                    {{ $articleReport['words'] }}
                                </td>

                                <td>
                                    {{ $articleReport['youtube_count'] }}
                                </td>

                                <td>
                                    {{ $articleReport['body_image_count'] }}
                                </td>

                                <td>
                                    @if($articleReport['block_count'])
                                        <div style="color:#991b1b">
                                            {{ $articleReport['block_count'] }} block
                                        </div>
                                    @endif

                                    @if($articleReport['warning_count'])
                                        <div style="color:#854d0e">
                                            {{ $articleReport['warning_count'] }} review
                                        </div>
                                    @endif

                                    @if(
                                        !$articleReport['block_count']
                                        && !$articleReport['warning_count']
                                    )
                                        <span style="color:#166534">
                                            Không có
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    Chưa có Published Article.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="audit-card">
            <h2>Các mục bắt buộc kiểm tra thủ công</h2>

            <ul class="audit-manual">
                @foreach($report['manual_checks'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>

        <section class="audit-card">
            <h2>Google Policy References</h2>

            <div class="audit-policy-links">
                @foreach($report['policy_links'] as $link)
                    <a
                        href="{{ $link['url'] }}"
                        target="_blank"
                        rel="noopener"
                    >
                        {{ $link['label'] }} ↗
                    </a>
                @endforeach
            </div>
        </section>

    @endif

</div>

@endsection
