@extends('admin.layout')

@section('heading', 'Dashboard')

@section('content')

@php
    $maxDailyViews =
        max(
            1,
            collect($dailyViews)
                ->max('value')
        );

    $maxTrending =
        max(
            1,
            (int) (
                collect($trending)
                    ->max('range_views')
                ?? 1
            )
        );

    $maxAdChanges =
        max(
            1,
            collect($adChangeSeries)
                ->max('value')
        );

    $maxEditedAds =
        max(
            1,
            (int) (
                collect($mostEditedAds)
                    ->max('edit_count')
                ?? 1
            )
        );
@endphp

<style>
.dash-shell{
    display:grid;
    gap:16px;
}
.dash-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
}
.dash-sub{
    color:#64748b;
    font-size:12px;
    margin-top:4px;
}
.dash-controls{
    display:flex;
    gap:8px;
    align-items:end;
    flex-wrap:wrap;
}
.dash-controls .field{
    margin:0;
}
.dash-controls label{
    font-size:10px;
    color:#64748b;
    margin-bottom:4px;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.dash-controls input,
.dash-controls select{
    min-height:36px;
    padding:7px 9px;
    font-size:12px;
}
.metric-grid{
    display:grid;
    grid-template-columns:
        repeat(4,minmax(0,1fr));
    gap:10px;
}
.metric{
    padding:14px 16px;
}
.metric-label{
    color:#64748b;
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.metric-value{
    margin-top:5px;
    font-size:25px;
    font-weight:800;
    color:#111827;
}
.analytics-grid{
    display:grid;
    grid-template-columns:
        minmax(220px,.72fr)
        minmax(0,2fr);
    gap:14px;
}
.overview-list{
    display:grid;
    gap:10px;
}
.overview-box{
    border:1px solid #dbe3ec;
    border-radius:11px;
    padding:12px;
}
.overview-box small{
    display:block;
    color:#64748b;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.035em;
}
.overview-box strong{
    display:block;
    margin-top:5px;
    font-size:20px;
}
.card-title-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
}
.card-title-row h2{
    margin:0;
    font-size:16px;
}
.range-form{
    display:flex;
    gap:6px;
    align-items:end;
    flex-wrap:wrap;
}
.range-form input{
    width:135px;
    min-height:32px;
    padding:6px 8px;
    font-size:11px;
}
.mini-btn{
    min-height:32px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0 10px;
    border:1px solid #dbe3ec;
    border-radius:7px;
    background:#fff;
    color:#334155;
    text-decoration:none;
    font-size:10px;
    font-weight:750;
    cursor:pointer;
}
.mini-btn.primary{
    background:#1687e8;
    border-color:#1687e8;
    color:#fff;
}
.bar-chart{
    height:175px;
    display:flex;
    align-items:flex-end;
    gap:7px;
    padding:6px 2px 0;
    border-bottom:1px solid #dbe3ec;
}
.bar-item{
    flex:1;
    min-width:17px;
    height:100%;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-end;
    gap:5px;
}
.bar-track{
    width:100%;
    flex:1;
    display:flex;
    align-items:flex-end;
    border-radius:5px 5px 0 0;
    background:#f1f5f9;
    overflow:hidden;
}
.bar-fill{
    width:100%;
    min-height:3px;
    border-radius:5px 5px 0 0;
    background:#11a8ee;
}
.bar-label{
    color:#64748b;
    font-size:8px;
    white-space:nowrap;
}
.top-list{
    display:grid;
    gap:8px;
    margin-top:14px;
}
.top-row{
    display:grid;
    grid-template-columns:
        minmax(0,1fr)
        auto auto;
    gap:8px;
    align-items:center;
}
.top-main{
    min-width:0;
}
.top-title{
    font-size:11px;
    color:#0f172a;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.progress{
    height:5px;
    border-radius:999px;
    background:#e8eef4;
    overflow:hidden;
    margin-top:5px;
}
.progress span{
    display:block;
    height:100%;
    border-radius:999px;
    background:#11a8ee;
}
.progress.green span{
    background:#14b88a;
}
.top-number{
    min-width:62px;
    text-align:right;
    font-size:10px;
    font-weight:800;
}
.delta{
    color:#0e7490;
    font-size:9px;
    white-space:nowrap;
}
.two-col{
    display:grid;
    grid-template-columns:
        2fr 1fr;
    gap:14px;
}
.small-panel{
    min-height:185px;
}
.small-chart{
    height:110px;
}
.empty-note{
    color:#94a3b8;
    font-size:11px;
}
.info-grid{
    display:grid;
    grid-template-columns:
        1fr 1fr;
    gap:10px;
}
.info-box{
    border:1px solid #dbe3ec;
    border-radius:10px;
    padding:12px;
}
.info-box small{
    color:#64748b;
    font-size:9px;
    text-transform:uppercase;
}
.info-box strong{
    display:block;
    margin-top:4px;
}
.audit-table{
    width:100%;
    border-collapse:collapse;
}
.audit-table th,
.audit-table td{
    padding:9px 8px;
    font-size:10px;
}
.audit-table th{
    font-size:9px;
}
.field-chips{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
}
.field-chip{
    display:inline-flex;
    padding:3px 6px;
    border-radius:999px;
    background:#f1f5f9;
    color:#475569;
    font-size:9px;
}
@media(max-width:1100px){
    .metric-grid{
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }
    .analytics-grid,
    .two-col{
        grid-template-columns:1fr;
    }
}
@media(max-width:650px){
    .metric-grid{
        grid-template-columns:1fr;
    }
    .bar-chart{
        overflow-x:auto;
    }
    .bar-item{
        min-width:28px;
    }
}
</style>

<div class="dash-shell">

    <div class="dash-head">
        <div>
            <div class="dash-sub">
                Performance dashboard
                @if($site)
                    · Site:
                    <strong>
                        {{ $site->name }}
                    </strong>
                @endif
            </div>
        </div>

        <form
            method="get"
            action="{{ route('admin.dashboard') }}"
            class="dash-controls"
        >
            <div class="field">
                <label>Site</label>

                <select name="site_id">
                    @foreach($sites as $siteOption)
                        <option
                            value="{{ $siteOption->id }}"
                            @selected(
                                (int) $siteId ===
                                (int) $siteOption->id
                            )
                        >
                            {{ $siteOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <input
                type="hidden"
                name="from"
                value="{{ $from->toDateString() }}"
            >

            <input
                type="hidden"
                name="to"
                value="{{ $to->toDateString() }}"
            >

            <button
                type="submit"
                class="mini-btn primary"
            >
                Apply
            </button>
        </form>
    </div>

    <div class="metric-grid">
        <div class="card metric">
            <div class="metric-label">
                Total ads
            </div>
            <div class="metric-value">
                {{ number_format($summary['total_ads']) }}
            </div>
        </div>

        <div class="card metric">
            <div class="metric-label">
                Active ads
            </div>
            <div class="metric-value">
                {{ number_format($summary['active_ads']) }}
            </div>
        </div>

        <div class="card metric">
            <div class="metric-label">
                Changes · 7 days
            </div>
            <div class="metric-value">
                {{ number_format($summary['ad_changes_7d']) }}
            </div>
        </div>

        <div class="card metric">
            <div class="metric-label">
                Ads changed · 7 days
            </div>
            <div class="metric-value">
                {{ number_format($summary['changed_ads_7d']) }}
            </div>
        </div>
    </div>

    <div class="analytics-grid">

        <div class="card">
            <div class="card-title-row">
                <h2>Post Views Overview</h2>
            </div>

            <div class="overview-list">
                <div class="overview-box">
                    <small>Published posts</small>
                    <strong>
                        {{ number_format($summary['published_posts']) }}
                    </strong>
                </div>

                <div class="overview-box">
                    <small>Total post views</small>
                    <strong>
                        {{ number_format($summary['total_post_views']) }}
                    </strong>
                </div>

                <div class="overview-box">
                    <small>Average views / post</small>
                    <strong>
                        {{ number_format($summary['average_views']) }}
                    </strong>
                </div>
            </div>
        </div>

        <div class="card">

            <div class="card-title-row">
                <h2>Article views by day</h2>

                <form
                    method="get"
                    action="{{ route('admin.dashboard') }}"
                    class="range-form"
                >
                    <input
                        type="hidden"
                        name="site_id"
                        value="{{ $siteId }}"
                    >

                    <div>
                        <label class="small muted">
                            From
                        </label>
                        <input
                            type="date"
                            name="from"
                            value="{{ $from->toDateString() }}"
                        >
                    </div>

                    <div>
                        <label class="small muted">
                            To
                        </label>
                        <input
                            type="date"
                            name="to"
                            value="{{ $to->toDateString() }}"
                        >
                    </div>

                    <button
                        type="submit"
                        class="mini-btn primary"
                    >
                        Apply
                    </button>
                </form>
            </div>

            <div class="bar-chart">
                @foreach($dailyViews as $point)
                    @php
                        $height =
                            max(
                                3,
                                round(
                                    ($point['value'] / $maxDailyViews)
                                    * 100
                                )
                            );
                    @endphp

                    <div
                        class="bar-item"
                        title="{{
                            $point['date']
                            . ': '
                            . number_format($point['value'])
                            . ' views'
                        }}"
                    >
                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                style="height:{{ $height }}%"
                            ></div>
                        </div>

                        <div class="bar-label">
                            {{ $point['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div
                class="card-title-row"
                style="margin-top:15px;margin-bottom:5px"
            >
                <h2 style="font-size:12px">
                    Top Viewed Posts
                </h2>

                <a
                    class="mini-btn"
                    href="{{
                        route(
                            'admin.dashboard.export-urls',
                            ['site_id' => $siteId]
                        )
                    }}"
                >
                    Export URLs (.txt)
                </a>
            </div>

            <div class="top-list">
                @forelse($topViewed as $article)
                    @php
                        $width =
                            $summary['total_post_views'] > 0
                                ? max(
                                    2,
                                    min(
                                        100,
                                        round(
                                            ($article->views /
                                            max(
                                                1,
                                                $topViewed->max('views')
                                            ))
                                            * 100
                                        )
                                    )
                                )
                                : 2;
                    @endphp

                    <div class="top-row">
                        <div class="top-main">
                            <div class="top-title">
                                {{ $article->title }}
                            </div>

                            <div class="progress">
                                <span
                                    style="width:{{ $width }}%"
                                ></span>
                            </div>
                        </div>

                        <div class="top-number">
                            {{ number_format((int) $article->views) }}
                        </div>

                        <div class="delta">
                            +{{ number_format((int) $article->range_views) }}/range
                        </div>
                    </div>
                @empty
                    <div class="empty-note">
                        No published articles yet.
                    </div>
                @endforelse
            </div>

        </div>
    </div>

    <div class="card">

        <div class="card-title-row">
            <h2>Popular articles</h2>

            <a
                class="mini-btn"
                href="{{ route('admin.articles.index', [
                    'status' => 'published',
                    'sort' => 'views',
                    'direction' => 'desc',
                    'site_id' => $siteId,
                ]) }}"
            >
                Open Articles
            </a>
        </div>

        <div class="top-list">
            @forelse($trending as $article)
                @php
                    $current =
                        (int) $article->range_views;

                    $previous =
                        (int) $article->previous_views;

                    $delta =
                        $current - $previous;

                    $width =
                        max(
                            2,
                            min(
                                100,
                                round(
                                    ($current / $maxTrending)
                                    * 100
                                )
                            )
                        );
                @endphp

                <div class="top-row">
                    <div class="top-main">
                        <div class="top-title">
                            {{ $article->title }}
                        </div>

                        <div class="progress green">
                            <span
                                style="width:{{ $width }}%"
                            ></span>
                        </div>
                    </div>

                    <div class="top-number">
                        {{ number_format($current) }}
                    </div>

                    <div
                        class="delta"
                        style="{{
                            $delta < 0
                                ? 'color:#b91c1c'
                                : ''
                        }}"
                    >
                        {{
                            $delta >= 0
                                ? '+'
                                : ''
                        }}{{ number_format($delta) }}
                    </div>
                </div>
            @empty
                <div class="empty-note">
                    Daily view history begins after this analytics upgrade.
                </div>
            @endforelse
        </div>
    </div>

    <div class="two-col">

        <div class="card small-panel">
            <div class="card-title-row">
                <h2>Ad change speed · 14 days</h2>
            </div>

            <div class="bar-chart small-chart">
                @foreach($adChangeSeries as $point)
                    @php
                        $height =
                            max(
                                3,
                                round(
                                    ($point['value'] / $maxAdChanges)
                                    * 100
                                )
                            );
                    @endphp

                    <div
                        class="bar-item"
                        title="{{
                            $point['date']
                            . ': '
                            . number_format($point['value'])
                            . ' changes'
                        }}"
                    >
                        <div class="bar-track">
                            <div
                                class="bar-fill"
                                style="height:{{ $height }}%"
                            ></div>
                        </div>

                        <div class="bar-label">
                            {{ $point['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card small-panel">
            <div class="card-title-row">
                <h2>Most edited ads · 30 days</h2>
            </div>

            <div class="top-list">
                @forelse($mostEditedAds as $ad)
                    @php
                        $width =
                            max(
                                3,
                                round(
                                    (
                                        (int) $ad->edit_count
                                        / $maxEditedAds
                                    ) * 100
                                )
                            );
                    @endphp

                    <div class="top-row">
                        <div class="top-main">
                            <div class="top-title">
                                {{
                                    $ad->label
                                    ?: 'Deleted ad slot'
                                }}
                            </div>

                            <div class="progress">
                                <span
                                    style="width:{{ $width }}%"
                                ></span>
                            </div>
                        </div>

                        <div class="top-number">
                            {{ number_format((int) $ad->edit_count) }}
                        </div>

                        <div></div>
                    </div>
                @empty
                    <div class="empty-note">
                        No ad edits tracked yet.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <div class="two-col">

        <div class="card">
            <div class="card-title-row">
                <h2>My site access</h2>
            </div>

            <div class="info-grid">
                <div class="info-box">
                    <small>Current role</small>
                    <strong>
                        {{
                            ucfirst(
                                auth()->user()->role
                                ?? 'user'
                            )
                        }}
                    </strong>
                </div>

                <div class="info-box">
                    <small>Sites available</small>
                    <strong>
                        {{ number_format($sites->count()) }}
                    </strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title-row">
                <h2>Article activity · 30 days</h2>
            </div>

            <div class="info-grid">
                <div class="info-box">
                    <small>Created</small>
                    <strong>
                        {{ number_format($activity30['created']) }}
                    </strong>
                </div>

                <div class="info-box">
                    <small>Published</small>
                    <strong>
                        {{ number_format($activity30['published']) }}
                    </strong>
                </div>

                <div class="info-box">
                    <small>Updated</small>
                    <strong>
                        {{ number_format($activity30['updated']) }}
                    </strong>
                </div>

                <div class="info-box">
                    <small>All articles</small>
                    <strong>
                        {{ number_format($summary['all_articles']) }}
                    </strong>
                </div>
            </div>
        </div>

    </div>

    <div class="card">
        <div class="card-title-row">
            <h2>Recent ad updates</h2>
        </div>

        <div style="overflow-x:auto">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Ad</th>
                        <th>Action</th>
                        <th>Fields changed</th>
                        <th>User</th>
                        <th>Time</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentAdChanges as $audit)
                        <tr>
                            <td>
                                {{
                                    $audit->adSlot?->label
                                    ?? 'Deleted ad slot'
                                }}
                            </td>

                            <td>
                                {{ ucfirst($audit->action) }}
                            </td>

                            <td>
                                <div class="field-chips">
                                    @foreach(
                                        array_keys(
                                            $audit->changed_fields
                                            ?? []
                                        )
                                        as $field
                                    )
                                        <span class="field-chip">
                                            {{ $field }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td>
                                {{
                                    $audit->user?->name
                                    ?? 'System'
                                }}
                            </td>

                            <td class="muted">
                                {{
                                    $audit->created_at
                                        ->diffForHumans()
                                }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="empty-note"
                            >
                                No ad changes tracked yet. Tracking begins after this upgrade.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
