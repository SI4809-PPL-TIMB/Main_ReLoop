<x-app-layout title="ReLoop · Chat" bodyClass="bg-white">

    {{-- Top Bar: Tombol kembali + Info barang + Avatar lawan bicara --}}
    <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3">
        {{-- Tombol Kembali --}}
        <a href="/pesan" aria-label="Kembali"
           class="flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-ink transition hover:bg-slate-50">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>

        {{-- Info Barang (mini card di header) --}}
        <a href="/barang/1" class="flex min-w-0 flex-1 items-center gap-2.5 overflow-hidden">
            <div class="size-10 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-kraft-100">
                <div class="flex size-full items-center justify-center text-xl">🪑</div>
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-ink">Kursi Kayu Jati Antik</p>
                <p class="text-xs text-brand-600 font-medium">Rp 350.000</p>
            </div>
        </a>

        {{-- Avatar lawan bicara --}}
        <div class="shrink-0 flex size-9 items-center justify-center rounded-full bg-brand-100 border border-brand-300 text-xs font-bold text-brand-800">
            B
        </div>
    </header>

    {{-- Area Chat (scroll) --}}
    <main class="flex flex-col gap-3 px-4 py-4"
          style="min-height: calc(100dvh - 64px - 72px); padding-bottom: 80px;">

        {{-- Label Tanggal --}}
        <div class="flex items-center gap-3 py-1">
            <div class="h-px flex-1 bg-slate-100"></div>
            <span class="text-[11px] text-slate-400">Hari ini</span>
            <div class="h-px flex-1 bg-slate-100"></div>
        </div>

        {{-- Pesan masuk (dari lawan bicara) --}}
        <div class="flex items-end gap-2 self-start">
            <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-800">
                B
            </div>
            <div class="max-w-[72%] space-y-1">
                <div class="rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-2.5 text-sm text-ink">
                    Halo kak, kursinya masih tersedia kak? 😊
                </div>
                <span class="ml-1 block text-[10px] text-slate-400">09:10</span>
            </div>
        </div>

        {{-- Pesan masuk (dari lawan bicara, lanjutan) --}}
        <div class="flex items-end gap-2 self-start">
            <div class="size-7 shrink-0"></div>{{-- spacer avatar --}}
            <div class="max-w-[72%] space-y-1">
                <div class="rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-2.5 text-sm text-ink">
                    Kalau masih ada, bisa COD ga kak di daerah Depok?
                </div>
                <span class="ml-1 block text-[10px] text-slate-400">09:11</span>
            </div>
        </div>

        {{-- Pesan keluar (dari saya / user login) --}}
        <div class="flex flex-col items-end gap-1 self-end max-w-[72%]">
            <div class="rounded-2xl rounded-br-sm bg-ink px-4 py-2.5 text-sm text-white">
                Masih ada kak! Bisa COD, lokasi saya di Beji, Depok 🙂
            </div>
            <div class="flex items-center gap-1 mr-1">
                <span class="text-[10px] text-slate-400">09:14</span>
                {{-- Centang dua = sudah dibaca --}}
                <svg class="size-3.5 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12l5 5L17 7"/><path d="M9 12l5 5L23 7"/>
                </svg>
            </div>
        </div>

        {{-- Pesan masuk --}}
        <div class="flex items-end gap-2 self-start">
            <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-800">
                B
            </div>
            <div class="max-w-[72%] space-y-1">
                <div class="rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-2.5 text-sm text-ink">
                    Wah deket kak! Boleh ditawar ga kak? 🙏
                </div>
                <span class="ml-1 block text-[10px] text-slate-400">09:14</span>
            </div>
        </div>

        {{-- Pesan keluar --}}
        <div class="flex flex-col items-end gap-1 self-end max-w-[72%]">
            <div class="rounded-2xl rounded-br-sm bg-ink px-4 py-2.5 text-sm text-white">
                Boleh, harga bisa diskusi 😄
            </div>
            <div class="flex items-center gap-1 mr-1">
                <span class="text-[10px] text-slate-400">09:15</span>
                {{-- Centang satu = terkirim, belum dibaca --}}
                <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12l5 5L17 7"/>
                </svg>
            </div>
        </div>

    </main>

    {{-- Input Pesan (sticky di bawah) --}}
    <div class="fixed inset-x-0 bottom-0 mx-auto max-w-[420px] border-t border-slate-200 bg-white px-4 py-3"
         style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
        <form class="flex items-center gap-2">
            @csrf
            <input type="text" name="body" placeholder="Ketik pesan…" autocomplete="off"
                   class="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:bg-white focus:ring-1 focus:ring-brand-400">
            <button type="submit"
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm transition hover:bg-brand-600 active:scale-95">
                <svg class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="M22 2 11 13"/>
                </svg>
            </button>
        </form>
    </div>

</x-app-layout>

