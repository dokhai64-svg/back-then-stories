@extends('admin.layout')

@section('heading', 'Articles')

@section('content')

@php
    $baseQuery =
        request()->except([
            'page',
            'scope',
        ]);

    $sortUrl = function ($column) use (
        $sort,
        $direction
    ) {
        $nextDirection =
            $sort === $column &&
            $direction === 'asc'
                ? 'desc'
                : 'asc';

        return route(
            'admin.articles.index',
            array_merge(
                request()->except('page'),
                [
                    'sort' => $column,
                    'direction' =>
                        $nextDirection,
                ]
            )
        );
    };

    $sortMark = function ($column) use (
        $sort,
        $direction
    ) {
        if ($sort !== $column) {
            return '↕';
        }

        return $direction === 'asc'
            ? '↑'
            : '↓';
    };
@endphp

<style>
.article-manager-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.article-manager-subtitle{
    margin-top:3px;
    color:#64748b;
    font-size:12px;
}
.article-tabs{
    display:flex;
    gap:7px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.article-tab{
    display:inline-flex;
    align-items:center;
    gap:6px;
    min-height:34px;
    padding:0 12px;
    border:1px solid #dbe3ec;
    border-radius:999px;
    background:#fff;
    color:#475569;
    font-size:12px;
    font-weight:700;
    text-decoration:none;
}
.article-tab.active{
    border-color:#172033;
    background:#172033;
    color:#fff;
}
.article-tab-count{
    opacity:.72;
}
.article-filters{
    display:grid;
    grid-template-columns:
        minmax(190px,1.4fr)
        minmax(125px,.8fr)
        minmax(145px,.9fr)
        minmax(145px,.9fr)
        minmax(135px,.8fr)
        minmax(135px,.8fr)
        auto;
    gap:8px;
    align-items:end;
    margin-bottom:16px;
}
.article-filters input,
.article-filters select{
    min-height:38px;
}
.filter-field label{
    display:block;
    margin-bottom:4px;
    color:#64748b;
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.04em;
}
.filter-actions{
    display:flex;
    gap:7px;
}
.table-scroll{
    overflow-x:auto;
    border:1px solid #edf1f5;
    border-radius:12px;
}
.article-table{
    width:100%;
    min-width:1150px;
    border-collapse:collapse;
    background:#fff;
}
.article-table th{
    padding:12px 10px;
    border-bottom:1px solid #e5e7eb;
    background:#f8fafc;
    color:#64748b;
    font-size:10px;
    line-height:1.15;
    font-weight:800;
    text-align:left;
    text-transform:uppercase;
    letter-spacing:.035em;
    white-space:nowrap;
}
.article-table td{
    padding:12px 10px;
    border-bottom:1px solid #edf1f5;
    vertical-align:middle;
}
.article-table tbody tr:last-child td{
    border-bottom:0;
}
.article-table tbody tr:hover{
    background:#fbfdff;
}
.sort-link{
    color:inherit;
    text-decoration:none;
}
.sort-arrow{
    margin-left:4px;
    color:#94a3b8;
}
.thumb{
    width:58px;
    height:58px;
    display:block;
    border-radius:10px;
    object-fit:cover;
    border:1px solid #e5e7eb;
    background:#f1f5f9;
}
.thumb-empty{
    width:58px;
    height:58px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    border:1px solid #e5e7eb;
    background:#f1f5f9;
    color:#94a3b8;
    font-size:9px;
    font-weight:800;
}
.row-title{
    max-width:490px;
    color:#111827;
    font-size:13px;
    line-height:1.35;
    font-weight:750;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
}
.row-slug{
    margin-top:4px;
    max-width:490px;
    color:#94a3b8;
    font-size:10px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.site-name{
    color:#334155;
    font-size:12px;
}
.site-author{
    margin-top:3px;
    color:#94a3b8;
    font-size:10px;
}
.category-badge,
.status-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:5px 8px;
    border-radius:8px;
    font-size:10px;
    font-weight:750;
    line-height:1.2;
}
.category-badge{
    background:#fff7ed;
    color:#c2410c;
}
.category-empty{
    background:#f8fafc;
    color:#94a3b8;
}
.status-draft{
    background:#f1f5f9;
    color:#475569;
}
.status-review{
    background:#fff7ed;
    color:#c2410c;
}
.status-scheduled{
    background:#eff6ff;
    color:#1d4ed8;
}
.status-published{
    background:#ecfdf5;
    color:#047857;
}
.trashed-badge{
    background:#fef2f2;
    color:#b91c1c;
}
.views-number{
    font-variant-numeric:tabular-nums;
    color:#475569;
    font-size:12px;
}
.date-cell{
    color:#64748b;
    font-size:11px;
    line-height:1.35;
}
.actions{
    display:flex;
    align-items:center;
    gap:3px;
    white-space:nowrap;
}
.icon-action{
    width:31px;
    height:31px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0;
    border:0;
    border-radius:7px;
    background:transparent;
    color:#64748b;
    cursor:pointer;
    text-decoration:none;
}
.icon-action:hover{
    background:#f1f5f9;
    color:#0f172a;
}
.icon-action.danger{
    color:#b91c1c;
}
.icon-action.danger:hover{
    background:#fef2f2;
}
.icon-action.restore{
    color:#047857;
}
.icon-action.restore:hover{
    background:#ecfdf5;
}
.icon-action svg{
    width:18px;
    height:18px;
}
.article-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    padding-top:14px;
}
.rows-control{
    display:flex;
    align-items:center;
    gap:8px;
    color:#64748b;
    font-size:11px;
}
.rows-control select{
    min-height:34px;
    width:auto;
    padding-right:28px;
}
.result-summary{
    color:#64748b;
    font-size:11px;
}
.pagination{
    display:flex;
    align-items:center;
    gap:3px;
    flex-wrap:wrap;
}
.page-link{
    min-width:32px;
    height:32px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0 8px;
    border:1px solid #dbe3ec;
    border-radius:7px;
    background:#fff;
    color:#475569;
    font-size:11px;
    text-decoration:none;
}
.page-link:hover{
    background:#f8fafc;
}
.page-link.active{
    border-color:#172033;
    background:#172033;
    color:#fff;
}
.page-link.disabled{
    pointer-events:none;
    opacity:.42;
}
.copy-toast{
    position:fixed;
    right:20px;
    bottom:20px;
    z-index:9999;
    display:none;
    padding:10px 14px;
    border-radius:9px;
    background:#111827;
    color:#fff;
    font-size:12px;
    box-shadow:0 10px 28px rgba(0,0,0,.2);
}
@media (max-width:1200px){
    .article-filters{
        grid-template-columns:
            repeat(3,minmax(150px,1fr));
    }
}
@media (max-width:760px){
    .article-filters{
        grid-template-columns:1fr;
    }
    .filter-actions{
        width:100%;
    }
    .filter-actions .btn{
        flex:1;
    }
}
</style>

