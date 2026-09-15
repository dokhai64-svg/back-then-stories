@extends('admin.layout')

@section('heading', $article->exists ? 'Edit Article' : 'New Article')

@section('content')

<style>
.article-form-grid{
    display:grid;
    grid-template-columns:minmax(0, 2fr) minmax(320px, 420px);
    gap:18px;
    align-items:start;
}
.article-form-grid > *{
    min-width:0;
}
.article-form-grid aside{
    min-width:0;
}
@media (max-width: 980px){
    .article-form-grid{
        grid-template-columns:1fr;
    }
}

.search-preview{
    margin-top:18px;
    padding:16px;
    border:1px solid #e5e7eb;
    border-radius:12px;
    background:#fff;
}
.search-preview-label{
    margin-bottom:12px;
    font-size:12px;
    font-weight:700;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:#64748b;
}
.search-preview-box{
    max-width:680px;
    padding:14px 16px;
    border:1px solid #e5e7eb;
    border-radius:12px;
    background:#fff;
}
.search-preview-site{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:6px;
    font-size:12px;
    color:#4b5563;
}
.search-preview-dot{
    width:24px;
    height:24px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:50%;
    background:#f1f5f9;
    font-weight:700;
    color:#334155;
    flex:0 0 auto;
}
.search-preview-domain{
    min-width:0;
}
.search-preview-domain strong{
    display:block;
    font-size:12px;
    color:#111827;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.search-preview-url{
    margin-top:1px;
    font-size:11px;
    color:#64748b;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.search-preview-title{
    margin:4px 0 5px;
    font-size:20px;
    line-height:1.3;
    font-weight:400;
    color:#1a0dab;
    word-break:break-word;
}
.search-preview-description{
    font-size:13px;
    line-height:1.5;
    color:#4b5563;
    word-break:break-word;
}
.search-preview-note{
    margin-top:8px;
    font-size:11px;
    color:#94a3b8;
}
@media (max-width: 640px){
    .search-preview{
        padding:12px;
    }
    .search-preview-box{
        padding:12px;
    }
    .search-preview-title{
        font-size:18px;
    }
}

.content-mode-toggle{
    display:inline-flex;
    padding:3px;
    border:1px solid #dbe3ec;
    border-radius:10px;
    background:#f8fafc;
    gap:3px;
}
.content-mode-option{
    position:relative;
    margin:0;
    cursor:pointer;
}
.content-mode-option input{
    position:absolute;
    opacity:0;
    pointer-events:none;
}
.content-mode-option span{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:90px;
    min-height:34px;
    padding:0 14px;
    border-radius:8px;
    color:#475569;
    font-size:12px;
    font-weight:750;
}
.content-mode-option input:checked + span{
    background:#1687e8;
    color:#fff;
}
.alias-panel,
.chapter-manager{
    padding:14px;
    border:1px solid #dbe3ec;
    border-radius:12px;
    background:#fbfdff;
}
.alias-controls,
.chapter-actions{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
    margin-top:10px;
}
.alias-count-wrap{
    display:inline-flex;
    align-items:center;
    gap:7px;
    min-height:36px;
    padding:0 9px;
    border:1px solid #dbe3ec;
    border-radius:8px;
    background:#fff;
}
.alias-count-wrap input{
    width:52px;
    min-height:28px;
    padding:3px 5px;
    border:0;
    text-align:center;
}
.alias-list,
.chapter-list-admin{
    display:grid;
    gap:8px;
    margin-top:12px;
}
.alias-row{
    display:flex;
    align-items:center;
    gap:8px;
    min-width:0;
    padding:8px 10px;
    border:1px solid #e5e7eb;
    border-radius:8px;
    background:#fff;
}
.alias-row code{
    flex:1;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    color:#334155;
    font-size:11px;
}
.alias-mini-btn{
    border:1px solid #dbe3ec;
    border-radius:7px;
    background:#fff;
    color:#475569;
    min-height:30px;
    padding:0 9px;
    font-size:10px;
    font-weight:700;
    cursor:pointer;
}
.alias-mini-btn.danger{
    color:#b91c1c;
}
.alias-status,
.chapter-status{
    margin-top:8px;
    min-height:18px;
    color:#64748b;
    font-size:11px;
}
.chapter-card{
    padding:12px;
    border:1px solid #e5e7eb;
    border-radius:10px;
    background:#fff;
}
.chapter-card-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    margin-bottom:9px;
}
.chapter-card-title{
    font-size:12px;
    font-weight:800;
}
.chapter-card input{
    margin-bottom:8px;
}
.chapter-card textarea{
    min-height:150px;
    font-family:inherit;
}
.chapter-card-actions{
    display:flex;
    gap:7px;
    flex-wrap:wrap;
    margin-top:8px;
}
.chapter-public-link{
    color:#1687e8;
    font-size:10px;
    text-decoration:none;
}


.chapter-mode-options{
    margin-top:10px;
}
.chapter-skip-row{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:12px;
    font-size:12px;
    font-weight:650;
}
.chapter-skip-row input{
    width:auto;
}
.chapter-source-label{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
}
.chapter-analyze-tools{
    display:flex;
    align-items:center;
    gap:7px;
    flex-wrap:wrap;
}
.chapter-analyze-tools input{
    width:58px;
    min-height:34px;
    padding:5px 7px;
    text-align:center;
}
.chapter-analyze-btn{
    border:0;
    border-radius:999px;
    min-height:36px;
    padding:0 15px;
    background:#f59e0b;
    color:#fff;
    font-weight:800;
    cursor:pointer;
}
.chapter-analyze-btn:disabled{
    opacity:.65;
    cursor:wait;
}
.chapter-builder{
    margin-top:18px;
    padding:16px;
    border:1px solid #dbe3ec;
    border-radius:12px;
    background:#fbfdff;
}
.chapter-builder-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
}
.chapter-builder-title{
    font-size:12px;
    font-weight:850;
    letter-spacing:.04em;
    text-transform:uppercase;
}
.chapter-builder-actions{
    display:flex;
    gap:7px;
    align-items:center;
}
.chapter-builder-actions input{
    width:58px;
    min-height:34px;
    padding:5px 7px;
    text-align:center;
}
.chapter-empty{
    padding:28px 16px;
    border:1px dashed #dbe3ec;
    border-radius:10px;
    color:#94a3b8;
    text-align:center;
    font-size:12px;
}
.chapter-editor-card{
    overflow:hidden;
    border:1px solid #dbe3ec;
    border-radius:11px;
    background:#fff;
    margin-top:10px;
}
.chapter-editor-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border-bottom:1px solid #edf1f5;
    background:#f8fafc;
}
.chapter-number{
    font-size:12px;
    font-weight:850;
}
.chapter-head-actions{
    display:flex;
    align-items:center;
    gap:6px;
}
.chapter-head-actions button{
    min-height:29px;
    padding:0 9px;
    border:1px solid #dbe3ec;
    border-radius:7px;
    background:#fff;
    color:#475569;
    font-size:10px;
    font-weight:700;
    cursor:pointer;
}
.chapter-head-actions button.danger{
    color:#b91c1c;
}
.chapter-editor-body{
    padding:12px;
}
.chapter-title-field{
    margin-bottom:9px;
}
.chapter-mini-toolbar{
    display:flex;
    flex-wrap:wrap;
    gap:5px;
    margin-bottom:7px;
}
.chapter-mini-toolbar button{
    min-width:31px;
    min-height:29px;
    padding:0 7px;
    border:1px solid #dbe3ec;
    border-radius:6px;
    background:#fff;
    font-size:10px;
    cursor:pointer;
}
.chapter-rich-editor{
    min-height:180px;
    padding:12px;
    border:1px solid #dbe3ec;
    border-radius:8px;
    background:#fff;
    line-height:1.65;
    outline:none;
}
.chapter-rich-editor:focus{
    border-color:#94a3b8;
    box-shadow:0 0 0 2px rgba(148,163,184,.13);
}
.chapter-builder-note{
    margin-top:9px;
    color:#64748b;
    font-size:11px;
    line-height:1.45;
}


