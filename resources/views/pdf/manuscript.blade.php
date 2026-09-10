<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin: 0; padding: 0; }

body {
    font-family: {{ $bodyFont }};
    font-size: {{ $setting->font_size }}pt;
    line-height: {{ $setting->line_height * 1.4 }};
    direction: {{ $isRtl ? 'rtl' : 'ltr' }};
    color: #1a1a1a;
}

/* ── Cover ── */
.cover {
    padding-top: 100px;
    text-align: {{ $isRtl ? 'right' : 'left' }};
}
.cover-title {
    font-family: {{ $bodyFont }};
    font-size: 22pt;
    font-weight: bold;
    color: #111;
    margin-bottom: 6px;
}
.cover-subtitle {
    font-size: 12pt;
    color: #444;
    margin-bottom: 30px;
}
.cover-meta {
    font-size: 10pt;
    color: #555;
    margin-bottom: 4px;
}

/* ── Table of Contents ── */
.toc-title {
    font-family: {{ $bodyFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    text-align: {{ $isRtl ? 'right' : 'left' }};
    margin-bottom: 16px;
}
.toc-list {
    padding-{{ $isRtl ? 'right' : 'left' }}: 0;
    list-style-type: none;
}
.toc-list li {
    margin-bottom: 6px;
    font-size: {{ $setting->font_size }}pt;
}
.toc-dot {
    color: #999;
    margin-{{ $isRtl ? 'left' : 'right' }}: 6px;
}
.toc-list a {
    color: inherit;
    text-decoration: none;
}

/* ── Chapter ── */
.chapter-label {
    font-size: 9pt;
    font-weight: bold;
    letter-spacing: 3px;
    color: #555;
    margin-bottom: 4px;
    text-align: {{ $isRtl ? 'right' : 'left' }};
}
.chapter-title {
    font-family: {{ $bodyFont }};
    font-size: {{ $setting->chapter_header_font_size }}pt;
    font-weight: bold;
    text-align: {{ $isRtl ? 'right' : 'left' }};
    border-bottom: 1px solid #333;
    padding-bottom: 8px;
    margin-bottom: 22px;
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
    <p class="cover-title">{!! $fmt->inline($book->title) !!}</p>
    @if($setting->subtitle)
        <p class="cover-subtitle">{!! $fmt->inline($setting->subtitle) !!}</p>
    @endif
    @if($setting->author)
        <p class="cover-meta">{{ $labels['author'] }} {!! $fmt->inline($setting->author) !!}</p>
    @endif
    <p class="cover-meta" style="margin-top: 30px;">
        {{ $labels['chapters_count'] }} {{ $chapters->count() }}
    </p>
</div>

{{-- Table of Contents --}}
@if($setting->show_table_of_contents && $chapters->isNotEmpty())
<div style="page-break-before: always;">
    <div class="toc-title">{{ $labels['contents'] }}</div>
    <ul class="toc-list">
        @foreach($chapters as $i => $chapter)
            <li>
                <a href="#{{ $chapter->pdfAnchor() }}"><span class="toc-dot">{{ $i + 1 }}.</span>{!! $fmt->inline($chapter->title) !!}</a>
            </li>
        @endforeach
    </ul>
</div>
@endif

{{-- Chapters --}}
@foreach($chapters as $i => $chapter)
<div style="page-break-before: always;">
    <bookmark content="{{ $chapter->title }}" level="0" />
    @if($setting->show_chapter_headers)
        <div class="chapter-label">
            {{ $labels['chapter_label'] }} {{ $i + 1 }}
        </div>
        <div class="chapter-title">{!! $fmt->inline($chapter->title) !!}</div>
    @endif
    <div class="chapter-body">{!! $fmt->block($chapter->content) !!}</div>
</div>
@endforeach

</body>
</html>
