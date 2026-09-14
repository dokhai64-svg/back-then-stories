@extends('admin.layout')
@section('heading', $article->exists ? 'Edit Article' : 'New Article')
@section('content')
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
    <div class="grid2">
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