.media-picker-overlay{
    position:fixed;
    inset:0;
    z-index:9999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:22px;
    background:rgba(15,23,42,.58);
}
.media-picker-overlay.open{
    display:flex;
}
.media-picker-dialog{
    width:min(1040px,96vw);
    max-height:88vh;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    background:#fff;
    border-radius:14px;
    box-shadow:0 24px 70px rgba(0,0,0,.28);
}
.media-picker-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding:14px 16px;
    border-bottom:1px solid #e5e7eb;
}
.media-picker-head h3{
    margin:0;
    font-size:17px;
}
.media-picker-close{
    border:1px solid #d1d5db;
    background:#fff;
    border-radius:7px;
    width:34px;
    height:34px;
    cursor:pointer;
}
.media-picker-tools{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    padding:12px 16px;
    border-bottom:1px solid #eef2f7;
}
.media-picker-tools input[type="search"]{
    flex:1 1 280px;
    min-height:38px;
    padding:8px 10px;
    border:1px solid #cfd4dc;
    border-radius:8px;
}
.media-picker-grid{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:10px;
    padding:14px 16px;
    overflow:auto;
}
.media-picker-item{
    padding:0;
    border:1px solid #e5e7eb;
    border-radius:9px;
    background:#fff;
    overflow:hidden;
    cursor:pointer;
    text-align:left;
}
.media-picker-item:hover{
    border-color:#7a2f22;
    box-shadow:0 0 0 2px rgba(122,47,34,.08);
}
.media-picker-item img{
    display:block;
    width:100%;
    aspect-ratio:1;
    object-fit:cover;
    background:#f3f4f6;
}
.media-picker-caption{
    padding:7px 8px;
    font-size:10px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.media-picker-empty{
    grid-column:1/-1;
    padding:36px 12px;
    text-align:center;
    color:#64748b;
}
.media-picker-foot{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding:10px 16px;
    border-top:1px solid #e5e7eb;
}
.media-picker-status{
    font-size:11px;
    color:#64748b;
}
@media(max-width:900px){
    .media-picker-grid{
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
}

</style>

<form
    id="articleForm"
    method="post"
    enctype="multipart/form-data"
    action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
>
    @csrf

    @if($article->exists)
        @method('PUT')
    @endif

    <div class="article-form-grid">

        <div>

            <div class="card">

                <div
                    class="field"
                    style="
                        padding:14px;
                        border:1px solid #dbe3ec;
                        border-radius:12px;
                        background:#f8fafc;
                        margin-bottom:18px;
                    "
                >
                    <label>
                        Import from URL
                        <span class="muted">
                            (public article pages only)
                        </span>
                    </label>

                    <div
                        style="
                            display:flex;
                            gap:8px;
                            align-items:center;
                            flex-wrap:wrap;
                        "
                    >
                        <input
                            type="url"
                            id="articleImportUrl"
                            placeholder="https://example.com/article"
                            style="
                                flex:1 1 420px;
                                min-width:220px;
                            "
                        >

                        <button
                            type="button"
                            id="articleImportBtn"
                            class="btn secondary"
                            style="width:auto"
                        >
                            ↓ Import article
                        </button>
                    </div>

                    <div
                        style="
                            display:flex;
                            flex-wrap:wrap;
                            gap:14px;
                            margin-top:10px;
                            font-size:12px;
                        "
                    >
                        <label style="display:flex;gap:6px;align-items:center">
                            <input
                                type="checkbox"
                                id="importFeaturedImage"
                                checked
                                style="width:auto"
                            >
                            Featured image
                        </label>

                        <label style="display:flex;gap:6px;align-items:center">
                            <input
                                type="checkbox"
                                id="importAutoRewrite"
                                checked
                                style="width:auto"
                            >
                            Rewrite body with AI
                        </label>

                        <label style="display:flex;gap:6px;align-items:center">
                            <input
                                type="checkbox"
                                id="importAutoSeo"
                                checked
                                style="width:auto"
                            >
                            Generate Opening + SEO
                        </label>
                    </div>

                    <input
                        type="hidden"
                        id="importedFeaturedImageUrl"
                        name="imported_featured_image_url"
                        value=""
                    >

                    <div
                        id="articleImportStatus"
                        class="muted"
                        style="
                            margin-top:8px;
                            font-size:12px;
                            line-height:1.45;
                        "
                    >
                        Imports title and clean article text. Use only content you are allowed to reuse.
                    </div>
                </div>

                <div class="field">
                    <label>Title</label>
                    <input
                        name="title"
                        value="{{ old('title', $article->title) }}"
                        required
                    >
                </div>

                <div class="field">
                    <label>
                        Slug
                        <span class="muted">(leave blank to auto-generate)</span>
                    </label>

                    <input
                        name="slug"
                        value="{{ old('slug', $article->slug) }}"
                    >
                </div>

                <div class="field">
                    <label>Content mode</label>

                    @php
                        $contentMode =
                            old(
                                'content_mode',
                                $article->content_mode
                                ?? 'normal'
                            );
                    @endphp

                    <div class="content-mode-toggle">
                        <label class="content-mode-option">
                            <input
                                type="radio"
                                name="content_mode"
                                value="normal"
                                @checked($contentMode === 'normal')
                            >
                            <span>Normal</span>
                        </label>

                        <label class="content-mode-option">
                            <input
                                type="radio"
                                name="content_mode"
                                value="chapter"
                                @checked($contentMode === 'chapter')
                            >
                            <span>Chapter</span>
                        </label>
                    </div>

                    <div
                        class="muted"
                        style="font-size:11px;margin-top:6px"
                    >
                        Chapter mode creates separate public pages with Previous / Next navigation.
                    </div>

                    <div
                        id="chapterModeOptions"
                        class="chapter-mode-options"
                        style="{{
                            $contentMode === 'chapter'
                                ? ''
                                : 'display:none'
                        }}"
                    >
                        <label class="chapter-skip-row">
                            <input
                                type="checkbox"
                                name="skip_intro"
                                value="1"
                                @checked(
                                    old(
                                        'skip_intro',
                                        $article->skip_intro
                                        ?? false
                                    )
                                )
                            >
                            <span>
                                Skip intro — go directly to Chapter 1
                            </span>
                        </label>

                        <div
                            class="muted"
                            style="font-size:11px;margin-top:5px"
                        >
                            When enabled, visitors opening the main story URL are redirected to Chapter 1.
                        </div>
                    </div>
                </div>

                <div class="field alias-panel">
                    <label>
                        Alternate slugs
                        <span class="muted">
                            — extra URLs for the same article
                        </span>
                    </label>

                    @if($article->exists)
                        <div class="alias-controls">
                            <div class="alias-count-wrap">
                                <span>QTY</span>

                                <input
                                    type="number"
                                    id="aliasGenerateCount"
                                    value="3"
                                    min="1"
                                    max="20"
                                >
                            </div>

                            <button
                                type="button"
                                id="generateAliasesBtn"
                                class="btn secondary"
                                style="width:auto"
                            >
                                ✨ Generate random
                            </button>

                            <button
                                type="button"
                                id="addAliasBtn"
                                class="btn secondary"
                                style="width:auto"
                            >
                                + Add alias
                            </button>
                        </div>

                        <div
                            id="aliasList"
                            class="alias-list"
                        >
                            @foreach($article->aliases as $alias)
                                <div
                                    class="alias-row"
                                    data-alias-id="{{ $alias->id }}"
                                    data-slug="{{ $alias->slug }}"
                                >
                                    <code>
                                        /story/{{ $alias->slug }}
                                    </code>

                                    <button
                                        type="button"
                                        class="alias-mini-btn copy-alias-btn"
                                    >
                                        Copy
                                    </button>

                                    <button
                                        type="button"
                                        class="alias-mini-btn danger delete-alias-btn"
                                    >
                                        Delete
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div
                            id="aliasStatus"
                            class="alias-status"
                        >
                            {{ $article->aliases->count() }}
                            alternate URL(s)
                        </div>
                    @else
                        <div
                            class="muted"
                            style="font-size:11px;margin-top:7px"
                        >
                            Save this article once, then Edit it to create alternate URLs.
                        </div>
                    @endif
                </div>

                <div class="field">
                    <label>Opening excerpt / deck</label>

                    <textarea id="excerptField" name="excerpt">{{ old('excerpt', $article->excerpt) }}</textarea>

                    <div
                        class="muted"
                        id="excerptCount"
                        style="font-size:12px;margin-top:5px"
                    >
                        0 words
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px">
                        <button
                            type="button"
                            id="aiGenerateBtn"
                            class="btn secondary"
                            style="width:auto"
                        >
                            ✨ Generate Opening + SEO with AI
                        </button>

                        <span
                            id="aiGenerateStatus"
                            class="muted"
                            style="font-size:12px"
                        ></span>
                    </div>
                </div>

                <div class="field">

                    <div class="chapter-source-label">
                        <label id="articleBodyLabel">
                            Article body
                        </label>

                        <div
                            id="chapterAnalyzeTools"
                            class="chapter-analyze-tools"
                            style="{{
                                $contentMode === 'chapter'
                                    ? ''
                                    : 'display:none'
                            }}"
                        >
                            <span
                                class="muted"
                                style="font-size:10px"
                            >
                                Chapters
                            </span>

                            <input
                                type="number"
                                id="chapterAiCount"
                                value="4"
                                min="2"
                                max="8"
                                title="Desired chapter count"
                            >

                            <button
                                type="button"
                                id="analyzeChaptersBtn"
                                class="chapter-analyze-btn"
                            >
                                ✨ Analyze Chapters
                            </button>
                        </div>
                    </div>

                    <div class="editorbar" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
                        <button type="button" data-cmd="undo" title="Undo">↶</button>
                        <button type="button" data-cmd="redo" title="Redo">↷</button>

                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>

                        <button type="button" id="upperBtn" title="UPPERCASE">UPPER</button>
                        <button type="button" id="lowerBtn" title="lowercase">lower</button>

                        <button type="button" data-cmd="formatBlock" data-val="h2" title="Heading 2">H2</button>
                        <button type="button" data-cmd="formatBlock" data-val="h3" title="Heading 3">H3</button>
                        <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">P</button>

                        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
                        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
                        <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Quote">Quote</button>

                        <button type="button" id="linkBtn" title="Add link">Link</button>
                        <button type="button" id="unlinkBtn" title="Remove link">Unlink</button>

                        <button type="button" id="youtubeBtn" title="Insert YouTube video">YouTube</button>
                        <button type="button" id="chooseEditorImageBtn" title="Choose image from computer">Choose Image</button>
                        <button type="button" id="openMediaLibraryBtn" title="Choose an existing image from Media Library">Media Library</button>
                        <button type="button" id="imgBtn" title="Insert image by URL">Image URL</button>
                    </div>

                    <div
                        id="editor"
                        class="editor"
                        contenteditable="true"
                    >{!! old('body', $article->body) !!}</div>

                    <input
                        type="hidden"
                        id="body"
                        name="body"
                    >

                
                    <input
                        type="file"
                        id="editorImageInput"
                        accept="image/*"
                        hidden
                    >

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:10px;
                            flex-wrap:wrap;
                            margin-top:12px;
                        "
                    >
                        <button
                            type="button"
                            id="aiRewriteBtn"
                            class="btn secondary"
                            style="width:auto"
                        >
                            ✨ Rewrite Article Uniquely with AI
                        </button>

                        <button
                            type="button"
                            id="restoreRewriteBtn"
                            class="btn secondary"
                            style="width:auto;display:none"
                        >
                            ↶ Restore original
                        </button>

                        <span
                            id="aiRewriteStatus"
                            class="muted"
                            style="font-size:12px"
                        ></span>
                    </div>

                    <div
                        class="muted"
                        style="
                            margin-top:7px;
                            font-size:11px;
                            line-height:1.45;
                        "
                    >
                        Rewrites the full body in fresh wording while preserving supported facts.
                        Nothing is saved until you click Save article.
                    </div>
