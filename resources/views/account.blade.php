@php
    $kel = $profil?->kelurahan;
    $lokasi = $kel ? $kel->nama.', '.$kel->kecamatan->nama : 'Belum atur lokasi';

    // Tautan '#' = halamannya belum dibuat di Sprint 1.
    $grup = [
        'Jual beli' => [
            ['Barang saya', '#', '<path d="m12 3 9 5-9 5-9-5z"/><path d="M3 8v9l9 5 9-5V8M12 13v9M7.5 5.5l9 5"/>'],
            ['Pesanan saya', '#', '<path d="M6 4h12v17l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>'],
        ],
        'Daur ulang' => [
            ['Riwayat setoran', '#', '<path d="M3 11a9 9 0 1 1 2.6 7.4M3 4v7h7"/><path d="M12 7v5l3 2"/>'],
            ['Jadi pengepul mitra', '#', '<path d="M3 9 5 4h14l2 5M3 9h18v11H3zM9 20v-6h6v6"/>'],
        ],
        'Akun' => [
            ['Lokasi & alamat', route('lokasi', ['from' => 'profil']), '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>'],
            ['Notifikasi', '#', '<path d="M6 16V10a6 6 0 0 1 12 0v6l2 2H4zM10 21h4M12 2v2"/>'],
            ['Bantuan', '#', '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4 2c-1 .6-1.5 1-1.5 2.5M12 17h.01"/>'],
            ['Syarat & privasi', '#', '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>'],
        ],
    ];
@endphp

@component('layouts.app', ['title' => 'ReLoop · Akun', 'nav' => 'user'])
    <x-top-bar title="Akun" :border="false" large />

    <div class="flex items-center gap-3 px-4 pt-1">
        @if ($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-16 shrink-0 rounded-full object-cover">
        @else
            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-100 text-lg font-bold uppercase text-brand-800" aria-label="Avatar inisial: {{ $user->initials() }}">
                {{ $user->initials() }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="truncate text-lg font-bold">{{ $user->name }}</p>
            <p class="truncate text-sm text-slate-500">{{ $lokasi }}</p>
        </div>
        <a href="{{ route('account.edit') }}" class="shrink-0 rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Ubah profil</a>
    </div>

    <main class="space-y-5 px-4 pt-5">
        @if (session('sukses'))
            <div role="alert" class="rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-medium text-brand-800">{{ session('sukses') }}</div>
        @endif

        @foreach ($grup as $judul => $menu)
            <section>
                <h2 class="px-1 pb-2 text-xs font-medium text-slate-500">{{ $judul }}</h2>
                <ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                    @foreach ($menu as [$label, $href, $ikon])
                        <li>
                            <a href="{{ $href }}" class="flex items-center gap-3 px-4 py-3.5 text-sm font-medium">
                                <svg class="size-5 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
                                <span class="flex-1">{{ $label }}</span>
                                <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <ul class="rounded-2xl border border-slate-200">
            <li>
                <button type="button" data-open="keluar" class="flex w-full items-center gap-3 px-4 py-3.5 text-left text-sm font-semibold text-red-500">
                    <svg class="size-5 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4H4v16h5M9 12h12M17 8l4 4-4 4"/></svg>
                    <span class="flex-1">Keluar</span>
                </button>
            </li>
        </ul>

        <p class="pb-2 text-center text-xs text-slate-400">Reloop v0.1.0</p>
    </main>

    <dialog id="keluar" class="w-[min(92vw,360px)] rounded-3xl p-0">
        <div class="p-6 text-center">
            <h2 class="text-lg font-bold">Keluar dari Reloop?</h2>
            <p class="mt-1 text-sm text-slate-500">Streak dan barangmu tetap aman. Masuk lagi kapan saja dengan emailmu.</p>
            <div class="mt-5 grid gap-2">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-full bg-red-500 py-3 font-semibold text-white">Ya, keluar</button>
                </form>
                <button type="button" data-close class="rounded-full border border-slate-300 py-3 font-semibold">Batal</button>
            </div>
        </div>
    </dialog>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-open]').forEach((tombol) =>
                tombol.addEventListener('click', () => document.getElementById(tombol.dataset.open)?.showModal()));
            document.querySelectorAll('dialog').forEach((dialog) =>
                dialog.addEventListener('click', (e) => {
                    if (e.target === dialog || e.target.closest('[data-close]')) dialog.close();
                }));
        </script>
    @endpush
@endcomponent
