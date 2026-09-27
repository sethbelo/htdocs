<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<title>{{ filled($title ?? null) ? $title.' — ' : '' }}{{ \App\Models\Setting::get('company.name', config('app.name')) }}</title>
@if ($favicon = \App\Models\Setting::get('company.favicon_path'))
    <link rel="icon" href="{{ Storage::disk('public')->url($favicon) }}" />
@else
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
@endif
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
<link rel="manifest" href="/site.webmanifest" />
<meta name="theme-color" content="#E6007E" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