</div>

                <div
                    id="chapterManager"
                    class="chapter-builder"
                    style="{{
                        $contentMode === 'chapter'
                            ? ''
                            : 'display:none'
                    }}"
                >
                    <div class="chapter-builder-head">
                        <div class="chapter-builder-title">
                            Chapters
                            (<span id="chapterCountLabel">{{ $article->exists ? $article->chapters->count() : 0 }}</span>)
                        </div>

                        <div class="chapter-builder-actions">
                            <input
                                type="number"
                                id="chapterAddCount"
                                value="1"
                                min="1"
                                max="10"
                                title="Number of chapters to add"
                            >

                            <button
                                type="button"
                                id="addChapterBtn"
                                class="btn secondary"
                                style="width:auto"
                            >
                                + Add Chapter
                            </button>
                        </div>
                    </div>

                    <input
                        type="hidden"
                        id="chaptersJson"
                        name="chapters_json"
                        value=""
                    >

                    <input
                        type="file"
                        id="chapterImageInput"
                        accept="image/*"
                        style="display:none"
                    >

                    <div
                        id="chapterListAdmin"
                        class="chapter-list-admin"
                    >
                        @if(
                            $article->exists
                            && $article->chapters->count()
                        )
                            @foreach($article->chapters as $chapter)
                                <div
                                    class="chapter-editor-card"
                                    data-chapter-id="{{ $chapter->id }}"
                                >
                                    <div class="chapter-editor-head">
                                        <div class="chapter-number">
                                            Chapter
                                            <span class="chapter-number-value">
                                                {{ $chapter->chapter_number }}
                                            </span>
                                            ·
                                            {{ number_format($chapter->views) }}
                                            views
                                        </div>

                                        <div class="chapter-head-actions">
                                            <a
                                                class="chapter-public-link"
                                                href="{{
                                                    route(
                                                        'articles.chapter',
                                                        [
                                                            'slug' =>
                                                                $article->slug,
                                                            'chapterNumber' =>
                                                                $chapter->chapter_number,
                                                        ]
                                                    )
                                                }}"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                Open ↗
                                            </a>

                                            <button
                                                type="button"
                                                class="move-chapter-up"
                                            >
                                                ↑
                                            </button>

                                            <button
                                                type="button"
                                                class="move-chapter-down"
                                            >
                                                ↓
                                            </button>

                                            <button
                                                type="button"
                                                class="danger remove-chapter-btn"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>

                                    <div class="chapter-editor-body">
                                        <input
                                            class="chapter-title-input chapter-title-field"
                                            value="{{ $chapter->title }}"
                                            placeholder="Chapter title"
                                        >

                                        <div class="chapter-mini-toolbar">
                                            <button type="button" data-chapter-cmd="bold"><b>B</b></button>
                                            <button type="button" data-chapter-cmd="italic"><i>I</i></button>
                                            <button type="button" data-chapter-cmd="underline"><u>U</u></button>
                                            <button type="button" data-chapter-cmd="formatBlock" data-chapter-val="h2">H2</button>
                                            <button type="button" data-chapter-cmd="formatBlock" data-chapter-val="h3">H3</button>
                                            <button type="button" data-chapter-cmd="formatBlock" data-chapter-val="p">P</button>
                                            <button type="button" data-chapter-cmd="insertUnorderedList">• List</button>
                                            <button type="button" data-chapter-cmd="insertOrderedList">1. List</button>
                                            <button type="button" data-chapter-cmd="formatBlock" data-chapter-val="blockquote">Quote</button>
                                            <button type="button" data-chapter-link>Link</button>
                                            <button type="button" data-chapter-youtube>YouTube</button>
                                            <button type="button" data-chapter-image>Choose Image</button>
                                            <button type="button" data-chapter-media-library>Media Library</button>
                                            <button type="button" data-chapter-image-url>Image URL</button>
                                        </div>

                                        <div
                                            class="chapter-rich-editor"
                                            contenteditable="true"
                                        >{!! $chapter->body !!}</div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div
                        id="chapterEmpty"
                        class="chapter-empty"
                        style="{{
                            $article->exists
                            && $article->chapters->count()
                                ? 'display:none'
                                : ''
                        }}"
                    >
                        No chapters yet. Click “Add Chapter” or use “Analyze Chapters”.
                    </div>

                    <div class="chapter-builder-note">
                        Each chapter becomes a separate public page with its own URL, Previous / Next navigation, view counter and ad slots. Chapters are saved together when you click Save article.
                    </div>

                    <div
                        id="chapterStatus"
                        class="chapter-status"
                    ></div>
                </div>

            </div>


            <div
                class="card"
                style="margin-top:18px"
            >

                <h3>SEO</h3>

                <div class="field">
                    <label>SEO title</label>

                    <input
                        id="seoTitleField"
                        name="seo_title"
                        maxlength="255"
                        value="{{ old('seo_title', $article->seo_title) }}"
                    >

                    <div
                        class="muted"
                        id="seoTitleCount"
                        style="font-size:12px;margin-top:5px"
                    >
                        0 characters
                    </div>
                </div>

                <div class="field">
                    <label>Meta description <span class="muted">(AI target: ~140–160 characters)</span></label>

                    <textarea
                        id="metaDescriptionField"
                        name="meta_description"
                        maxlength="320"
                    >{{ old('meta_description', $article->meta_description) }}</textarea>

                    <div
                        class="muted"
                        id="metaDescriptionCount"
                        style="font-size:12px;margin-top:5px"
                    >
                        0 characters
                    </div>
                </div>

                <div class="search-preview">
                    <div class="search-preview-label">
                        Search preview
                    </div>

                    <div class="search-preview-box">
                        <div class="search-preview-site">
                            <div class="search-preview-dot">B</div>

                            <div class="search-preview-domain">
                                <strong>Back Then Stories</strong>

                                <div
                                    class="search-preview-url"
                                    id="searchPreviewUrl"
                                ></div>
                            </div>
                        </div>

                        <div
                            class="search-preview-title"
                            id="searchPreviewTitle"
                        >
                            Article title
                        </div>

                        <div
                            class="search-preview-description"
                            id="searchPreviewDescription"
                        >
                            Add a meta description to preview how this article may appear in search results.
                        </div>
                    </div>

                    <div class="search-preview-note">
                        Preview only — Google may rewrite the title or description shown in search results.
                    </div>
                </div>
