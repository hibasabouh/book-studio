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
    color: #1a1a1a;
}

/* ── Cover ── */
.cover {
    text-align: center;
    padding-top: 70px;
    border: 2px solid #8b7355;
    padding: 60px 40px;
    margin: 30px 20px;
}
.cover-ornament {
    font-size: 18pt;
    color: #8b7355;
    margin-bottom: 20px;
    letter-spacing: 8px;
}
.cover-title {
    font-family: {{ $headingFont }};
    font-size: 30pt;
    font-weight: bold;
    color: #111;
    margin-bottom: 10px;
}
.cover-rule {
    border: none;
    border-top: 1px solid #8b7355;
    width: 30%;
    margin: 14px auto;
}
.cover-subtitle {
    font-size: 13pt;
    font-style: italic;
    color: #555;
    margin-bottom: 6px;
}
.cover-author {
    font-size: 12pt;
    color: #8b7355;
    margin-top: 20px;
}
.cover-ornament-bottom {
    font-size: 14pt;
    color: #8b7355;
    margin-top: 20px;
    letter-spacing: 8px;
}

/* ── Table of Contents ── */
.toc-ornament {
    text-align: center;
    font-size: 14pt;
    color: #8b7355;
    letter-spacing: 6px;
    margin-bottom: 10px;
}
.toc-title {
    font-family: {{ $headingFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    text-align: center;
    color: #111;
    margin-bottom: 6px;
}
.toc-rule {
    border: none;
    border-top: 1px solid #8b7355;
    width: 50%;
    margin: 8px auto 20px;
}
.toc-list {
    padding-{{ $isRtl ? 'right' : 'left' }}: 20px;
    list-style-type: upper-roman;
}
.toc-list li {
    margin-bottom: 8px;
    font-size: {{ $setting->font_size }}pt;
}

/* ── Chapter ── */
.chapter-ornament {
    text-align: center;
    font-size: 14pt;
    color: #8b7355;
    letter-spacing: 6px;
    margin-bottom: 10px;
}
.chapter-title {
    font-family: {{ $headingFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    text-align: center;
    color: #111;
    margin-bottom: 6px;
}
.chapter-rule {
    border: none;
    border-top: 1px solid #8b7355;
    width: 40%;
    margin: 8px auto 28px;
}
.chapter-body {
    text-align: {{ $isRtl ? 'right' : 'justify' }};
    white-space: pre-wrap;
    word-wrap: break-word;
}
.section-break {
    text-align: center;
    color: #8b7355;
    font-size: 14pt;
    margin: 20px 0;
    letter-spacing: 8px;
}
</style>
</head>
<body>

{{-- Cover --}}
<div class="cover">
    <div class="cover-ornament">&#10022; &#10022; &#10022;</div>
    <p class="cover-title">{{ $book->title }}</p>
    @if($setting->subtitle)
        <div class="cover-rule"></div>
        <p class="cover-subtitle">{{ $setting->subtitle }}</p>
    @else
        <div class="cover-rule"></div>
    @endif
    @if($setting->author)
        <p class="cover-author">{{ $setting->author }}</p>
    @endif
    <div class="cover-ornament-bottom">&#10022; &#10022; &#10022;</div>
</div>

{{-- Table of Contents --}}
@if($setting->show_table_of_contents && $chapters->isNotEmpty())
<div style="page-break-before: always;">
    <div class="toc-ornament">&#10022; &#10022; &#10022;</div>
    <div class="toc-title">{{ $isRtl ? 'جدول المحتويات' : 'Table of Contents' }}</div>
    <div class="toc-rule"></div>
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
        <div class="chapter-ornament">&#10022; &#10022; &#10022;</div>
        <div class="chapter-title">{{ $chapter->title }}</div>
        <div class="chapter-rule"></div>
    @endif
    <div class="chapter-body">{{ $chapter->content }}</div>
</div>
@endforeach

</body>
</html>
