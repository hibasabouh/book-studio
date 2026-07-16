<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), config('app.rtl_locales', [])) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #333; padding: 32px;">
    <h2 style="margin-bottom: 4px;">{{ __('contact.mail_heading', ['category' => $contactMessage->category->label()]) }}</h2>
    <p style="color: #888; font-size: 13px;">
        {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;
    </p>
    <div style="margin: 24px 0; padding: 16px; background: #f9fafb; border-radius: 8px; white-space: pre-wrap;">{{ $contactMessage->message }}</div>
    @if ($contactMessage->attachments->isNotEmpty())
        <div style="margin: 0 0 24px;">
            <p style="font-weight: bold; margin-bottom: 6px;">{{ __('contact.mail_attachments_heading', ['count' => $contactMessage->attachments->count()]) }}</p>
            <ul style="padding-left: 18px; margin: 0;">
                @foreach ($contactMessage->attachments as $attachment)
                    <li><a href="{{ file_url($attachment->path) }}">{{ $attachment->original_name }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif
    <p style="color: #888; font-size: 13px;">{{ __('contact.mail_footer') }}</p>
</body>
</html>