</div>

        </div>


        <aside>

            <div class="card">

                <div class="field">

                    <label>Site</label>

                    <select
                        name="site_id"
                        required
                    >

                        @foreach($sites as $s)

                            <option
                                value="{{ $s->id }}"
                                @selected(old('site_id', $article->site_id) == $s->id)
                            >
                                {{ $s->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="field">

                    <label>Status</label>

                    <select name="status">

                        @foreach(['draft','review','scheduled','published'] as $s)

                            <option
                                value="{{ $s }}"
                                @selected(old('status', $article->status) === $s)
                            >
                                {{ ucfirst($s) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="field">

                    <label>Publish at</label>

                    <input
                        type="datetime-local"
                        name="published_at"
                        value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\TH:i')) }}"
                    >

                </div>


                <label>

                    <input
                        type="checkbox"
                        name="featured"
                        value="1"
                        @checked(old('featured', $article->featured))
                    >

                    Featured story

                </label>


                <hr style="border:0;border-top:1px solid #eee;margin:18px 0">


                <div class="field">

                    <label>Artist</label>

                    <select name="artist_id">

                        <option value="">
                            — none —
                        </option>

                        @foreach($artists as $a)

                            <option
                                value="{{ $a->id }}"
                                @selected(old('artist_id', $article->artist_id) == $a->id)
                            >
                                {{ $a->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="field">

                    <label>Category</label>

                    <select name="category_id">

                        <option value="">
                            — none —
                        </option>

                        @foreach($categories as $c)

                            <option
                                value="{{ $c->id }}"
                                @selected(old('category_id', $article->category_id) == $c->id)
                            >
                                {{ $c->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="field">

                    <label>Featured image</label>

                    <div
                        id="featuredImagePreviewWrap"
                        style="
                            {{ $article->featured_image ? '' : 'display:none;' }}
                            margin-bottom:12px;
                            border:1px solid #e5e7eb;
                            border-radius:10px;
                            padding:8px;
                            background:#f8fafc;
                        "
                    >

                        <img
                            id="featuredImagePreview"
                            src="{{ $article->featured_image ? asset('storage/'.$article->featured_image) : '' }}"
                            alt="Featured image preview"
                            style="
                                display:block;
                                width:100%;
                                max-height:340px;
                                object-fit:contain;
                                border-radius:8px;
                            "
                        >

                    </div>


                    <input
                        type="hidden"
                        id="featuredMediaId"
                        name="featured_media_id"
                        value=""
                    >

                    <div
                        style="
                            display:flex;
                            gap:8px;
                            align-items:center;
                            flex-wrap:wrap;
                        "
                    >
                        <input
                            id="featuredImageInput"
                            type="file"
                            name="featured_image_file"
                            accept="image/*"
                            style="
                                flex:1 1 220px;
                                min-width:180px;
                            "
                        >

                        <button
                            type="button"
                            id="featuredMediaLibraryBtn"
                            class="btn secondary"
                            style="width:auto"
                        >
                            Media Library
                        </button>
                    </div>


                    <div
                        id="featuredImageName"
                        class="muted"
                        style="
                            margin-top:8px;
                            font-size:12px;
                            word-break:break-word;
                        "
                    ></div>

                </div>


                <button
                    class="btn"
                    style="width:100%"
                >
                    Save article
                </button>


                @if($article->exists)

                    <p>

                        <a
                            class="btn secondary"
                            style="width:100%"
                            target="_blank"
                            href="{{ route('admin.articles.preview', $article) }}"
                        >
                            Preview ↗
                        </a>

                    </p>

                @endif

            </div>

        </aside>

    </div>

</form>


@if($article->exists)

    <form
        method="post"
        action="{{ route('admin.articles.destroy', $article) }}"
        onsubmit="return confirm('Move this article to Trash?')"
        style="margin-top:18px"
    >

        @csrf
        @method('DELETE')

        <button class="btn danger">
            Delete article
        </button>

    </form>

@endif

@endsection



<div
    id="mediaPickerOverlay"
    class="media-picker-overlay"
    aria-hidden="true"
>
    <div
        class="media-picker-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mediaPickerTitle"
    >
        <div class="media-picker-head">
            <h3 id="mediaPickerTitle">
                Media Library
            </h3>

            <button
                type="button"
                id="mediaPickerClose"
                class="media-picker-close"
                aria-label="Close"
            >
                ×
            </button>
        </div>

        <div class="media-picker-tools">
            <input
                type="search"
                id="mediaPickerSearch"
                placeholder="Search filename or alt text..."
            >

            <button
                type="button"
                id="mediaPickerSearchBtn"
                class="btn secondary"
            >
                Search
            </button>

            <button
                type="button"
                id="mediaPickerUploadBtn"
                class="btn"
            >
                Upload new image
            </button>

            <input
                type="file"
                id="mediaPickerUploadInput"
                accept="image/*"
                style="display:none"
            >
        </div>

        <div
            id="mediaPickerGrid"
            class="media-picker-grid"
        ></div>

        <div class="media-picker-foot">
            <div
                id="mediaPickerStatus"
                class="media-picker-status"
            ></div>

            <button
                type="button"
                id="mediaPickerLoadMore"
                class="btn secondary"
                style="display:none"
            >
                Load more
            </button>
        </div>
    </div>
</div>

@push('scripts')

<script>
const ed = document.getElementById('editor');
const body = document.getElementById('body');
const articleForm = document.getElementById('articleForm');

let savedRange = null;

function syncEditor() {
    body.value = ed.innerHTML;
}

function saveSelection() {
    const selection = window.getSelection();

    if (
        selection &&
        selection.rangeCount > 0 &&
        ed.contains(selection.anchorNode)
    ) {
        savedRange = selection.getRangeAt(0).cloneRange();
    }
}

function restoreSelection() {
    ed.focus();

    if (!savedRange) {
        return;
    }

    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(savedRange);
}

function bindKeepSelection(button) {
    button.addEventListener('mousedown', function (event) {
        event.preventDefault();
        saveSelection();
    });
}

ed.addEventListener('keyup', saveSelection);
ed.addEventListener('mouseup', saveSelection);
ed.addEventListener('focus', saveSelection);
ed.addEventListener('input', function () {
    saveSelection();
    syncEditor();
});

/* Standard editor commands */
document.querySelectorAll('[data-cmd]').forEach(function (button) {
    bindKeepSelection(button);

    button.addEventListener('click', function () {
        restoreSelection();

        document.execCommand(
            this.dataset.cmd,
            false,
            this.dataset.val || null
        );

        saveSelection();
        syncEditor();
    });
});

/* UPPERCASE / lowercase */
function changeSelectionCase(mode) {
    restoreSelection();

    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);

    if (range.collapsed) {
        alert('Select the text you want to change first.');
        return;
    }

    const fragment = range.cloneContents();
    const walker = document.createTreeWalker(
        fragment,
        NodeFilter.SHOW_TEXT
    );

    let node;

    while ((node = walker.nextNode())) {
        node.nodeValue = mode === 'upper'
            ? node.nodeValue.toUpperCase()
            : node.nodeValue.toLowerCase();
    }

    const marker = document.createElement('span');
    marker.setAttribute('data-case-marker', '1');
    fragment.appendChild(marker);

    range.deleteContents();
    range.insertNode(fragment);

    const newRange = document.createRange();
    newRange.setStartAfter(marker);
    newRange.collapse(true);

    marker.remove();

    selection.removeAllRanges();
    selection.addRange(newRange);

    savedRange = newRange.cloneRange();

    syncEditor();
}

const upperBtn = document.getElementById('upperBtn');
const lowerBtn = document.getElementById('lowerBtn');

bindKeepSelection(upperBtn);
bindKeepSelection(lowerBtn);

upperBtn.addEventListener('click', function () {
    changeSelectionCase('upper');
});

lowerBtn.addEventListener('click', function () {
    changeSelectionCase('lower');
});

/* Link / unlink */
const linkBtn = document.getElementById('linkBtn');
const unlinkBtn = document.getElementById('unlinkBtn');

bindKeepSelection(linkBtn);
bindKeepSelection(unlinkBtn);

linkBtn.addEventListener('click', function () {
    const url = prompt('Paste link URL');

    if (!url) {
        return;
    }

    restoreSelection();
    document.execCommand('createLink', false, url);

    saveSelection();
    syncEditor();
});

unlinkBtn.addEventListener('click', function () {
    restoreSelection();
    document.execCommand('unlink', false, null);

    saveSelection();
    syncEditor();
});

/* Image by URL */
const imgBtn = document.getElementById('imgBtn');

bindKeepSelection(imgBtn);

imgBtn.addEventListener('click', function () {
    const url = prompt(
        'Image URL (upload the image in Media and paste its URL here)'
    );

    if (!url) {
        return;
    }

    restoreSelection();

    const safeUrl = String(url).replace(/"/g, '&quot;');

    document.execCommand(
        'insertHTML',
        false,
        '<p><img src="' + safeUrl + '" alt="" style="max-width:100%;height:auto;display:block;margin:20px auto;border-radius:8px"></p><p><br></p>'
    );

    saveSelection();
    syncEditor();
});

/* Choose image from computer */
const chooseEditorImageBtn =
    document.getElementById('chooseEditorImageBtn');

const editorImageInput =
    document.getElementById('editorImageInput');

bindKeepSelection(chooseEditorImageBtn);

chooseEditorImageBtn.addEventListener('click', function () {
    editorImageInput.value = '';
    editorImageInput.click();
});

async function uploadImageToMediaLibrary(file) {
    if (!file) {
        throw new Error(
            'No image selected.'
        );
    }

    if (
        !file.type.startsWith(
            'image/'
        )
    ) {
        throw new Error(
            'Please select an image file.'
        );
    }

    if (
        file.size
        > 8 * 1024 * 1024
    ) {
        throw new Error(
            'Please choose an image smaller than 8 MB.'
        );
    }

    const formData =
        new FormData();

    formData.append(
        'file',
        file
    );

    const response =
        await fetch(
            @json(
                route(
                    'admin.media.inline-store'
                )
            ),
            {
                method: 'POST',
                headers: {
                    'Accept':
                        'application/json',
                    'X-CSRF-TOKEN':
                        @json(
                            csrf_token()
                        )
                },
                body:
                    formData
            }
        );

    let data = {};

    try {
        data =
            await response.json();
    } catch (e) {
        data = {};
    }

    if (!response.ok) {
        throw new Error(
            data.message
            || 'Image upload failed.'
        );
    }

    if (!data.item?.url) {
        throw new Error(
            'The uploaded image URL was not returned.'
        );
    }

    return data.item;
}

editorImageInput.addEventListener(
    'change',
    async function () {
        const file =
            this.files
            && this.files[0];

        if (!file) {
            return;
        }

        const oldText =
            chooseEditorImageBtn
                .textContent;

        chooseEditorImageBtn.disabled =
            true;

        chooseEditorImageBtn.textContent =
            'Uploading…';

        try {
            const item =
                await uploadImageToMediaLibrary(
                    file
                );

            restoreSelection();

            const safeUrl =
                String(
                    item.url
                ).replace(
                    /"/g,
                    '&quot;'
                );

            const safeAlt =
                String(
                    item.alt_text
                    || ''
                ).replace(
                    /"/g,
                    '&quot;'
                );

            document.execCommand(
                'insertHTML',
                false,
                '<p><img src="'
                + safeUrl
                + '" alt="'
                + safeAlt
                + '" style="max-width:100%;height:auto;display:block;margin:20px auto;border-radius:8px"></p><p><br></p>'
            );

            saveSelection();
            syncEditor();

        } catch (error) {
            alert(
                error.message
                || 'Could not upload this image.'
            );

        } finally {
            chooseEditorImageBtn.disabled =
                false;

            chooseEditorImageBtn.textContent =
                oldText;

            editorImageInput.value =
                '';
        }
    }
);

/* YouTube embed */
const youtubeBtn = document.getElementById('youtubeBtn');

bindKeepSelection(youtubeBtn);

function getYouTubeVideoId(url) {
    const value = String(url || '').trim();

    const patterns = [
        /youtu\.be\/([a-zA-Z0-9_-]{6,})/,
        /youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{6,})/,
        /youtube\.com\/shorts\/([a-zA-Z0-9_-]{6,})/,
        /youtube\.com\/embed\/([a-zA-Z0-9_-]{6,})/
    ];

    for (const pattern of patterns) {
        const match = value.match(pattern);

        if (match && match[1]) {
            return match[1];
        }
    }

    return null;
}

youtubeBtn.addEventListener('click', function () {
    const url = prompt(
        'Paste a YouTube URL (watch, youtu.be, Shorts, or embed URL)'
    );

    if (!url) {
        return;
    }

    const videoId = getYouTubeVideoId(url);

    if (!videoId) {
        alert('This does not look like a valid YouTube URL.');
        return;
    }

    restoreSelection();

    const html =
        '<div style="position:relative;width:100%;max-width:760px;padding-bottom:56.25%;height:0;overflow:hidden;margin:24px auto;border-radius:10px;background:#000">' +
            '<iframe ' +
                'src="https://www.youtube.com/embed/' + videoId + '" ' +
                'title="YouTube video" ' +
                'loading="lazy" ' +
                'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ' +
                'allowfullscreen ' +
                'style="position:absolute;top:0;left:0;width:100%;height:100%;border:0">' +
            '</iframe>' +
        '</div><p><br></p>';

    document.execCommand(
        'insertHTML',
        false,
        html
    );

    saveSelection();
    syncEditor();
});

/* Form submit */
articleForm.addEventListener('submit', function () {
    syncEditor();

    if (
        typeof syncChaptersJson
        === 'function'
    ) {
        syncChaptersJson();
    }
});

syncEditor();


/* =========================
   IMPORT ARTICLE FROM URL
========================= */
const articleImportUrl =
    document.getElementById('articleImportUrl');

const articleImportBtn =
    document.getElementById('articleImportBtn');

const articleImportStatus =
    document.getElementById('articleImportStatus');

const importFeaturedImage =
    document.getElementById('importFeaturedImage');

const importAutoRewrite =
    document.getElementById('importAutoRewrite');

const importAutoSeo =
    document.getElementById('importAutoSeo');

const importedFeaturedImageUrl =
    document.getElementById('importedFeaturedImageUrl');

function setImportStatus(
    message,
    isError = false
) {
    articleImportStatus.textContent =
        message;

    articleImportStatus.style.color =
        isError ? '#b91c1c' : '#64748b';
}

articleImportBtn.addEventListener(
    'click',
    async function () {
        const sourceUrl =
            (articleImportUrl.value || '')
                .trim();

        if (!sourceUrl) {
            alert(
                'Paste the public article URL first.'
            );

            articleImportUrl.focus();

            return;
        }

        const currentTitle =
            (
                articleForm.elements
                    .namedItem('title')
                    ?.value
                || ''
            ).trim();

        const currentBody =
            (ed.innerText || '')
                .trim();

        if (
            (currentTitle || currentBody) &&
            !confirm(
                'Importing will replace the current Title and Article body. Continue?'
            )
        ) {
            return;
        }

        const oldLabel =
            articleImportBtn.textContent;

        articleImportBtn.disabled = true;
        articleImportBtn.textContent =
            'Importing…';

        setImportStatus(
            'Fetching and cleaning the source article…'
        );

        try {
            const response = await fetch(
                @json(route('admin.articles.import-url')),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/json',
                        'Accept':
                            'application/json',
                        'X-CSRF-TOKEN':
                            @json(csrf_token())
                    },
                    body: JSON.stringify({
                        source_url: sourceUrl
                    })
                }
            );

            let data = {};

            try {
                data =
                    await response.json();
            } catch (e) {
                data = {};
            }

            if (!response.ok) {
                throw new Error(
                    data.message ||
                    'Could not import this article.'
                );
            }

            const titleField =
                articleForm.elements
                    .namedItem('title');

            titleField.value =
                data.title || '';

            titleField.dispatchEvent(
                new Event(
                    'input',
                    { bubbles: true }
                )
            );

            ed.innerHTML =
                data.body || '';

            syncEditor();

            /*
             * Imported main image is only downloaded when Save article
             * is clicked. This avoids orphan image files when the user
             * abandons an imported draft.
             */
            if (
                importFeaturedImage.checked &&
                data.featured_image_url
            ) {
                importedFeaturedImageUrl.value =
                    data.featured_image_url;

                featuredImagePreview.src =
                    data.featured_image_url;

                featuredImagePreviewWrap.style.display =
                    'block';

                featuredImageName.textContent =
                    'Imported featured image — will be copied to this site when you save.';
            } else {
                importedFeaturedImageUrl.value =
                    '';
            }

            setImportStatus(
                'Imported from '
                + (data.source_host || 'source')
                + '.'
            );

            let rewriteOk = true;

            if (importAutoRewrite.checked) {
                setImportStatus(
                    'Article imported. AI is now rewriting the body…'
                );

                rewriteOk =
                    await runArticleRewrite(
                        true
                    );
            }

            if (
                rewriteOk &&
                importAutoSeo.checked
            ) {
                setImportStatus(
                    'Body ready. AI is generating Opening + SEO…'
                );

                await runAiOpeningSeo();
            }

            setImportStatus(
                'Import complete. Review everything, choose Site/Category/Status, then Save article.'
            );

        } catch (error) {
            console.error(error);

            setImportStatus(
                error.message ||
                'Article import failed.',
                true
            );

        } finally {
            articleImportBtn.disabled = false;
            articleImportBtn.textContent =
                oldLabel;
        }
    }
);


/* =========================
   AI FULL ARTICLE REWRITE
========================= */
const aiRewriteBtn =
    document.getElementById('aiRewriteBtn');

const restoreRewriteBtn =
    document.getElementById('restoreRewriteBtn');

const aiRewriteStatus =
    document.getElementById('aiRewriteStatus');

let lastBodyBeforeAiRewrite = null;

function setRewriteStatus(
    message,
    isError = false
) {
    aiRewriteStatus.textContent = message;

    aiRewriteStatus.style.color =
        isError ? '#b91c1c' : '#64748b';
}

restoreRewriteBtn.addEventListener(
    'click',
    function () {
        if (lastBodyBeforeAiRewrite === null) {
            return;
        }

        ed.innerHTML =
            lastBodyBeforeAiRewrite;

        syncEditor();

        lastBodyBeforeAiRewrite = null;

        restoreRewriteBtn.style.display =
            'none';

        setRewriteStatus(
            'Original article body restored.'
        );
    }
);

async function runArticleRewrite(
    skipConfirm = false
) {
    const titleField =
        articleForm.elements.namedItem(
            'title'
        );

    const title =
        (titleField?.value || '')
            .trim();

    const articleText =
        (ed.innerText || '')
            .trim();

    const articleHtml =
        ed.innerHTML.trim();

    if (!title) {
        alert(
            'Please enter the article title first.'
        );

        titleField?.focus();

        return false;
    }

    if (articleText.length < 200) {
        alert(
            'Please add a fuller article body before rewriting.'
        );

        ed.focus();

        return false;
    }

    if (!skipConfirm) {
        const confirmed = confirm(
            'AI will replace the current Article body with a rewritten version. ' +
            'You can restore the original before saving. Continue?'
        );

        if (!confirmed) {
            return false;
        }
    }

    const oldLabel =
        aiRewriteBtn.textContent;

    const originalHtml =
        articleHtml;

    aiRewriteBtn.disabled = true;

    if (aiGenerateBtn) {
        aiGenerateBtn.disabled = true;
    }

    aiRewriteBtn.textContent =
        'Rewriting…';

    setRewriteStatus(
        'AI is rewriting the full article. Please wait…'
    );

    try {
        const response = await fetch(
            @json(route('admin.articles.ai-generate')),
            {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/json',
                    'Accept':
                        'application/json',
                    'X-CSRF-TOKEN':
                        @json(csrf_token())
                },
                body: JSON.stringify({
                    mode: 'rewrite',
                    title: title,
                    body: articleHtml
                })
            }
        );

        let data = {};

        try {
            data =
                await response.json();
        } catch (e) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                'AI rewrite failed. Please try again.'
            );
        }

        if (!data.rewritten_body) {
            throw new Error(
                'AI returned an empty rewritten article.'
            );
        }

        lastBodyBeforeAiRewrite =
            originalHtml;

        ed.innerHTML =
            data.rewritten_body;

        syncEditor();

        restoreRewriteBtn.style.display =
            'inline-flex';

        const rewrittenWords =
            (ed.innerText || '')
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .length;

        setRewriteStatus(
            'Rewrite complete — '
            + rewrittenWords
            + ' words. Review it, then generate Opening + SEO or save.'
        );

        ed.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        return true;

    } catch (error) {
        console.error(error);

        ed.innerHTML =
            originalHtml;

        syncEditor();

        setRewriteStatus(
            error.message ||
            'AI rewrite failed. Please try again.',
            true
        );

        return false;

    } finally {
        aiRewriteBtn.disabled = false;

        aiRewriteBtn.textContent =
            oldLabel;

        if (aiGenerateBtn) {
            aiGenerateBtn.disabled = false;
        }
    }
}

aiRewriteBtn.addEventListener(
    'click',
    function () {
        runArticleRewrite(false);
    }
);


/* =========================
   AI OPENING + SEO — GEMINI
========================= */
const aiGenerateBtn =
    document.getElementById('aiGenerateBtn');

const aiGenerateStatus =
    document.getElementById('aiGenerateStatus');

const excerptField =
    document.getElementById('excerptField');

const seoTitleField =
    document.getElementById('seoTitleField');

const metaDescriptionField =
    document.getElementById('metaDescriptionField');

function setAiStatus(message, isError = false) {
    aiGenerateStatus.textContent = message;
    aiGenerateStatus.style.color =
        isError ? '#b91c1c' : '#64748b';
}

async function runAiOpeningSeo() {
    const titleField =
        articleForm.elements.namedItem('title');

    const title =
        (titleField?.value || '').trim();

    const articleText =
        (ed.innerText || '').trim();

    if (!title) {
        alert('Please enter the article title first.');
        titleField?.focus();
        return false;
    }

    if (articleText.length < 80) {
        alert(
            'Please add more article body text before using AI.'
        );
        ed.focus();
        return false;
    }

    const oldLabel = aiGenerateBtn.textContent;

    aiGenerateBtn.disabled = true;
    aiGenerateBtn.textContent = 'Generating…';
    setAiStatus(
        'AI is preparing the opening and SEO fields…'
    );

    try {
        const response = await fetch(
            @json(route('admin.articles.ai-generate')),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify({
                    mode: 'seo',
                    title: title,
                    body: articleText
                })
            }
        );

        let data = {};

        try {
            data = await response.json();
        } catch (e) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                'AI request failed. Please try again.'
            );
        }

        excerptField.value =
            data.opening_excerpt || '';

        seoTitleField.value =
            data.seo_title || '';

        metaDescriptionField.value =
            data.meta_description || '';

        updateEditorialCounters();
        updateSearchPreview();

        setAiStatus(
            'AI suggestions added. Review them before saving.'
        );

        return true;

    } catch (error) {
        console.error(error);

        setAiStatus(
            error.message ||
            'AI request failed. Please try again.',
            true
        );

        return false;

    } finally {
        aiGenerateBtn.disabled = false;
        aiGenerateBtn.textContent = oldLabel;
    }
}

