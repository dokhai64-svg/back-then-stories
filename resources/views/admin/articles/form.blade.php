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

                    <textarea id="excerptField" name="excerpt">{{ old('excerpt', $article->excerpt) }}</textarea>

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

                    <label>Article body</label>

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
                        id="seoTitleField"
                        name="seo_title"
                        maxlength="255"
                        value="{{ old('seo_title', $article->seo_title) }}"
                    >
                </div>

                <div class="field">
                    <label>Meta description (max 320)</label>

                    <textarea
                        id="metaDescriptionField"
                        name="meta_description"
                        maxlength="320"
                    >{{ old('meta_description', $article->meta_description) }}</textarea>
                </div>

                <div class="field">
                    <label>Facebook hook</label>

                    <textarea name="facebook_hook">{{ old('facebook_hook', $article->facebook_hook) }}</textarea>
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

function compressEditorImage(file, maxWidth = 1400, quality = 0.82) {
    return new Promise(function (resolve, reject) {
        const reader = new FileReader();

        reader.onload = function () {
            const image = new Image();

            image.onload = function () {
                const scale = Math.min(1, maxWidth / image.width);

                const canvas = document.createElement('canvas');
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);

                const ctx = canvas.getContext('2d');
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

                resolve(
                    canvas.toDataURL('image/jpeg', quality)
                );
            };

            image.onerror = reject;
            image.src = reader.result;
        };

        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

editorImageInput.addEventListener('change', async function () {
    const file = this.files && this.files[0];

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        return;
    }

    if (file.size > 8 * 1024 * 1024) {
        alert('Please choose an image smaller than 8 MB.');
        return;
    }

    try {
        const dataUrl = await compressEditorImage(file);

        restoreSelection();

        document.execCommand(
            'insertHTML',
            false,
            '<p><img src="' + dataUrl + '" alt="" style="max-width:100%;height:auto;display:block;margin:20px auto;border-radius:8px"></p><p><br></p>'
        );

        saveSelection();
        syncEditor();

    } catch (error) {
        alert('Could not insert this image.');
    }
});

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
});

syncEditor();


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

aiGenerateBtn.addEventListener('click', async function () {
    const titleField =
        articleForm.elements.namedItem('title');

    const title =
        (titleField?.value || '').trim();

    const articleText =
        (ed.innerText || '').trim();

    if (!title) {
        alert('Please enter the article title first.');
        titleField?.focus();
        return;
    }

    if (articleText.length < 80) {
        alert(
            'Please add more article body text before using AI.'
        );
        ed.focus();
        return;
    }

    const oldLabel = aiGenerateBtn.textContent;

    aiGenerateBtn.disabled = true;
    aiGenerateBtn.textContent = 'Generating…';
    setAiStatus('AI is preparing the opening and SEO fields…');

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

        setAiStatus(
            'AI suggestions added. Review them before saving.'
        );

    } catch (error) {
        console.error(error);

        setAiStatus(
            error.message ||
            'AI request failed. Please try again.',
            true
        );

    } finally {
        aiGenerateBtn.disabled = false;
        aiGenerateBtn.textContent = oldLabel;
    }
});

/* Featured image preview */
const featuredImageInput =
    document.getElementById('featuredImageInput');

const featuredImagePreview =
    document.getElementById('featuredImagePreview');

const featuredImagePreviewWrap =
    document.getElementById('featuredImagePreviewWrap');

const featuredImageName =
    document.getElementById('featuredImageName');

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

    const sizeMb =
        (file.size / 1024 / 1024).toFixed(2);

    featuredImageName.textContent =
        file.name + ' • ' + sizeMb + ' MB';
});

window.addEventListener('beforeunload', function () {
    if (featuredImageObjectUrl) {
        URL.revokeObjectURL(featuredImageObjectUrl);
    }
});
</script>

@endpush
