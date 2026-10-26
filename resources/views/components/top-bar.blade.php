@props([
    'title' => '',
    'back' => null,
    'border' => true,
    'large' => false,
])

<header {{ $attributes->class([
    'sticky top-0 z-20 flex items-center gap-3 bg-white px-4',
    'border-b border-slate-200' => $border,
    'pt-5 pb-3' => $large,
    'py-4' => ! $large,
]) }}>
    @if ($back)
        <a href="{{ $back }}" aria-label="Kembali"
           class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-ink transition-colors hover:bg-slate-50">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" />
            </svg>
        </a>
    @endif

    <h1 class="flex-1 truncate font-bold text-ink {{ $large ? 'text-2xl' : 'text-xl' }}">{{ $title }}</h1>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
