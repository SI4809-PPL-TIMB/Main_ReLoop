@props(['active' => null])

@php
    // Ikon dan gaya mengikuti ui/js/app.js (NAV_ICONS). Aktif = stroke hijau Reloop, label gelap.
    $tabs = [
        'home' => ['label' => 'Beranda', 'href' => '/', 'icon' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>'],
        'search' => ['label' => 'Cari', 'href' => '/cari', 'icon' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'],
        'list' => ['label' => 'Aktivitas', 'href' => '/aktivitas', 'icon' => '<rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M8 12h8M8 16h5"/>'],
        'chat' => ['label' => 'Pesan', 'href' => '/pesan', 'icon' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>'],
        'user' => ['label' => 'Akun', 'href' => '/profil', 'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
    ];
@endphp

<nav id="bottom-nav" data-active="{{ $active }}" aria-label="Navigasi utama"
     {{ $attributes->merge(['class' => 'fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-2 pt-2 pb-[max(12px,env(safe-area-inset-bottom))]']) }}>
    <ul class="grid grid-cols-5">
        @foreach ($tabs as $key => $tab)
            @php($on = $active === $key)
            <li>
                <a href="{{ $tab['href'] }}" @if ($on) aria-current="page" @endif
                   class="flex flex-col items-center gap-1 text-[11px] leading-none {{ $on ? 'font-semibold text-ink' : 'font-medium text-slate-400' }}">
                    <span class="grid h-8 w-14 place-items-center">
                        <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="{{ $on ? '#9be600' : 'currentColor' }}"
                             stroke-width="{{ $on ? 2 : 1.8 }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $tab['icon'] !!}</svg>
                    </span>
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
