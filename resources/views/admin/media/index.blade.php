@extends('admin.layout')

@section('heading','Media')

@section('content')
<style>
.media-page-tools{
    display:flex;
    justify-content:space-between;
    gap:14px;
    align-items:end;
    flex-wrap:wrap;
}
.media-upload-box{
    flex:1 1 560px;
}
.media-search-box{
    flex:0 1 360px;
}
.media-upload-row{
    display:grid;
    grid-template-columns:minmax(240px,1fr) minmax(220px,1fr) auto;
    gap:10px;
    align-items:end;
}
.media-upload-row input{
    width:100%;
    min-height:40px;
    padding:8px 10px;
    border:1px solid #cfd4dc;
    border-radius:8px;
    background:#fff;
}
.media-search-form{
    display:flex;
    gap:8px;
}
.media-search-form input{
    flex:1;
    min-height:40px;
    padding:8px 10px;
    border:1px solid #cfd4dc;
    border-radius:8px;
}
.media-grid-v2{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:14px;
    margin-top:18px;
}
.media-card-v2{
    overflow:hidden;
    padding:0;
}
.media-card-v2 img{
    width:100%;
    aspect-ratio:1;
    object-fit:cover;
    display:block;
    background:#f3f4f6;
}
.media-card-body{
    padding:12px;
}
.media-filename{
    font-size:12px;
    font-weight:800;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.media-meta{
    color:#6b7280;
    font-size:10px;
    margin-top:4px;
}
.media-url{
    margin-top:8px;
    padding:7px 8px;
    border:1px solid #e5e7eb;
    border-radius:7px;
    background:#f8fafc;
    font-size:10px;
    word-break:break-all;
    max-height:48px;
    overflow:auto;
}
.media-alt-form{
    display:flex;
    gap:6px;
    margin-top:8px;
}
.media-alt-form input{
    flex:1;
    min-width:0;
    padding:7px 8px;
    border:1px solid #d1d5db;
    border-radius:7px;
    font-size:11px;
}
.media-actions{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
    margin-top:8px;
}
.media-actions .btn{
    padding:7px 9px;
    font-size:11px;
}
.media-empty{
    margin-top:18px;
    padding:42px 18px;
    text-align:center;
}
@media(max-width:1200px){
    .media-grid-v2{
        grid-template-columns:repeat(4,minmax(0,1fr));
    }
}
@media(max-width:900px){
    .media-upload-row{
        grid-template-columns:1fr;
    }
    .media-grid-v2{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}
</style>

<div class="card">
    <div class="media-page-tools">
        <div class="media-upload-box">
            <div style="font-weight:850;margin-bottom:8px">
                Upload images
            </div>

            <form
                method="post"
                enctype="multipart/form-data"
                action="{{ route('admin.media.store') }}"
                class="media-upload-row"
            >
                @csrf

                <div>
                    <label class="small">
                        Images
                    </label>
                    <input
                        type="file"
                        name="files[]"
                        accept="image/*"
                        multiple
                        required
                    >
                </div>

                <div>
                    <label class="small">
                        Alt text (optional)
                    </label>
                    <input
                        name="alt_text"
                        placeholder="Describe the image"
                    >
                </div>

                <button class="btn">
                    Upload
                </button>
            </form>

            <div class="muted small" style="margin-top:7px">
                Up to 20 images per upload. Each image can be up to 8 MB.
                Uploaded images are stored in the shared Media Library.
            </div>
        </div>

        <div class="media-search-box">
            <div style="font-weight:850;margin-bottom:8px">
                Search library
            </div>

            <form
                method="get"
                action="{{ route('admin.media.index') }}"
                class="media-search-form"
            >
                <input
                    name="q"
                    value="{{ $search }}"
                    placeholder="Filename or alt text..."
                >

                <button class="btn secondary">
                    Search
                </button>

                @if($search !== '')
                    <a
                        class="btn secondary"
                        href="{{ route('admin.media.index') }}"
                    >
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>
</div>

@if($media->count())
    <div class="media-grid-v2">
        @foreach($media as $m)
            @php
                $mediaUrl =
                    asset(
                        'storage/'
                        . $m->path
                    );
            @endphp

            <div class="card media-card-v2">
                <img
                    src="{{ $mediaUrl }}"
                    alt="{{ $m->alt_text }}"
                    loading="lazy"
                >

                <div class="media-card-body">
                    <div
                        class="media-filename"
                        title="{{ $m->filename }}"
                    >
                        {{ $m->filename }}
                    </div>

                    <div class="media-meta">
                        {{ strtoupper(str_replace('image/','',$m->mime_type ?? 'image')) }}
                        ·
                        {{ number_format(($m->size ?? 0) / 1024, 0) }} KB
                    </div>

                    <div class="media-url">
                        {{ $mediaUrl }}
                    </div>

                    <form
                        method="post"
                        action="{{ route('admin.media.alt',$m) }}"
                        class="media-alt-form"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            name="alt_text"
                            value="{{ $m->alt_text }}"
                            placeholder="Alt text"
                        >

                        <button class="btn secondary">
                            Save
                        </button>
                    </form>

                    <div class="media-actions">
                        <button
                            class="btn secondary js-copy-media-url"
                            type="button"
                            data-url="{{ $mediaUrl }}"
                        >
                            Copy URL
                        </button>

                        <form
                            method="post"
                            action="{{ route('admin.media.destroy',$m) }}"
                            onsubmit="return confirm('Delete image?')"
                        >
                            @csrf
                            @method('DELETE')

                            <button class="btn danger">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="pagination">
        {{ $media->links() }}
    </div>
@else
    <div class="card media-empty">
        <b>No images found.</b>
        <div class="muted small" style="margin-top:5px">
            Upload an image or change your search.
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document
    .querySelectorAll(
        '.js-copy-media-url'
    )
    .forEach(
        function (button) {
            button.addEventListener(
                'click',
                async function () {
                    const url =
                        this.dataset.url;

                    try {
                        await navigator
                            .clipboard
                            .writeText(url);

                        const old =
                            this.textContent;

                        this.textContent =
                            'Copied';

                        setTimeout(
                            () => {
                                this.textContent =
                                    old;
                            },
                            1200
                        );
                    } catch (e) {
                        prompt(
                            'Copy this URL',
                            url
                        );
                    }
                }
            );
        }
    );
</script>
@endpush