<div class="article-manager-head">
    <div>
        <div class="article-manager-subtitle">
            Manage, filter, sort, restore and track article performance.
        </div>
    </div>

    <a
        class="btn"
        href="{{ route('admin.articles.create') }}"
    >
        + New article
    </a>
</div>

<div class="article-tabs">

    @foreach([
        'active' => 'Active',
        'trash' => 'Trash',
        'all' => 'All',
    ] as $tabKey => $tabLabel)

        <a
            class="article-tab {{ $scope === $tabKey ? 'active' : '' }}"
            href="{{
                route(
                    'admin.articles.index',
                    array_merge(
                        $baseQuery,
                        ['scope' => $tabKey]
                    )
                )
            }}"
        >
            {{ $tabLabel }}

            <span class="article-tab-count">
                {{ $counts[$tabKey] ?? 0 }}
            </span>
        </a>

    @endforeach

</div>

<div class="card">

    <form
        method="get"
        action="{{ route('admin.articles.index') }}"
        class="article-filters"
    >
        <input
            type="hidden"
            name="scope"
            value="{{ $scope }}"
        >

        <input
            type="hidden"
            name="sort"
            value="{{ $sort }}"
        >

        <input
            type="hidden"
            name="direction"
            value="{{ $direction }}"
        >

        <input
            type="hidden"
            name="per_page"
            value="{{ request('per_page', 20) }}"
        >

        <div class="filter-field">
            <label>Search</label>

            <input
                type="search"
                name="q"
                placeholder="Title or slug..."
                value="{{ request('q') }}"
            >
        </div>

        <div class="filter-field">
            <label>Status</label>

            <select name="status">
                <option value="">All statuses</option>

                @foreach([
                    'draft',
                    'review',
                    'scheduled',
                    'published',
                ] as $status)

                    <option
                        value="{{ $status }}"
                        @selected(
                            request('status') ===
                            $status
                        )
                    >
                        {{ ucfirst($status) }}
                    </option>

                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label>Category</label>

            <select name="category_id">
                <option value="">
                    All categories
                </option>

                @foreach($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(
                            (string) request('category_id') ===
                            (string) $category->id
                        )
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label>Site</label>

            <select name="site_id">
                <option value="">
                    All sites
                </option>

                @foreach($sites as $site)
                    <option
                        value="{{ $site->id }}"
                        @selected(
                            (string) request('site_id') ===
                            (string) $site->id
                        )
                    >
                        {{ $site->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-field">
            <label>Published from</label>

            <input
                type="date"
                name="published_from"
                value="{{ request('published_from') }}"
            >
        </div>

        <div class="filter-field">
            <label>Published to</label>

            <input
                type="date"
                name="published_to"
                value="{{ request('published_to') }}"
            >
        </div>

        <div class="filter-actions">
            <button
                type="submit"
                class="btn secondary"
            >
                Filter
            </button>

            <a
                class="btn secondary"
                href="{{
                    route(
                        'admin.articles.index',
                        ['scope' => $scope]
                    )
                }}"
            >
                Clear
            </a>
        </div>
    </form>

    <div class="table-scroll">

        <table class="article-table">

            <thead>
                <tr>
                    <th style="width:78px">
                        Thumb
                    </th>

                    <th>
                        <a
                            class="sort-link"
                            href="{{ $sortUrl('title') }}"
                        >
                            Title
                            <span class="sort-arrow">
                                {{ $sortMark('title') }}
                            </span>
                        </a>
                    </th>

                    <th>
                        Site / Author
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        <a
                            class="sort-link"
                            href="{{ $sortUrl('views') }}"
                        >
                            Views
                            <span class="sort-arrow">
                                {{ $sortMark('views') }}
                            </span>
                        </a>
                    </th>

                    <th>
                        <a
                            class="sort-link"
                            href="{{ $sortUrl('published_at') }}"
                        >
                            Published
                            <span class="sort-arrow">
                                {{ $sortMark('published_at') }}
                            </span>
                        </a>
                    </th>

                    <th style="text-align:right">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody>

                @forelse($articles as $article)

                    @php
                        $isTrashed =
                            method_exists(
                                $article,
                                'trashed'
                            )
                            && $article->trashed();

                        $publicUrl =
                            route(
                                'articles.show',
                                ['slug' => $article->slug]
                            );

                        $openUrl =
                            $article->status === 'published'
                            && !$isTrashed
                                ? $publicUrl
                                : (
                                    !$isTrashed
                                        ? route(
                                            'admin.articles.preview',
                                            $article
                                        )
                                        : null
                                );
                    @endphp

                    <tr>

                        <td>
                            @if($article->featured_image)
                                <img
                                    class="thumb"
                                    src="{{
                                        asset(
                                            'storage/' .
                                            $article->featured_image
                                        )
                                    }}"
                                    alt=""
                                >
                            @else
                                <div class="thumb-empty">
                                    NO IMG
                                </div>
                            @endif
                        </td>

                        <td>
                            <div class="row-title">
                                {{ $article->title }}
                            </div>

                            <div class="row-slug">
                                /story/{{ $article->slug }}
                            </div>
                        </td>

                        <td>
                            <div class="site-name">
                                {{
                                    $article->site?->name
                                    ?? '—'
                                }}
                            </div>

                            @if($article->author)
                                <div class="site-author">
                                    {{
                                        $article->author->name
                                        ?? $article->author->email
                                        ?? 'Administrator'
                                    }}
                                </div>
                            @endif
                        </td>

                        <td>
                            <span
                                class="category-badge {{
                                    $article->category
                                        ? ''
                                        : 'category-empty'
                                }}"
                            >
                                {{
                                    $article->category?->name
                                    ?? 'No category'
                                }}
                            </span>
                        </td>

                        <td>
                            @if($isTrashed)
                                <span class="status-badge trashed-badge">
                                    Trashed
                                </span>
                            @else
                                <span
                                    class="status-badge status-{{ $article->status }}"
                                >
                                    {{ ucfirst($article->status) }}
                                </span>
                            @endif
                        </td>

                        <td>
                            <span class="views-number">
                                {{
                                    number_format(
                                        (int) $article->views
                                    )
                                }}
                            </span>
                        </td>

                        <td>
                            <div class="date-cell">
                                @if($article->published_at)
                                    {{
                                        $article
                                            ->published_at
                                            ->format('M d, Y')
                                    }}
                                @else
                                    —
                                @endif
                            </div>
                        </td>

                        <td>
                            <div
                                class="actions"
                                style="justify-content:flex-end"
                            >

                                @if(!$isTrashed)

                                    {{-- Open public page / preview --}}
                                    <a
                                        class="icon-action"
                                        href="{{ $openUrl }}"
                                        target="_blank"
                                        rel="noopener"
                                        title="{{
                                            $article->status === 'published'
                                                ? 'Open live page'
                                                : 'Open preview'
                                        }}"
                                        aria-label="Open article"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 3h7v7"></path>
                                            <path d="M10 14L21 3"></path>
                                            <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path>
                                        </svg>
                                    </a>

                                    {{-- Copy public URL --}}
                                    <button
                                        type="button"
                                        class="icon-action copy-url"
                                        data-url="{{ $publicUrl }}"
                                        title="Copy public link"
                                        aria-label="Copy public link"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                        </svg>
                                    </button>

                                    {{-- Edit --}}
                                    <a
                                        class="icon-action"
                                        href="{{
                                            route(
                                                'admin.articles.edit',
                                                $article
                                            )
                                        }}"
                                        title="Edit article"
                                        aria-label="Edit article"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </a>

                                    {{-- Move to trash --}}
                                    <form
                                        method="post"
                                        action="{{
                                            route(
                                                'admin.articles.destroy',
                                                $article
                                            )
                                        }}"
                                        style="margin:0"
                                        onsubmit="return confirm('Move this article to Trash?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="icon-action danger"
                                            title="Move to trash"
                                            aria-label="Move to trash"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4h8v2"></path>
                                                <path d="M19 6l-1 14H6L5 6"></path>
                                            </svg>
                                        </button>
                                    </form>

                                @else

                                    {{-- Restore --}}
                                    <form
                                        method="post"
                                        action="{{
                                            route(
                                                'admin.articles.restore',
                                                $article->id
                                            )
                                        }}"
                                        style="margin:0"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="icon-action restore"
                                            title="Restore article"
                                            aria-label="Restore article"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 12a9 9 0 1 0 3-6.7"></path>
                                                <path d="M3 3v6h6"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    {{-- Permanent delete --}}
                                    <form
                                        method="post"
                                        action="{{
                                            route(
                                                'admin.articles.force-delete',
                                                $article->id
                                            )
                                        }}"
                                        style="margin:0"
                                        onsubmit="return confirm('Permanently delete this article? This cannot be undone.')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="icon-action danger"
                                            title="Delete permanently"
                                            aria-label="Delete permanently"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4h8v2"></path>
                                                <path d="M19 6l-1 14H6L5 6"></path>
                                                <path d="M10 11v5"></path>
                                                <path d="M14 11v5"></path>
                                            </svg>
                                        </button>
                                    </form>

                                @endif

                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            style="
                                padding:36px;
                                text-align:center;
                                color:#94a3b8;
                            "
                        >
                            No articles found.
                        </td>
                    </tr>

                @endforelse

            </tbody>
        </table>

    </div>

    <div class="article-footer">

        <form
            method="get"
            action="{{ route('admin.articles.index') }}"
            class="rows-control"
        >
            @foreach(
                request()->except(
                    'page',
                    'per_page'
                )
                as $key => $value
            )
                @if(is_scalar($value))
                    <input
                        type="hidden"
                        name="{{ $key }}"
                        value="{{ $value }}"
                    >
                @endif
            @endforeach

            <span>Rows per page</span>

            <select
                name="per_page"
                onchange="this.form.submit()"
            >
                @foreach(
                    $perPageOptions
                    as $option
                )
                    <option
                        value="{{ $option }}"
                        @selected(
                            $articles->perPage()
                            === $option
                        )
                    >
                        {{ $option }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="result-summary">
            @if($articles->total())
                Showing
                {{ $articles->firstItem() }}
                to
                {{ $articles->lastItem() }}
                of
                {{ number_format($articles->total()) }}
                results
            @else
                0 results
            @endif
        </div>

        @if($articles->hasPages())

            @php
                $current =
                    $articles->currentPage();

                $last =
                    $articles->lastPage();

                $start =
                    max(1, $current - 2);

                $end =
                    min($last, $current + 2);
            @endphp

            <nav class="pagination">

                <a
                    class="page-link {{
                        $articles->onFirstPage()
                            ? 'disabled'
                            : ''
                    }}"
                    href="{{
                        $articles->onFirstPage()
                            ? '#'
                            : $articles->previousPageUrl()
                    }}"
                >
                    ‹
                </a>

                @if($start > 1)
                    <a
                        class="page-link"
                        href="{{ $articles->url(1) }}"
                    >
                        1
                    </a>

                    @if($start > 2)
                        <span class="page-link disabled">
                            …
                        </span>
                    @endif
                @endif

                @for(
                    $page = $start;
                    $page <= $end;
                    $page++
                )
                    <a
                        class="page-link {{
                            $page === $current
                                ? 'active'
                                : ''
                        }}"
                        href="{{ $articles->url($page) }}"
                    >
                        {{ $page }}
                    </a>
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)
                        <span class="page-link disabled">
                            …
                        </span>
                    @endif

                    <a
                        class="page-link"
                        href="{{ $articles->url($last) }}"
                    >
                        {{ $last }}
                    </a>
                @endif

                <a
                    class="page-link {{
                        $articles->hasMorePages()
                            ? ''
                            : 'disabled'
                    }}"
                    href="{{
                        $articles->hasMorePages()
                            ? $articles->nextPageUrl()
                            : '#'
                    }}"
                >
                    ›
                </a>

            </nav>

        @endif

    </div>

