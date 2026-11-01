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

        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        },
                        colors: {
                            ink: '#15181c',
                            loop: {
                                DEFAULT: '#9be600',
                                dark: '#5f9200',
                                soft: '#f3fbe3',
                            },
                            brand: {
                                50: '#ecfbf1',
                                100: '#d3f5df',
                                200: '#a9eac2',
                                300: '#72d99c',
                                400: '#3fc476',
                                500: '#22b45a',
                                600: '#179447',
                                700: '#14753b',
                                800: '#135d32',
                                900: '#0f3d22',
                            },
                            kraft: {
                                50: '#fbf5ec',
                                100: '#f6ead9',
                                300: '#e2bf8f',
                                500: '#c8955c',
                                700: '#8a5f33',
                            },
                        },
                        boxShadow: {
                            card: '0 8px 24px -12px rgba(16, 40, 24, 0.22)',
                        }
                    }
                }
            }
        </script>

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
