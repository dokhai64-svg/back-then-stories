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
                    <label>Opening excerpt / deck</label>

                    <textarea name="excerpt">{{ old('excerpt', $article->excerpt) }}</textarea>
                </div>

                <div class="field">

                    <label>Article body</label>

                    <div class="editorbar" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
                        <button type="button" data-cmd="undo" title="Undo">↶</button>
                        <button type="button" data-cmd="redo" title="Redo">↷</button>
                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                        <button type="button" data-cmd="formatBlock" data-val="h2" title="Heading 2">H2</button>
                        <button type="button" data-cmd="formatBlock" data-val="h3" title="Heading 3">H3</button>
                        <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">P</button>
                        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
                        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
                        <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Quote">Quote</button>
                        <button type="button" id="linkBtn" title="Add link">Link</button>
                        <button type="button" id="unlinkBtn" title="Remove link">Unlink</button>
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

                </div>

            </div>


            <div
                class="card"
                style="margin-top:18px"
            >

                <h3>SEO & Facebook</h3>

                <div class="field">
                    <label>SEO title</label>

                    <input
                        name="seo_title"
                        maxlength="255"
                        value="{{ old('seo_title', $article->seo_title) }}"
                    >
                </div>

                <div class="field">
                    <label>Meta description (max 320)</label>

                    <textarea
                        name="meta_description"
                        maxlength="320"
                    >{{ old('meta_description', $article->meta_description) }}</textarea>
                </div>

                <div class="field">
                    <label>Facebook hook</label>

                    <textarea name="facebook_hook">{{ old('facebook_hook', $article->facebook_hook) }}</textarea>
                </div>

                <div class="field">
                    <label>YouTube URL</label>

                    <input
                        name="youtube_url"
                        value="{{ old('youtube_url', $article->youtube_url) }}"
                    >
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
                        id="featuredImageInput"
                        type="file"
                        name="featured_image_file"
                        accept="image/*"
                    >


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
        onsubmit="return confirm('Delete this article?')"
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
    if (selection && selection.rangeCount > 0 && ed.contains(selection.anchorNode)) {
        savedRange = selection.getRangeAt(0).cloneRange();
    }
}

function restoreSelection() {
    ed.focus();
    if (!savedRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(savedRange);
}

ed.addEventListener('keyup', saveSelection);
ed.addEventListener('mouseup', saveSelection);
ed.addEventListener('focus', saveSelection);
ed.addEventListener('input', function () {
    saveSelection();
    syncEditor();
});

document.querySelectorAll('[data-cmd]').forEach(function (button) {
    button.addEventListener('mousedown', function (event) {
        event.preventDefault();
        saveSelection();
    });
    button.addEventListener('click', function () {
        restoreSelection();
        document.execCommand(this.dataset.cmd, false, this.dataset.val || null);
        saveSelection();
        syncEditor();
    });
});

document.getElementById('linkBtn').addEventListener('mousedown', function (event) {
    event.preventDefault();
    saveSelection();
});
document.getElementById('linkBtn').addEventListener('click', function () {
    const url = prompt('Paste link URL');
    if (!url) return;
    restoreSelection();
    document.execCommand('createLink', false, url);
    saveSelection();
    syncEditor();
});

document.getElementById('unlinkBtn').addEventListener('mousedown', function (event) {
    event.preventDefault();
    saveSelection();
});
document.getElementById('unlinkBtn').addEventListener('click', function () {
    restoreSelection();
    document.execCommand('unlink', false, null);
    saveSelection();
    syncEditor();
});

document.getElementById('imgBtn').addEventListener('mousedown', function (event) {
    event.preventDefault();
    saveSelection();
});
document.getElementById('imgBtn').addEventListener('click', function () {
    const url = prompt('Image URL (upload the image in Media and paste its URL here)');
    if (!url) return;
    restoreSelection();
    document.execCommand('insertImage', false, url);
    saveSelection();
    syncEditor();
});

articleForm.addEventListener('submit', function () {
    syncEditor();
});
syncEditor();

const featuredImageInput = document.getElementById('featuredImageInput');
const featuredImagePreview = document.getElementById('featuredImagePreview');
const featuredImagePreviewWrap = document.getElementById('featuredImagePreviewWrap');
const featuredImageName = document.getElementById('featuredImageName');
let featuredImageObjectUrl = null;

featuredImageInput.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        this.value = '';
        return;
    }
    if (featuredImageObjectUrl) {
        URL.revokeObjectURL(featuredImageObjectUrl);
    }
    featuredImageObjectUrl = URL.createObjectURL(file);
    featuredImagePreview.src = featuredImageObjectUrl;
    featuredImagePreviewWrap.style.display = 'block';
    const sizeMb = (file.size / 1024 / 1024).toFixed(2);
    featuredImageName.textContent = file.name + ' • ' + sizeMb + ' MB';
});

window.addEventListener('beforeunload', function () {
    if (featuredImageObjectUrl) {
        URL.revokeObjectURL(featuredImageObjectUrl);
    }
});
</script>

@endpush
