<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; }

body {
    font-family: {{ $bodyFont }};
    font-size: {{ $setting->font_size }}pt;
    line-height: {{ $setting->line_height }};
    direction: {{ $isRtl ? 'rtl' : 'ltr' }};
    color: #1c1c1c;
}

/* ── Cover ── */
.cover {
    text-align: center;
    padding-top: 90px;
}
.cover-title {
    font-family: {{ $headingFont }};
    font-size: 26pt;
    font-weight: bold;
    color: #111;
    margin-bottom: 14px;
}
.cover-divider {
    border: none;
    border-top: 1px solid #888;
    width: 40%;
    margin: 12px auto;
}
.cover-subtitle {
    font-size: 13pt;
    font-style: italic;
    color: #555;
    margin-bottom: 6px;
}
.cover-author {
    font-size: 12pt;
    color: #444;
    margin-top: 8px;
}

/* ── Table of Contents ── */
.toc-title {
    font-family: {{ $headingFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    text-align: {{ $isRtl ? 'right' : 'left' }};
    border-bottom: 2px solid #1c1c1c;
    padding-bottom: 6px;
    margin-bottom: 18px;
}
.toc-list {
    padding-{{ $isRtl ? 'right' : 'left' }}: 20px;
    list-style-type: decimal;
}
.toc-list li {
    margin-bottom: 7px;
    font-size: {{ $setting->font_size }}pt;
}

/* ── Chapter ── */
.chapter-title {
    font-family: {{ $headingFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    text-align: center;
    border-bottom: 1px solid #aaa;
    padding-bottom: 10px;
    margin-bottom: 24px;
}
.chapter-body {
    text-align: {{ $isRtl ? 'right' : 'justify' }};
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
</head>
<body>

{{-- Cover --}}
<div class="cover">
    <p class="cover-title">{{ $book->title }}</p>
    @if($setting->subtitle)
        <p class="cover-subtitle">{{ $setting->subtitle }}</p>
    @endif
    <div class="cover-divider"></div>
    @if($setting->author)
        <p class="cover-author">{{ $setting->author }}</p>
    @endif
</div>

{{-- Table of Contents --}}
@if($setting->show_table_of_contents && $chapters->isNotEmpty())
<div style="page-break-before: always;">
    <div class="toc-title">{{ $isRtl ? 'جدول المحتويات' : 'Table of Contents' }}</div>
    <ol class="toc-list">
        @foreach($chapters as $chapter)
            <li>{{ $chapter->title }}</li>
        @endforeach
    </ol>
</div>
@endif

{{-- Chapters --}}
@foreach($chapters as $chapter)
<div style="page-break-before: always;">
    @if($setting->show_chapter_headers)
        <div class="chapter-title">{{ $chapter->title }}</div>
    @endif
    <div class="chapter-body">{{ $chapter->content }}</div>
</div>
@endforeach

</body>
</html>
