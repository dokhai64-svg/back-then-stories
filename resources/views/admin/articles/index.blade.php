@extends('admin.layout')

@section('heading', 'Articles')

@section('content')

<style>
.articles-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:16px;
}
.articles-filter{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
}
.articles-filter input,
.articles-filter select{
    min-height:38px;
}
.articles-table-wrap{
    overflow-x:auto;
}
.articles-table{
    width:100%;
    border-collapse:collapse;
}
.articles-table th{
    padding:11px 10px;
    border-bottom:1px solid #e5e7eb;
    color:#64748b;
    font-size:11px;
    font-weight:700;
    text-align:left;
    text-transform:uppercase;
    letter-spacing:.03em;
    white-space:nowrap;
}
.articles-table td{
    padding:13px 10px;
    border-bottom:1px solid #edf1f5;
    vertical-align:middle;
}
.articles-table tr:last-child td{
    border-bottom:0;
}
.article-main{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:340px;
}
.article-thumb{
    width:58px;
    height:58px;
    border-radius:10px;
    object-fit:cover;
    flex:0 0 auto;
    background:#f1f5f9;
    border:1px solid #e5e7eb;
}
.article-thumb-placeholder{
    width:58px;
    height:58px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
    background:#f1f5f9;
    border:1px solid #e5e7eb;
    color:#94a3b8;
    font-size:11px;
    font-weight:700;
}
.article-title{
    margin:0 0 4px;
    color:#111827;
    font-size:14px;
    line-height:1.35;
    font-weight:700;
    max-width:620px;
}
.article-slug{
    max-width:620px;
    color:#94a3b8;
    font-size:11px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.status-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:5px 9px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
    white-space:nowrap;
}
.status-draft{
    background:#f3f4f6;
    color:#4b5563;
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
.article-actions{
    display:flex;
    align-items:center;
    gap:5px;
    white-space:nowrap;
}
.action-btn{
    width:34px;
    height:34px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid #dbe3ec;
    border-radius:8px;
    background:#fff;
    color:#64748b;
    cursor:pointer;
    text-decoration:none;
    transition:.15s ease;
}
.action-btn:hover{
    background:#f8fafc;
    border-color:#cbd5e1;
    color:#0f172a;
}
.action-btn svg{
    width:17px;
    height:17px;
}
.action-delete{
    color:#b91c1c;
}
.action-delete:hover{
    background:#fef2f2;
    border-color:#fecaca;
    color:#991b1b;
}
.copy-feedback{
    position:fixed;
    right:20px;
    bottom:20px;
    z-index:1000;
    display:none;
    padding:10px 14px;
    border-radius:9px;
    background:#111827;
    color:white;
    font-size:12px;
    box-shadow:0 8px 30px rgba(0,0,0,.18);
}
.pagination-wrap{
    margin-top:16px;
}
@media (max-width: 760px){
    .articles-toolbar{
        align-items:stretch;
    }
    .articles-filter{
        width:100%;
    }
    .articles-filter input{
        flex:1 1 180px;
    }
}
</style>

<div class="card">

    <div class="articles-toolbar">

        <form
            method="get"
            action="{{ route('admin.articles.index') }}"
            class="articles-filter"
        >
            <input
                type="search"
                name="q"
                placeholder="Search title"
                value="{{ request('q') }}"
            >

            <select name="status">
                <option value="">All statuses</option>

                @foreach(['draft','review','scheduled','published'] as $status)
                    <option
                        value="{{ $status }}"
                        @selected(request('status') === $status)
                    >
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>

            <button class="btn secondary" type="submit">
                Filter
            </button>

            @if(request()->filled('q') || request()->filled('status'))
                <a
                    class="btn secondary"
                    href="{{ route('admin.articles.index') }}"
                >
                    Clear
                </a>
            @endif
        </form>

        <a
            class="btn"
            href="{{ route('admin.articles.create') }}"
        >
            + New article
        </a>

    </div>

    <div class="articles-table-wrap">

        <table class="articles-table">

            <thead>
                <tr>
                    <th>Article</th>
                    <th>Site</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Publish</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse($articles as $article)

                    @php
                        $publicUrl = route(
                            'articles.show',
                            ['slug' => $article->slug]
                        );

                        $openUrl = $article->status === 'published'
                            ? $publicUrl
                            : route('admin.articles.preview', $article);
                    @endphp

                    <tr>

                        <td>
                            <div class="article-main">

                                @if($article->featured_image)
                                    <img
                                        class="article-thumb"
                                        src="{{ asset('storage/'.$article->featured_image) }}"
                                        alt=""
                                    >
                                @else
                                    <div class="article-thumb-placeholder">
                                        NO IMG
                                    </div>
                                @endif

                                <div style="min-width:0">

                                    <div class="article-title">
                                        {{ $article->title }}
                                    </div>

                                    <div class="article-slug">
                                        /story/{{ $article->slug }}
                                    </div>

                                </div>

                            </div>
                        </td>

                        <td>
                            {{ $article->site?->name ?? '—' }}
                        </td>

                        <td>
                            {{ $article->category?->name ?? '—' }}
                        </td>

                        <td>
                            <span class="status-pill status-{{ $article->status }}">
                                {{ ucfirst($article->status) }}
                            </span>
                        </td>

                        <td>
                            @if($article->published_at)
                                {{ $article->published_at->format('M d, Y') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            <div
                                class="article-actions"
                                style="justify-content:flex-end"
                            >

                                {{-- 1. Open page --}}
                                <a
                                    class="action-btn"
                                    href="{{ $openUrl }}"
                                    target="_blank"
                                    rel="noopener"
                                    title="{{ $article->status === 'published' ? 'Open live page' : 'Open preview' }}"
                                    aria-label="Open page"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M14 3h7v7"></path>
                                        <path d="M10 14L21 3"></path>
                                        <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path>
                                    </svg>
                                </a>

                                {{-- 2. Copy public URL --}}
                                <button
                                    type="button"
                                    class="action-btn copy-article-url"
                                    data-url="{{ $publicUrl }}"
                                    title="Copy article link"
                                    aria-label="Copy article link"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                </button>

                                {{-- 3. Edit --}}
                                <a
                                    class="action-btn"
                                    href="{{ route('admin.articles.edit', $article) }}"
                                    title="Edit article"
                                    aria-label="Edit article"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                </a>

                                {{-- 4. Delete --}}
                                <form
                                    method="post"
                                    action="{{ route('admin.articles.destroy', $article) }}"
                                    onsubmit="return confirm('Delete this article permanently?')"
                                    style="margin:0"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="action-btn action-delete"
                                        title="Delete article"
                                        aria-label="Delete article"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M3 6h18"></path>
                                            <path d="M8 6V4h8v2"></path>
                                            <path d="M19 6l-1 14H6L5 6"></path>
                                            <path d="M10 11v5"></path>
                                            <path d="M14 11v5"></path>
                                        </svg>
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="6"
                            style="padding:28px;text-align:center;color:#94a3b8"
                        >
                            No articles found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    @if($articles->hasPages())
        <div class="pagination-wrap">
            {{ $articles->links() }}
        </div>
    @endif

</div>

<div id="copyFeedback" class="copy-feedback">
    Article link copied
</div>

@endsection

@push('scripts')

<script>
const copyFeedback =
    document.getElementById('copyFeedback');

let copyFeedbackTimer = null;

function showCopyFeedback(message) {
    copyFeedback.textContent = message;
    copyFeedback.style.display = 'block';

    clearTimeout(copyFeedbackTimer);

    copyFeedbackTimer = setTimeout(function () {
        copyFeedback.style.display = 'none';
    }, 1800);
}

async function copyText(value) {
    if (
        navigator.clipboard &&
        window.isSecureContext
    ) {
        await navigator.clipboard.writeText(value);
        return;
    }

    const textarea =
        document.createElement('textarea');

    textarea.value = value;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';

    document.body.appendChild(textarea);

    textarea.select();
    document.execCommand('copy');

    textarea.remove();
}

document
    .querySelectorAll('.copy-article-url')
    .forEach(function (button) {

        button.addEventListener(
            'click',
            async function () {

                try {
                    await copyText(
                        this.dataset.url
                    );

                    showCopyFeedback(
                        'Article link copied'
                    );

                } catch (error) {
                    console.error(error);

                    showCopyFeedback(
                        'Could not copy link'
                    );
                }
            }
        );

    });
</script>

@endpush