aiGenerateBtn.addEventListener(
    'click',
    function () {
        runAiOpeningSeo();
    }
);


/* =========================
   EDITORIAL LENGTH HELPERS
========================= */
const excerptCount =
    document.getElementById('excerptCount');

const seoTitleCount =
    document.getElementById('seoTitleCount');

const metaDescriptionCount =
    document.getElementById('metaDescriptionCount');

function countWords(value) {
    const clean = String(value || '').trim();

    if (!clean) {
        return 0;
    }

    return clean.split(/\s+/).length;
}

function updateEditorialCounters() {
    const openingWords =
        countWords(excerptField.value);

    const seoChars =
        seoTitleField.value.length;

    const metaChars =
        metaDescriptionField.value.length;

    excerptCount.textContent =
        openingWords + ' words' +
        (
            openingWords >= 35 && openingWords <= 55
                ? ' ✓'
                : ' — target 35–55'
        );

    seoTitleCount.textContent =
        seoChars + ' characters' +
        (
            seoChars >= 40 && seoChars <= 70
                ? ' ✓'
                : ' — keep concise'
        );

    metaDescriptionCount.textContent =
        metaChars + ' characters' +
        (
            metaChars >= 140 && metaChars <= 160
                ? ' ✓'
                : ' — target 140–160'
        );
}

[
    excerptField,
    seoTitleField,
    metaDescriptionField
].forEach(function (field) {
    field.addEventListener(
        'input',
        updateEditorialCounters
    );
});

updateEditorialCounters();


/* =========================
   MEDIA LIBRARY PICKER
========================= */
const openMediaLibraryBtn =
    document.getElementById(
        'openMediaLibraryBtn'
    );

const mediaPickerOverlay =
    document.getElementById(
        'mediaPickerOverlay'
    );

const mediaPickerClose =
    document.getElementById(
        'mediaPickerClose'
    );

const mediaPickerSearch =
    document.getElementById(
        'mediaPickerSearch'
    );

const mediaPickerSearchBtn =
    document.getElementById(
        'mediaPickerSearchBtn'
    );

const mediaPickerUploadBtn =
    document.getElementById(
        'mediaPickerUploadBtn'
    );

const mediaPickerUploadInput =
    document.getElementById(
        'mediaPickerUploadInput'
    );

const mediaPickerGrid =
    document.getElementById(
        'mediaPickerGrid'
    );

const mediaPickerStatus =
    document.getElementById(
        'mediaPickerStatus'
    );

const mediaPickerLoadMore =
    document.getElementById(
        'mediaPickerLoadMore'
    );

const mediaLibraryUrl =
    @json(
        route(
            'admin.media.library'
        )
    );

let mediaPickerPage = 1;
let mediaPickerHasMore = false;
let mediaPickerTarget = null;

function setMediaPickerStatus(text) {
    if (mediaPickerStatus) {
        mediaPickerStatus.textContent =
            text || '';
    }
}

function escapeMediaHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderMediaPickerItems(
    items,
    append = false
) {
    if (!mediaPickerGrid) {
        return;
    }

    if (!append) {
        mediaPickerGrid.innerHTML =
            '';
    }

    if (
        !items.length
        && !append
    ) {
        mediaPickerGrid.innerHTML =
            '<div class="media-picker-empty">'
            + 'No images found.'
            + '</div>';

        return;
    }

    items.forEach(
        function (item) {
            const button =
                document.createElement(
                    'button'
                );

            button.type =
                'button';

            button.className =
                'media-picker-item';

            button.dataset.id =
                item.id;

            button.dataset.url =
                item.url;

            button.dataset.alt =
                item.alt_text || '';

            button.innerHTML =
                '<img loading="lazy" src="'
                + escapeMediaHtml(
                    item.url
                )
                + '" alt="">'
                + '<div class="media-picker-caption">'
                + escapeMediaHtml(
                    item.filename
                    || 'Image'
                )
                + '</div>';

            mediaPickerGrid.appendChild(
                button
            );
        }
    );
}