</div>

<div
    id="copyToast"
    class="copy-toast"
>
    Link copied
</div>

@endsection

@push('scripts')

<script>
const copyToast =
    document.getElementById('copyToast');

let toastTimer = null;

function showToast(message) {
    copyToast.textContent = message;
    copyToast.style.display = 'block';

    clearTimeout(toastTimer);

    toastTimer = setTimeout(
        function () {
            copyToast.style.display =
                'none';
        },
        1800
    );
}

async function copyText(value) {
    if (
        navigator.clipboard &&
        window.isSecureContext
    ) {
        await navigator.clipboard
            .writeText(value);

        return;
    }

    const textarea =
        document.createElement('textarea');

    textarea.value = value;
    textarea.setAttribute(
        'readonly',
        ''
    );

    textarea.style.position =
        'fixed';

    textarea.style.opacity =
        '0';

    document.body.appendChild(
        textarea
    );

    textarea.select();

    document.execCommand('copy');

    textarea.remove();
}

document
    .querySelectorAll('.copy-url')
    .forEach(function (button) {

        button.addEventListener(
            'click',
            async function () {
                try {
                    await copyText(
                        this.dataset.url
                    );

                    showToast(
                        'Article link copied'
                    );
                } catch (error) {
                    console.error(error);

                    showToast(
                        'Could not copy link'
                    );
                }
            }
        );

    });
</script>

@endpush
