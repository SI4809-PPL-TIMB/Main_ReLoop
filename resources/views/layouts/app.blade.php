@props(['nav' => null])
<!DOCTYPE html>
<html lang="{{ $language ?? str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="theme-color" content="#ffffff">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="{{ $bodyClass ?? 'bg-slate-100' }} font-sans text-ink antialiased">
        <div class="{{ $shellClass ?? 'mx-auto min-h-screen max-w-[420px] bg-white'.($nav ? ' pb-24' : '') }}">
            {{ $header ?? '' }}

            {{ $slot }}
        </div>

        @if ($nav)
            <x-bottom-nav :active="$nav" />
        @endif

        @livewireScripts
        @stack('scripts')
    </body>
</html>
