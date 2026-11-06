<x-app-layout title="ReLoop · Pesan" :nav="'chat'">
    <x-top-bar title="Pesan" :border="false" />

    <section class="pb-6 pt-1">

        {{-- Search bar --}}
        <div class="px-4 pb-3">
            <div class="relative">
                <svg class="absolute inset-y-0 left-3.5 my-auto size-4 text-slate-400" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" placeholder="Cari percakapan…"
                       class="w-full rounded-full border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-brand-400 focus:bg-white focus:ring-1 focus:ring-brand-400">
            </div>
        </div>

        {{-- Daftar Percakapan (data statis untuk desain) --}}
        <ul class="divide-y divide-slate-100">

            {{-- Item percakapan 1 (ada pesan belum dibaca) --}}
            <li>
                <a href="/pesan/1" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                    {{-- Foto Barang --}}
                    <div class="relative shrink-0">
                        <div class="size-14 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <div class="flex size-full items-center justify-center bg-kraft-100 text-2xl">🪑</div>
                        </div>
                    </div>

                    {{-- Info Percakapan --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-ink">Kursi Kayu Jati Antik</span>
                            <span class="shrink-0 text-[11px] font-medium text-brand-700">09:14</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="truncate text-xs text-slate-500">Budi: Masih tersedia kak? Saya mau beli...</p>
                            <span class="ml-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white">3</span>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <div class="flex size-4 items-center justify-center rounded-full bg-slate-200 overflow-hidden">
                                <span class="text-[8px] font-bold text-slate-500">B</span>
                            </div>
                            <span class="text-[11px] text-slate-400">Budi Santoso</span>
                        </div>
                    </div>
                </a>
            </li>

            {{-- Item percakapan 2 (pesan sudah dibaca, pesan terakhir dari saya) --}}
            <li>
                <a href="/pesan/2" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                    <div class="relative shrink-0">
                        <div class="size-14 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <div class="flex size-full items-center justify-center bg-brand-50 text-2xl">📱</div>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-ink">HP Samsung Galaxy S21</span>
                            <span class="shrink-0 text-[11px] text-slate-400">Kemarin</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="truncate text-xs text-slate-400">
                                <span class="text-slate-500">Kamu:</span> Siap, bisa COD besok pagi
                            </p>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <div class="flex size-4 items-center justify-center rounded-full bg-brand-100 overflow-hidden">
                                <span class="text-[8px] font-bold text-brand-700">D</span>
                            </div>
                            <span class="text-[11px] text-slate-400">Dewi Rahayu</span>
                        </div>
                    </div>
                </a>
            </li>

            {{-- Item percakapan 3 --}}
            <li>
                <a href="/pesan/3" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                    <div class="relative shrink-0">
                        <div class="size-14 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <div class="flex size-full items-center justify-center bg-amber-50 text-2xl">🪴</div>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-ink">Tanaman Hias Monstera</span>
                            <span class="shrink-0 text-[11px] text-slate-400">Sen</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="truncate text-xs text-slate-400">Oke kak, ditunggu ya!</p>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <div class="flex size-4 items-center justify-center rounded-full bg-amber-100 overflow-hidden">
                                <span class="text-[8px] font-bold text-amber-700">A</span>
                            </div>
                            <span class="text-[11px] text-slate-400">Andi Wijaya</span>
                        </div>
                    </div>
                </a>
            </li>

        </ul>

        {{-- Empty State (tampil jika tidak ada percakapan) --}}
        {{-- Uncomment baris di bawah untuk preview empty state --}}
        {{--
        <div class="flex flex-col items-center justify-center gap-4 px-8 py-24 text-center">
            <div class="flex size-20 items-center justify-center rounded-full bg-slate-100 text-4xl">💬</div>
            <div>
                <p class="font-semibold text-ink">Belum ada percakapan</p>
                <p class="mt-1 text-sm text-slate-500">Mulai chat dengan mengetuk tombol Chat di halaman detail barang.</p>
            </div>
        </div>
        --}}

    </section>
</x-app-layout>

