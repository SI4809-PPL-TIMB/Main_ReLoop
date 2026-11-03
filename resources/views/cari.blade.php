@php
    $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $ikonLokasi = '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>';
@endphp
<x-app-layout :nav="'search'">
    <x-slot name="title">Reloop · {{ $keyword !== '' ? 'Cari: '.$keyword : 'Pencarian Barang' }}</x-slot>

    {{-- Top Search Bar --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white px-4 py-3">
        <form action="{{ route('cari') }}" method="GET" class="flex items-center gap-2">
            <a href="{{ route('beranda') }}" aria-label="Kembali ke Beranda"
               class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200 text-ink transition-colors hover:bg-slate-50">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <div class="relative flex-1">
                <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari kursi, buku, sepeda…" autofocus
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-9 text-sm text-ink placeholder-slate-400 focus:border-loop-dark focus:bg-white focus:outline-none focus:ring-1 focus:ring-loop-dark">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
                @if ($keyword !== '')
                    <a href="{{ route('cari') }}" aria-label="Hapus kata kunci"
                       class="absolute right-3 top-1/2 grid size-5 -translate-y-1/2 place-items-center rounded-full text-slate-400 hover:text-slate-600">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
            <button type="submit" class="rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90">
                Cari
            </button>
        </form>
    </header>

    <main class="px-4 pb-8 pt-3">
        {{-- Ringkasan hasil --}}
        <div class="mb-3 flex items-baseline justify-between text-xs text-slate-500">
            @if ($keyword !== '')
                <p>Hasil untuk <strong class="text-ink">"{{ $keyword }}"</strong></p>
                <p>{{ $barang->total() }} barang ditemukan</p>
            @else
                <p>Semua barang aktif di sekitarmu</p>
                <p>{{ $barang->total() }} barang</p>
            @endif
        </div>

        {{-- Grid Daftar Barang --}}
        @if ($barang->isNotEmpty())
            <div class="grid grid-cols-2 gap-3">
                @foreach ($barang as $b)
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm transition-shadow hover:shadow-md">
                        <div>
                            <div class="ph relative h-36 w-full overflow-hidden rounded-xl bg-slate-100">
                                @if ($b->primaryPhoto)
                                    <img src="{{ $b->primaryPhoto->url }}" alt="{{ $b->title }}" class="size-full object-cover">
                                @endif
                                @if (isset($kondisi[$b->condition]))
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $kondisi[$b->condition][1] }}">
                                        {{ $kondisi[$b->condition][0] }}
                                    </span>
                                @endif
                            </div>

                            @if ($b->user?->profile?->avg_rating > 0)
                                <p class="mt-2.5">
                                    <span class="inline-block rounded-md bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                        ★ {{ number_format($b->user->profile->avg_rating, 1) }}
                                    </span>
                                </p>
                            @endif

                            <h2 class="mt-1.5 line-clamp-2 text-sm font-semibold leading-snug text-ink" title="{{ $b->title }}">
                                {{ $b->title }}
                            </h2>

                            @if ($b->description)
                                <p class="mt-1 line-clamp-1 text-xs text-slate-400">
                                    {{ $b->description }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-2 pt-2 border-t border-slate-100">
                            <p class="flex items-center gap-1 text-[11px] text-slate-500">
                                <svg class="size-3 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>
                                <span class="truncate">
                                    {{ $b->jarak_km !== null ? number_format($b->jarak_km, 1, ',', '.').' km' : ($b->kelurahan?->nama ?? 'Lokasi belum diatur') }}
                                </span>
                            </p>
                            <p class="mt-1 text-sm font-bold text-ink">
                                {{ $rupiah($b->price) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $barang->links() }}
            </div>
        @else
            {{-- Hasil kosong ditangani (Empty State) --}}
            <div class="py-16 text-center">
                <div class="mx-auto grid size-16 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8 11h6"/>
                    </svg>
                </div>

                <h2 class="mt-4 text-base font-bold text-ink">Barang tidak ditemukan</h2>
                <p class="mx-auto mt-1.5 max-w-[280px] text-xs leading-relaxed text-slate-500">
                    @if ($keyword !== '')
                        Tidak ada barang aktif yang cocok dengan kata kunci <strong class="text-slate-800">"{{ $keyword }}"</strong> di judul maupun deskripsi.
                    @else
                        Belum ada barang aktif yang tersedia saat ini.
                    @endif
                </p>

                <div class="mt-6 flex flex-col items-center justify-center gap-2 sm:flex-row">
                    @if ($keyword !== '')
                        <a href="{{ route('cari') }}"
                           class="w-full max-w-[200px] rounded-full border border-slate-300 py-2.5 text-xs font-semibold text-ink transition-colors hover:bg-slate-50">
                            Hapus kata kunci
                        </a>
                    @endif
                    <a href="{{ route('beranda') }}"
                       class="w-full max-w-[200px] rounded-full bg-ink py-2.5 text-xs font-semibold text-white transition-opacity hover:opacity-90">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @endif
    </main>
</x-app-layout>

