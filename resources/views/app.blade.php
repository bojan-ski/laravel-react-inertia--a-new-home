<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ config('seo.theme_color') }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('favicon-16.png') }}" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <x-inertia::head>
        @foreach ($page['props']['head'] ?? [] as $element)
            {!! $element !!}
        @endforeach
    </x-inertia::head>

    @fonts
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <x-inertia::app />
</body>

</html>
