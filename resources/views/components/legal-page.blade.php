@props(['title', 'updated' => '16 November 2026'])
@php
    // PPLTIMB-135 · Tombol kembali ke halaman sebelumnya (hanya URL internal, bukan halaman ini sendiri).
    $previous = url()->previous();
    $kembali  = (str_starts_with($previous, url('/')) && $previous !== url()->current()) ? $previous : url('/');
@endphp
<x-app-layout>
    <x-slot name="title">{{ $title }} — ReLoop</x-slot>

    <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-4">
        <a href="{{ $kembali }}" id="legal-kembali" aria-label="Kembali"
           class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200 transition-colors hover:bg-slate-50">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="flex-1 truncate text-xl font-bold">{{ $title }}</h1>
    </header>

    <main class="px-4 pt-5 pb-12 text-sm leading-relaxed text-slate-600">
        <p class="text-xs text-slate-500">Terakhir diperbarui {{ $updated }}</p>
        <div class="mt-4 space-y-6 [&_h2]:text-base [&_h2]:font-bold [&_h2]:text-ink [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5 [&_p]:mt-2">
            {{ $slot }}
        </div>
    </main>

    @push('scripts')
    <script>
        // Kembali lewat riwayat browser agar isian halaman sebelumnya (mis. form daftar) tetap utuh.
        document.getElementById('legal-kembali')?.addEventListener('click', (e) => {
            let dariSitusIni = false;
            try { dariSitusIni = document.referrer && new URL(document.referrer).origin === location.origin; } catch (_) {}
            if (dariSitusIni && history.length > 1) {
                e.preventDefault();
                history.back();
            }
        });
    </script>
    @endpush
</x-app-layout>

