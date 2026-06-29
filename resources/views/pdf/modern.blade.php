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
    color: #1a1a2e;
}

/* ── Cover ── */
.cover {
    padding-top: 80px;
    padding-{{ $isRtl ? 'right' : 'left' }}: 8px;
    border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #4f46e5;
}
.cover-title {
    font-family: {{ $headingFont }};
    font-size: 28pt;
    font-weight: bold;
    color: #111;
    margin-bottom: 10px;
}
.cover-subtitle {
    font-size: 13pt;
    color: #4f46e5;
    margin-bottom: 30px;
}
.cover-author {
    font-size: 11pt;
    color: #555;
    letter-spacing: 1px;
}

/* ── Table of Contents ── */
.toc-title {
    font-family: {{ $headingFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    color: #4f46e5;
    text-align: {{ $isRtl ? 'right' : 'left' }};
    margin-bottom: 16px;
}
.toc-list {
    padding-{{ $isRtl ? 'right' : 'left' }}: 0px;
    list-style-type: none;
}
.toc-list li {
    padding: 5px 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: {{ $setting->font_size }}pt;
}
.toc-num {
    color: #4f46e5;
    font-weight: bold;
    margin-{{ $isRtl ? 'left' : 'right' }}: 8px;
}

/* ── Chapter ── */
.chapter-header {
    margin-bottom: 22px;
    border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #4f46e5;
    padding-{{ $isRtl ? 'right' : 'left' }}: 10px;
}
.chapter-number {
    font-size: 9pt;
    color: #4f46e5;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.chapter-title {
    font-family: {{ $headingFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    color: #111;
}
.chapter-body {
    text-align: {{ $isRtl ? 'right' : 'left' }};
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
    @if($setting->author)
        <p class="cover-author">{{ $setting->author }}</p>
    @endif
</div>

{{-- Table of Contents --}}
@if($setting->show_table_of_contents && $chapters->isNotEmpty())
<div style="page-break-before: always;">
    <div class="toc-title">{{ $labels['toc'] }}</div>
    <ul class="toc-list">
        @foreach($chapters as $i => $chapter)
            <li>
                <span class="toc-num">{{ $i + 1 }}.</span>{{ $chapter->title }}
            </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Chapters --}}
@foreach($chapters as $i => $chapter)
<div style="page-break-before: always;">
    @if($setting->show_chapter_headers)
        <div class="chapter-header">
            <div class="chapter-number">{{ $labels['chapter'] }} {{ $i + 1 }}</div>
            <div class="chapter-title">{{ $chapter->title }}</div>
        </div>
    @endif
    <div class="chapter-body">{{ $chapter->content }}</div>
</div>
@endforeach

</body>
</html>