async function loadMediaPicker(
    page = 1,
    append = false
) {
    if (!mediaPickerGrid) {
        return;
    }

    setMediaPickerStatus(
        'Loading…'
    );

    const params =
        new URLSearchParams();

    params.set(
        'page',
        page
    );

    const q =
        (
            mediaPickerSearch?.value
            || ''
        ).trim();

    if (q) {
        params.set(
            'q',
            q
        );
    }

    try {
        const response =
            await fetch(
                mediaLibraryUrl
                + '?'
                + params.toString(),
                {
                    headers: {
                        'Accept':
                            'application/json'
                    }
                }
            );

        const data =
            await response.json();

        if (!response.ok) {
            throw new Error(
                data.message
                || 'Could not load Media Library.'
            );
        }

        renderMediaPickerItems(
            data.items || [],
            append
        );

        mediaPickerPage =
            data.current_page || page;

        mediaPickerHasMore =
            !!data.has_more;

        if (mediaPickerLoadMore) {
            mediaPickerLoadMore.style.display =
                mediaPickerHasMore
                    ? ''
                    : 'none';
        }

        setMediaPickerStatus(
            (data.items || []).length
            + ' image(s) loaded'
        );

    } catch (error) {
        setMediaPickerStatus(
            error.message
            || 'Could not load Media Library.'
        );
    }
}

function openMediaPicker(
    type,
    editor = null
) {
    mediaPickerTarget = {
        type: type,
        editor: editor
    };

    mediaPickerOverlay
        ?.classList
        .add('open');

    mediaPickerOverlay
        ?.setAttribute(
            'aria-hidden',
            'false'
        );

    loadMediaPicker(
        1,
        false
    );
}

function closeMediaPicker() {
    mediaPickerOverlay
        ?.classList
        .remove('open');

    mediaPickerOverlay
        ?.setAttribute(
            'aria-hidden',
            'true'
        );
}

openMediaLibraryBtn?.addEventListener(
    'click',
    function () {
        saveSelection();

        openMediaPicker(
            'main'
        );
    }
);

mediaPickerClose?.addEventListener(
    'click',
    closeMediaPicker
);

mediaPickerOverlay?.addEventListener(
    'click',
    function (event) {
        if (
            event.target
            === mediaPickerOverlay
        ) {
            closeMediaPicker();
        }
    }
);

mediaPickerSearchBtn?.addEventListener(
    'click',
    function () {
        loadMediaPicker(
            1,
            false
        );
    }
);

mediaPickerSearch?.addEventListener(
    'keydown',
    function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();

            loadMediaPicker(
                1,
                false
            );
        }
    }
);

mediaPickerLoadMore?.addEventListener(
    'click',
    function () {
        if (!mediaPickerHasMore) {
            return;
        }

        loadMediaPicker(
            mediaPickerPage + 1,
            true
        );
    }
);

mediaPickerGrid?.addEventListener(
    'click',
    function (event) {
        const item =
            event.target.closest(
                '.media-picker-item'
            );

        if (
            !item
            || !mediaPickerTarget
        ) {
            return;
        }

        const url =
            item.dataset.url;

        const alt =
            item.dataset.alt || '';

        if (
            mediaPickerTarget.type
            === 'chapter'
            && mediaPickerTarget.editor
        ) {
            insertHtmlAtChapterCursor(
                mediaPickerTarget.editor,
                chapterImageHtml(
                    url,
                    alt
                )
            );

            setChapterStatus(
                'Media Library image inserted.'
            );

        } else if (
            mediaPickerTarget.type
            === 'featured'
        ) {
            const selectedId =
                item.dataset.id;

            if (featuredMediaId) {
                featuredMediaId.value =
                    selectedId || '';
            }

            if (featuredImageInput) {
                featuredImageInput.value =
                    '';
            }

            const importedField =
                document.getElementById(
                    'importedFeaturedImageUrl'
                );

            if (importedField) {
                importedField.value =
                    '';
            }

            if (featuredImageObjectUrl) {
                URL.revokeObjectURL(
                    featuredImageObjectUrl
                );

                featuredImageObjectUrl =
                    null;
            }

            featuredImagePreview.src =
                url;

            featuredImagePreviewWrap
                .style
                .display =
                'block';

            featuredImageName.textContent =
                'Media Library'
                + (
                    alt
                        ? ' • ' + alt
                        : ''
                );

        } else {
            restoreSelection();

            const safeUrl =
                String(url)
                    .replace(
                        /"/g,
                        '&quot;'
                    );

            const safeAlt =
                String(alt)
                    .replace(
                        /"/g,
                        '&quot;'
                    );

            document.execCommand(
                'insertHTML',
                false,
                '<p><img src="'
                + safeUrl
                + '" alt="'
                + safeAlt
                + '" style="max-width:100%;height:auto;display:block;margin:20px auto;border-radius:8px"></p><p><br></p>'
            );

            saveSelection();
            syncEditor();
        }

        closeMediaPicker();
    }
);

mediaPickerUploadBtn?.addEventListener(
    'click',
    function () {
        mediaPickerUploadInput.value =
            '';

        mediaPickerUploadInput.click();
    }
);

mediaPickerUploadInput?.addEventListener(
    'change',
    async function () {
        const file =
            this.files?.[0];

        if (!file) {
            return;
        }

        const oldText =
            mediaPickerUploadBtn.textContent;

        mediaPickerUploadBtn.disabled =
            true;

        mediaPickerUploadBtn.textContent =
            'Uploading…';

        try {
            const item =
                await uploadImageToMediaLibrary(
                    file
                );

            renderMediaPickerItems(
                [item],
                false
            );

            setMediaPickerStatus(
                'Uploaded. Click the image to insert it.'
            );

        } catch (error) {
            setMediaPickerStatus(
                error.message
                || 'Image upload failed.'
            );

        } finally {
            mediaPickerUploadBtn.disabled =
                false;

            mediaPickerUploadBtn.textContent =
                oldText;

            this.value = '';
        }
    }
);

document.addEventListener(
    'keydown',
    function (event) {
        if (
            event.key === 'Escape'
            && mediaPickerOverlay
                ?.classList
                .contains('open')
        ) {
            closeMediaPicker();
        }
    }
);


/* =========================
   CONTENT MODE / ALIASES / CHAPTER BUILDER
========================= */
const contentModeInputs =
    document.querySelectorAll(
        'input[name="content_mode"]'
    );

const chapterManager =
    document.getElementById(
        'chapterManager'
    );

const chapterModeOptions =
    document.getElementById(
        'chapterModeOptions'
    );

const chapterAnalyzeTools =
    document.getElementById(
        'chapterAnalyzeTools'
    );

const articleBodyLabel =
    document.getElementById(
        'articleBodyLabel'
    );

function refreshContentModeUi() {
    const selected =
        document.querySelector(
            'input[name="content_mode"]:checked'
        );

    const isChapter =
        selected?.value === 'chapter';

    if (chapterManager) {
        chapterManager.style.display =
            isChapter ? '' : 'none';
    }

    if (chapterModeOptions) {
        chapterModeOptions.style.display =
            isChapter ? '' : 'none';
    }

    if (chapterAnalyzeTools) {
        chapterAnalyzeTools.style.display =
            isChapter ? '' : 'none';
    }

    if (articleBodyLabel) {
        articleBodyLabel.textContent =
            isChapter
                ? 'INTRO / DESCRIPTION'
                : 'Article body';
    }
}

contentModeInputs.forEach(
    function (input) {
        input.addEventListener(
            'change',
            refreshContentModeUi
        );
    }
);

refreshContentModeUi();


/* =========================
   ALTERNATE SLUGS
========================= */
@if($article->exists)

const aliasList =
    document.getElementById('aliasList');

const aliasStatus =
    document.getElementById('aliasStatus');

const aliasGenerateCount =
    document.getElementById(
        'aliasGenerateCount'
    );

const generateAliasesBtn =
    document.getElementById(
        'generateAliasesBtn'
    );

const addAliasBtn =
    document.getElementById(
        'addAliasBtn'
    );

const aliasGenerateUrl =
    {!! json_encode(
        route(
            'admin.articles.aliases.generate',
            $article
        )
    ) !!};

const aliasStoreUrl =
    {!! json_encode(
        route(
            'admin.articles.aliases.store',
            $article
        )
    ) !!};

const aliasDestroyTemplate =
    {!! json_encode(
        url(
            '/admin/articles/'
            . $article->id
            . '/aliases/__ALIAS__'
        )
    ) !!};

async function uiCopyText(text) {
    if (
        navigator.clipboard &&
        window.isSecureContext
    ) {
        await navigator.clipboard
            .writeText(text);

        return;
    }

    const textarea =
        document.createElement(
            'textarea'
        );

    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';

    document.body.appendChild(
        textarea
    );

    textarea.select();
    document.execCommand('copy');
    textarea.remove();
}

async function adminJsonRequest(
    url,
    method,
    payload = null
) {
    const options = {
        method: method,
        headers: {
            'Accept':
                'application/json',
            'X-CSRF-TOKEN':
                @json(csrf_token())
        }
    };

    if (payload !== null) {
        options.headers[
            'Content-Type'
        ] = 'application/json';

        options.body =
            JSON.stringify(
                payload
            );
    }

    const response =
        await fetch(
            url,
            options
        );

    let data = {};

    try {
        data =
            await response.json();
    } catch (e) {
        data = {};
    }

    if (!response.ok) {
        throw new Error(
            data.message
            || 'Request failed.'
        );
    }

    return data;
}

function setAliasStatus(
    message,
    isError = false
) {
    if (!aliasStatus) {
        return;
    }

    aliasStatus.textContent =
        message;

    aliasStatus.style.color =
        isError
            ? '#b91c1c'
            : '#64748b';
}

function renderAliases(aliases) {
    if (!aliasList) {
        return;
    }

    aliasList.innerHTML = '';

    aliases.forEach(
        function (alias) {
            const row =
                document.createElement(
                    'div'
                );

            row.className =
                'alias-row';

            row.dataset.aliasId =
                alias.id;

            row.dataset.slug =
                alias.slug;

            row.innerHTML =
                '<code></code>'
                + '<button type="button" class="alias-mini-btn copy-alias-btn">Copy</button>'
                + '<button type="button" class="alias-mini-btn danger delete-alias-btn">Delete</button>';

            row.querySelector(
                'code'
            ).textContent =
                '/story/'
                + alias.slug;

            aliasList.appendChild(
                row
            );
        }
    );

    setAliasStatus(
        aliases.length
        + ' alternate URL(s)'
    );
}

