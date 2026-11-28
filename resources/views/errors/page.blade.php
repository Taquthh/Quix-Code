@php
    $locale = in_array(app()->getLocale(), ['id', 'en'], true) ? app()->getLocale() : 'id';
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07111f">
    <title>{{ $title }} — Quix Code</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f8fafc; color: #0f172a; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; }
        main { width: min(100%, 620px); border: 1px solid #e2e8f0; border-radius: 24px; background: #fff; padding: clamp(28px, 7vw, 56px); box-shadow: 0 24px 80px rgb(15 23 42 / 8%); }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: #0f172a; font-weight: 750; text-decoration: none; }
        .mark { display: grid; width: 36px; height: 36px; place-items: center; border-radius: 11px; background: #059669; color: #fff; font-size: 13px; }
        .code { margin: 36px 0 12px; color: #059669; font-size: 13px; font-weight: 700; letter-spacing: .18em; }
        h1 { margin: 0; font-size: clamp(30px, 6vw, 42px); line-height: 1.1; letter-spacing: -.04em; }
        p { margin: 16px 0 28px; color: #475569; line-height: 1.7; }
        .home { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; border-radius: 10px; background: #059669; padding: 0 18px; color: #fff; font-size: 14px; font-weight: 650; text-decoration: none; }
        .home:focus-visible { outline: 3px solid #6ee7b7; outline-offset: 3px; }
    </style>
</head>
<body>
    <main aria-labelledby="error-title">
        <a class="brand" href="{{ url('/'.$locale) }}" aria-label="Quix Code — {{ __('public.errors.back_home') }}">
            <span class="mark" aria-hidden="true">QC</span>
            <span>Quix Code</span>
        </a>
        <p class="code">{{ $status }} · QUIX CODE</p>
        <h1 id="error-title">{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="home" href="{{ url('/'.$locale) }}">{{ __('public.errors.back_home') }}</a>
    </main>
</body>
</html>
