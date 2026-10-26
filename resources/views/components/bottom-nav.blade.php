@props(['active' => null])

@php
    $tabs = [
        'home' => ['label' => 'Beranda', 'href' => '/', 'icon' => 'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10'],
        'search' => ['label' => 'Cari', 'href' => '/cari', 'icon' => 'M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z'],
        'list' => ['label' => 'Aktivitas', 'href' => '/aktivitas', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
        'chat' => ['label' => 'Pesan', 'href' => '/pesan', 'icon' => 'M21 12a8 8 0 0 1-11.6 7.1L3 20l1-4.6A8 8 0 1 1 21 12z'],
        'user' => ['label' => 'Akun', 'href' => '/profil', 'icon' => 'M20 21a8 8 0 0 0-16 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
    ];
@endphp

<nav id="bottom-nav" data-active="{{ $active }}" aria-label="Navigasi utama"
     {{ $attributes->merge(['class' => 'fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[420px] border-t border-slate-200 bg-white']) }}
     style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
    <ul class="grid grid-cols-5 pt-2">
        @foreach ($tabs as $key => $tab)
            @php($isActive = $active === $key)
            <li>
                <a href="{{ $tab['href'] }}" @if ($isActive) aria-current="page" @endif
                   class="flex flex-col items-center gap-1 py-1 {{ $isActive ? 'text-ink' : 'text-slate-400' }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                         class="{{ $isActive ? 'text-loop' : '' }}">
                        <path d="{{ $tab['icon'] }}" />
                    </svg>
                    <span class="text-[11px] {{ $isActive ? 'font-semibold' : '' }}">{{ $tab['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