generateAliasesBtn?.addEventListener(
    'click',
    async function () {
        const count =
            Math.max(
                1,
                Math.min(
                    20,
                    parseInt(
                        aliasGenerateCount.value,
                        10
                    ) || 3
                )
            );

        const oldLabel =
            this.textContent;

        this.disabled = true;
        this.textContent =
            'Generating…';

        try {
            const data =
                await adminJsonRequest(
                    aliasGenerateUrl,
                    'POST',
                    { count: count }
                );

            renderAliases(
                data.aliases || []
            );

        } catch (error) {
            setAliasStatus(
                error.message,
                true
            );

        } finally {
            this.disabled = false;
            this.textContent =
                oldLabel;
        }
    }
);

addAliasBtn?.addEventListener(
    'click',
    async function () {
        const slug =
            prompt(
                'Enter an alternate slug.'
            );

        if (!slug) {
            return;
        }

        try {
            const data =
                await adminJsonRequest(
                    aliasStoreUrl,
                    'POST',
                    { slug: slug }
                );

            renderAliases(
                data.aliases || []
            );

        } catch (error) {
            setAliasStatus(
                error.message,
                true
            );
        }
    }
);

aliasList?.addEventListener(
    'click',
    async function (event) {
        const row =
            event.target.closest(
                '.alias-row'
            );

        if (!row) {
            return;
        }

        if (
            event.target.closest(
                '.copy-alias-btn'
            )
        ) {
            await uiCopyText(
                window.location.origin
                + '/story/'
                + row.dataset.slug
            );

            setAliasStatus(
                'Alternate URL copied.'
            );

            return;
        }

        if (
            event.target.closest(
                '.delete-alias-btn'
            )
        ) {
            if (
                !confirm(
                    'Delete this alternate URL?'
                )
            ) {
                return;
            }

            try {
                const data =
                    await adminJsonRequest(
                        aliasDestroyTemplate
                            .replace(
                                '__ALIAS__',
                                row.dataset.aliasId
                            ),
                        'DELETE'
                    );

                renderAliases(
                    data.aliases || []
                );

            } catch (error) {
                setAliasStatus(
                    error.message,
                    true
                );
            }
        }
    }
);

@endif


/* =========================
   INLINE CHAPTER BUILDER
========================= */
const chapterListAdmin =
    document.getElementById(
        'chapterListAdmin'
    );

const chapterEmpty =
    document.getElementById(
        'chapterEmpty'
    );

const chapterCountLabel =
    document.getElementById(
        'chapterCountLabel'
    );

const chapterAddCount =
    document.getElementById(
        'chapterAddCount'
    );

const addChapterBtn =
    document.getElementById(
        'addChapterBtn'
    );

const chaptersJson =
    document.getElementById(
        'chaptersJson'
    );

const chapterStatus =
    document.getElementById(
        'chapterStatus'
    );

const analyzeChaptersBtn =
    document.getElementById(
        'analyzeChaptersBtn'
    );

const chapterAiCount =
    document.getElementById(
        'chapterAiCount'
    );

function setChapterStatus(
    message,
    isError = false
) {
    if (!chapterStatus) {
        return;
    }

    chapterStatus.textContent =
        message || '';

    chapterStatus.style.color =
        isError
            ? '#b91c1c'
            : '#64748b';
}

function chapterCards() {
    return Array.from(
        chapterListAdmin
            ?.querySelectorAll(
                '.chapter-editor-card'
            )
        || []
    );
}

function updateChapterNumbers() {
    const cards =
        chapterCards();

    cards.forEach(
        function (card, index) {
            const number =
                index + 1;

            const numberEl =
                card.querySelector(
                    '.chapter-number-value'
                );

            if (numberEl) {
                numberEl.textContent =
                    number;
            }
        }
    );

    if (chapterCountLabel) {
        chapterCountLabel.textContent =
            cards.length;
    }

    if (chapterEmpty) {
        chapterEmpty.style.display =
            cards.length
                ? 'none'
                : '';
    }
}

function collectChapters() {
    return chapterCards()
        .map(
            function (card) {
                return {
                    id:
                        card.dataset.chapterId
                        ? parseInt(
                            card.dataset.chapterId,
                            10
                        )
                        : null,
                    title:
                        (
                            card.querySelector(
                                '.chapter-title-input'
                            )?.value
                            || ''
                        ).trim(),
                    body:
                        (
                            card.querySelector(
                                '.chapter-rich-editor'
                            )?.innerHTML
                            || ''
                        ).trim()
                };
            }
        )
        .filter(
            function (chapter) {
                return (
                    chapter.title !== ''
                    || chapter.body
                        .replace(
                            /<[^>]+>/g,
                            ''
                        )
                        .trim() !== ''
                );
            }
        );
}

function syncChaptersJson() {
    if (!chaptersJson) {
        return;
    }

    chaptersJson.value =
        JSON.stringify(
            collectChapters()
        );
}

function createChapterCard(
    chapter = {}
) {
    const card =
        document.createElement(
            'div'
        );

    card.className =
        'chapter-editor-card';

    if (chapter.id) {
        card.dataset.chapterId =
            chapter.id;
    }

    card.innerHTML =
        '<div class="chapter-editor-head">'
        + '<div class="chapter-number">Chapter <span class="chapter-number-value"></span></div>'
        + '<div class="chapter-head-actions">'
        + '<button type="button" class="move-chapter-up">↑</button>'
        + '<button type="button" class="move-chapter-down">↓</button>'
        + '<button type="button" class="danger remove-chapter-btn">Delete</button>'
        + '</div>'
        + '</div>'
        + '<div class="chapter-editor-body">'
        + '<input class="chapter-title-input chapter-title-field" placeholder="Chapter title">'
        + '<div class="chapter-mini-toolbar">'
        + '<button type="button" data-chapter-cmd="bold"><b>B</b></button>'
        + '<button type="button" data-chapter-cmd="italic"><i>I</i></button>'
        + '<button type="button" data-chapter-cmd="underline"><u>U</u></button>'
        + '<button type="button" data-chapter-cmd="formatBlock" data-chapter-val="h2">H2</button>'
        + '<button type="button" data-chapter-cmd="formatBlock" data-chapter-val="h3">H3</button>'
        + '<button type="button" data-chapter-cmd="formatBlock" data-chapter-val="p">P</button>'
        + '<button type="button" data-chapter-cmd="insertUnorderedList">• List</button>'
        + '<button type="button" data-chapter-cmd="insertOrderedList">1. List</button>'
        + '<button type="button" data-chapter-cmd="formatBlock" data-chapter-val="blockquote">Quote</button>'
        + '<button type="button" data-chapter-link>Link</button>'
        + '<button type="button" data-chapter-youtube>YouTube</button>'
        + '<button type="button" data-chapter-image>Choose Image</button>'
        + '<button type="button" data-chapter-media-library>Media Library</button>'
        + '<button type="button" data-chapter-image-url>Image URL</button>'
        + '</div>'
        + '<div class="chapter-rich-editor" contenteditable="true"></div>'
        + '</div>';

    card.querySelector(
        '.chapter-title-input'
    ).value =
        chapter.title || '';

    card.querySelector(
        '.chapter-rich-editor'
    ).innerHTML =
        chapter.body
        || '<p><br></p>';

    chapterListAdmin.appendChild(
        card
    );

    updateChapterNumbers();
}

addChapterBtn?.addEventListener(
    'click',
    function () {
        const count =
            Math.max(
                1,
                Math.min(
                    10,
                    parseInt(
                        chapterAddCount.value,
                        10
                    ) || 1
                )
            );

        for (
            let i = 0;
            i < count;
            i++
        ) {
            createChapterCard();
        }

        setChapterStatus(
            count
            + ' chapter(s) added. Save the article when ready.'
        );
    }
);

let activeChapterEditor = null;

const chapterImageInput =
    document.getElementById(
        'chapterImageInput'
    );

function chapterYoutubeEmbedHtml(
    rawUrl
) {
    const value =
        (rawUrl || '').trim();

    if (!value) {
        return '';
    }

    let videoId = '';

    try {
        const url =
            new URL(value);

        if (
            url.hostname.includes(
                'youtu.be'
            )
        ) {
            videoId =
                url.pathname
                    .replace(/^\/+/, '')
                    .split('/')[0]
                    .split('?')[0];
        } else if (
            url.hostname.includes(
                'youtube.com'
            )
        ) {
            videoId =
                url.searchParams.get(
                    'v'
                ) || '';

            if (!videoId) {
                const match =
                    url.pathname.match(
                        /\/(?:shorts|embed)\/([^/?]+)/i
                    );

                if (match) {
                    videoId =
                        match[1];
                }
            }
        }
    } catch (e) {
        const match =
            value.match(
                /(?:youtu\.be\/|v=|shorts\/|embed\/)([A-Za-z0-9_-]{6,})/
            );

        if (match) {
            videoId =
                match[1];
        }
    }

    videoId =
        videoId.replace(
            /[^A-Za-z0-9_-]/g,
            ''
        );

    if (!videoId) {
        return '';
    }

    const src =
        'https://www.youtube.com/embed/'
        + videoId;

    return (
        '<div class="video-embed" '
        + 'style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin:16px 0;">'
        + '<iframe src="'
        + src
        + '" title="YouTube video" '
        + 'style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" '
        + 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
        + 'allowfullscreen loading="lazy"></iframe>'
        + '</div><p><br></p>'
    );
}

function insertHtmlAtChapterCursor(
    editor,
    html
) {
    if (!editor || !html) {
        return;
    }

    editor.focus();

    let inserted = false;

    try {
        inserted =
            document.execCommand(
                'insertHTML',
                false,
                html
            );
    } catch (e) {
        inserted = false;
    }

    if (!inserted) {
        editor.insertAdjacentHTML(
            'beforeend',
            html
        );
    }

    syncChaptersJson();
}

function chapterImageHtml(
    src,
    alt = ''
) {
    const safeAlt =
        String(alt)
            .replace(
                /"/g,
                '&quot;'
            );

    return (
        '<p><img src="'
        + src
        + '" alt="'
        + safeAlt
        + '" '
        + 'style="max-width:100%;height:auto;display:block;margin:16px auto;"></p>'
        + '<p><br></p>'
    );
}

chapterImageInput?.addEventListener(
    'change',
    async function () {
        const file =
            this.files?.[0];

        if (
            !file
            || !activeChapterEditor
        ) {
            this.value = '';
            return;
        }

        const editor =
            activeChapterEditor;

        try {
            setChapterStatus(
                'Uploading chapter image…'
            );

            const item =
                await uploadImageToMediaLibrary(
                    file
                );

            insertHtmlAtChapterCursor(
                editor,
                chapterImageHtml(
                    item.url,
                    item.alt_text || ''
                )
            );

            setChapterStatus(
                'Image uploaded to Media Library and inserted.'
            );

        } catch (error) {
            setChapterStatus(
                error.message
                || 'Could not upload this image.',
                true
            );

        } finally {
            activeChapterEditor =
                null;

            chapterImageInput.value =
                '';
        }
    }
);

