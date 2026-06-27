<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), config('app.rtl_locales', [])) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #333; padding: 32px;">
    <h2 style="margin-bottom: 4px;">{{ __('otp.heading') }}</h2>
    <p>{{ __('otp.intro', ['minutes' => 10]) }}</p>
    <div style="font-size: 36px; font-weight: bold; letter-spacing: 8px; margin: 24px 0; color: #4f46e5;">
        {{ $code }}
    </div>
    <p style="color: #888; font-size: 13px;">{{ __('otp.ignore') }}</p>
</body>
</html>
