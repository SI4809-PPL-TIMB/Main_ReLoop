@component('layouts.app', ['title' => 'ReLoop · '.($tipe === 'bekas' ? 'Pasang Barang' : 'Mitra Pengepul'), 'bodyClass' => 'bg-[#e9ebe8]'])
    <x-slot name="header">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
            <a href="{{ route('kategori.index', ['tipe' => $tipe]) }}" aria-label="Kembali ke pilihan" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="flex-1 text-xl font-bold">{{ $tipe === 'bekas' ? 'Pasang Barang' : 'Mitra Pengepul' }}</h1>
        </header>
    </x-slot>
    <main class="space-y-5 px-4 pb-8 pt-4">
        <div class="rounded-xl border border-loop bg-loop-soft p-3 text-sm">
            <span class="block text-xs text-slate-500">Jenis barang</span>
            <strong>{{ $label }}</strong>
        </div>
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <h2 class="text-lg font-bold">{{ $tipe === 'bekas' ? 'Form pasang barang segera hadir' : 'Pencarian mitra segera hadir' }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $tipe === 'bekas' ? 'Nantinya kamu bisa memilih kategori dan mengisi detail barang di sini. Untuk sementara, pemasangan barang belum tersedia.' : 'Nantinya kamu bisa memilih jenis material dan mencari mitra pengepul yang sesuai. Untuk sementara, layanan pencarian belum tersedia.' }}</p>
            <a href="{{ route('kategori.index', ['tipe' => $tipe]) }}" class="mt-5 block rounded-full bg-ink px-4 py-3.5 text-center font-semibold text-white">Kembali ke pilihan</a>
        </section>
    </main>
@endcomponent
