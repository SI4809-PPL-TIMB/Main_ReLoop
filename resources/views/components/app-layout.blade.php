@props(['nav' => null, 'title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <meta name="theme-color" content="#ffffff">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <script src="https://cdn.tailwindcss.com/3.4.16"></script>
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
            };
        </script>
        <style>
            body { -webkit-tap-highlight-color: transparent; }
            [hidden] { display: none !important; }
            fieldset { min-width: 0; }
            .no-scrollbar { scrollbar-width: none; }
            .no-scrollbar::-webkit-scrollbar { display: none; }
            dialog[open] { animation: sheet-up .22s ease-out; }
            dialog::backdrop { background: rgb(15 23 18 / .45); }
            @keyframes sheet-up { from { transform: translateY(24px); opacity: 0; } }
            .ph { background: #f1f5f9 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='16' rx='2.5'/%3E%3Ccircle cx='9' cy='10' r='1.8'/%3E%3Cpath d='m4 18 5-5 4 4 3-3 4 4'/%3E%3C/svg%3E") center / 34px no-repeat; }
            input:user-invalid, select:user-invalid, textarea:user-invalid { border-color: #ef4444 !important; }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-[#e9ebe8] font-sans text-ink antialiased">
        <div class="relative mx-auto min-h-screen max-w-[420px] overflow-x-hidden bg-white {{ $nav ? 'pb-24' : '' }}">
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