chapterListAdmin?.addEventListener(
    'click',
    function (event) {
        const card =
            event.target.closest(
                '.chapter-editor-card'
            );

        if (!card) {
            return;
        }

        if (
            event.target.closest(
                '.remove-chapter-btn'
            )
        ) {
            if (
                confirm(
                    'Remove this chapter? It will be deleted when you save the article.'
                )
            ) {
                card.remove();
                updateChapterNumbers();
                syncChaptersJson();
            }

            return;
        }

        if (
            event.target.closest(
                '.move-chapter-up'
            )
        ) {
            const previous =
                card.previousElementSibling;

            if (previous) {
                chapterListAdmin.insertBefore(
                    card,
                    previous
                );

                updateChapterNumbers();
            }

            return;
        }

        if (
            event.target.closest(
                '.move-chapter-down'
            )
        ) {
            const next =
                card.nextElementSibling;

            if (next) {
                chapterListAdmin.insertBefore(
                    next,
                    card
                );

                updateChapterNumbers();
            }

            return;
        }

        const linkButton =
            event.target.closest(
                '[data-chapter-link]'
            );

        if (linkButton) {
            const editor =
                card.querySelector(
                    '.chapter-rich-editor'
                );

            editor.focus();

            const url =
                prompt(
                    'Paste link URL'
                );

            if (url) {
                document.execCommand(
                    'createLink',
                    false,
                    url
                );
            }

            return;
        }

        const youtubeButton =
            event.target.closest(
                '[data-chapter-youtube]'
            );

        if (youtubeButton) {
            const editor =
                card.querySelector(
                    '.chapter-rich-editor'
                );

            const url =
                prompt(
                    'Paste YouTube URL'
                );

            if (!url) {
                return;
            }

            const html =
                chapterYoutubeEmbedHtml(
                    url
                );

            if (!html) {
                alert(
                    'This does not look like a valid YouTube URL.'
                );

                return;
            }

            insertHtmlAtChapterCursor(
                editor,
                html
            );

            return;
        }

        const imageButton =
            event.target.closest(
                '[data-chapter-image]'
            );

        if (imageButton) {
            activeChapterEditor =
                card.querySelector(
                    '.chapter-rich-editor'
                );

            chapterImageInput?.click();

            return;
        }

        const mediaLibraryButton =
            event.target.closest(
                '[data-chapter-media-library]'
            );

        if (mediaLibraryButton) {
            const editor =
                card.querySelector(
                    '.chapter-rich-editor'
                );

            openMediaPicker(
                'chapter',
                editor
            );

            return;
        }

        const imageUrlButton =
            event.target.closest(
                '[data-chapter-image-url]'
            );

        if (imageUrlButton) {
            const editor =
                card.querySelector(
                    '.chapter-rich-editor'
                );

            const imageUrl =
                prompt(
                    'Paste image URL'
                );

            if (!imageUrl) {
                return;
            }

            const cleanUrl =
                imageUrl.trim();

            if (
                !/^https?:\/\//i.test(
                    cleanUrl
                )
            ) {
                alert(
                    'Please enter a full image URL beginning with http:// or https://'
                );

                return;
            }

            insertHtmlAtChapterCursor(
                editor,
                chapterImageHtml(
                    cleanUrl
                )
            );

            return;
        }

        const toolbarButton =
            event.target.closest(
                '[data-chapter-cmd]'
            );

        if (toolbarButton) {
            const editor =
                card.querySelector(
                    '.chapter-rich-editor'
                );

            editor.focus();

            document.execCommand(
                toolbarButton.dataset
                    .chapterCmd,
                false,
                toolbarButton.dataset
                    .chapterVal
                || null
            );
        }
    }
);

chapterListAdmin?.addEventListener(
    'input',
    function (event) {
        if (
            event.target.closest(
                '.chapter-title-input'
            )
            || event.target.closest(
                '.chapter-rich-editor'
            )
        ) {
            syncChaptersJson();
        }
    }
);

updateChapterNumbers();
syncChaptersJson();


/* =========================
   AI ANALYZE CHAPTERS
========================= */
analyzeChaptersBtn?.addEventListener(
    'click',
    async function () {
        const titleField =
            articleForm.elements
                .namedItem('title');

        const title =
            (
                titleField?.value
                || ''
            ).trim();

        const sourceText =
            (
                ed.innerText
                || ''
            ).trim();

        if (!title) {
            alert(
                'Please enter the article title first.'
            );

            titleField?.focus();

            return;
        }

        if (
            sourceText.length < 350
        ) {
            alert(
                'Add a fuller source article before analyzing chapters.'
            );

            ed.focus();

            return;
        }

        if (
            chapterCards().length
            && !confirm(
                'AI analysis will replace the current chapter list. Continue?'
            )
        ) {
            return;
        }

        const count =
            Math.max(
                2,
                Math.min(
                    8,
                    parseInt(
                        chapterAiCount.value,
                        10
                    ) || 4
                )
            );

        const oldLabel =
            this.textContent;

        this.disabled = true;
        this.textContent =
            'Analyzing…';

        setChapterStatus(
            'AI is structuring the article into chapters…'
        );

        try {
            const response =
                await fetch(
                    @json(
                        route(
                            'admin.articles.ai-generate'
                        )
                    ),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json',
                            'Accept':
                                'application/json',
                            'X-CSRF-TOKEN':
                                @json(
                                    csrf_token()
                                )
                        },
                        body:
                            JSON.stringify({
                                mode:
                                    'chapters',
                                title:
                                    title,
                                body:
                                    ed.innerHTML,
                                chapter_count:
                                    count
                            })
                    }
                );

            let data = {};

            try {
                data =
                    await response.json();
            } catch (e) {
                data = {};
            }

            if (!response.ok) {
                throw new Error(
                    data.message
                    || 'AI chapter analysis failed.'
                );
            }

            if (
                !Array.isArray(
                    data.chapters
                )
                || !data.chapters.length
            ) {
                throw new Error(
                    'AI returned no chapters.'
                );
            }

            if (data.intro_html) {
                ed.innerHTML =
                    data.intro_html;

                syncEditor();
            }

            chapterListAdmin.innerHTML =
                '';

            data.chapters.forEach(
                function (chapter) {
                    createChapterCard(
                        chapter
                    );
                }
            );

            syncChaptersJson();

            const chapterMode =
                document.querySelector(
                    'input[name="content_mode"][value="chapter"]'
                );

            if (chapterMode) {
                chapterMode.checked = true;
                refreshContentModeUi();
            }

            setChapterStatus(
                data.chapters.length
                + ' AI chapter(s) created. Review them before saving.'
            );

        } catch (error) {
            console.error(error);

            setChapterStatus(
                error.message
                || 'AI chapter analysis failed.',
                true
            );

        } finally {
            this.disabled = false;
            this.textContent =
                oldLabel;
        }
    }
);

/* =========================
   LIVE SEARCH PREVIEW
========================= */
const articleTitleField =
    articleForm.elements.namedItem('title');

const articleSlugField =
    articleForm.elements.namedItem('slug');

const searchPreviewTitle =
    document.getElementById('searchPreviewTitle');

const searchPreviewDescription =
    document.getElementById('searchPreviewDescription');

const searchPreviewUrl =
    document.getElementById('searchPreviewUrl');

const publicStoryBaseUrl =
    @json(rtrim(config('app.url'), '/') . '/story');

function previewSlugify(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[’‘]/g, '')
        .replace(/&/g, ' and ')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .replace(/-{2,}/g, '-');
}

function truncatePreviewText(value, maxLength) {
    const clean =
        String(value || '')
            .replace(/\s+/g, ' ')
            .trim();

    if (clean.length <= maxLength) {
        return clean;
    }

    return clean.slice(0, maxLength - 1)
        .replace(/\s+\S*$/, '') + '…';
}

function updateSearchPreview() {
    const rawTitle =
        (seoTitleField.value || '').trim() ||
        (articleTitleField?.value || '').trim() ||
        'Article title';

    const rawDescription =
        (metaDescriptionField.value || '').trim() ||
        (excerptField.value || '').trim() ||
        'Add a meta description to preview how this article may appear in search results.';

    const manualSlug =
        (articleSlugField?.value || '').trim();

    const autoSlug =
        previewSlugify(
            articleTitleField?.value || ''
        );

    const previewSlug =
        manualSlug || autoSlug || 'article-slug';

    const previewUrl =
        publicStoryBaseUrl + '/' + previewSlug;

    /*
     * Keep the full field values in the form.
     * Truncation here is only visual, to resemble a search result.
     */
    searchPreviewTitle.textContent =
        truncatePreviewText(rawTitle, 68);

    searchPreviewDescription.textContent =
        truncatePreviewText(rawDescription, 165);

    searchPreviewUrl.textContent =
        previewUrl;
}

[
    articleTitleField,
    articleSlugField,
    excerptField,
    seoTitleField,
    metaDescriptionField
].forEach(function (field) {
    if (!field) {
        return;
    }

    field.addEventListener(
        'input',
        updateSearchPreview
    );
});

updateSearchPreview();

/* Featured image preview */
const featuredImageInput =
    document.getElementById('featuredImageInput');

const featuredImagePreview =
    document.getElementById('featuredImagePreview');

const featuredImagePreviewWrap =
    document.getElementById('featuredImagePreviewWrap');

const featuredImageName =
    document.getElementById('featuredImageName');

const featuredMediaId =
    document.getElementById(
        'featuredMediaId'
    );

const featuredMediaLibraryBtn =
    document.getElementById(
        'featuredMediaLibraryBtn'
    );

let featuredImageObjectUrl = null;

featuredImageInput.addEventListener('change', function () {
    const file = this.files && this.files[0];

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        this.value = '';
        return;
    }

    if (featuredImageObjectUrl) {
        URL.revokeObjectURL(featuredImageObjectUrl);
    }

    featuredImageObjectUrl =
        URL.createObjectURL(file);

    featuredImagePreview.src =
        featuredImageObjectUrl;

    featuredImagePreviewWrap.style.display =
        'block';

    if (featuredMediaId) {
        featuredMediaId.value = '';
    }

    const importedField =
        document.getElementById(
            'importedFeaturedImageUrl'
        );

    if (importedField) {
        importedField.value = '';
    }

    const sizeMb =
        (file.size / 1024 / 1024).toFixed(2);

    featuredImageName.textContent =
        file.name + ' • ' + sizeMb + ' MB';
});

featuredMediaLibraryBtn?.addEventListener(
    'click',
    function () {
        openMediaPicker(
            'featured'
        );
    }
);

window.addEventListener('beforeunload', function () {
    if (featuredImageObjectUrl) {
        URL.revokeObjectURL(featuredImageObjectUrl);
    }
});
</script>

@endpush
