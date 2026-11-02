@component('layouts.app', ['title' => 'ReLoop · Pilih Kategori', 'bodyClass' => 'bg-[#e9ebe8]'])
    <x-slot name="header">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
            <a href="{{ route('beranda') }}" aria-label="Kembali ke beranda" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="flex-1 text-xl font-bold">Pilih Kategori</h1>
        </header>
    </x-slot>
    <livewire:pilih-alur />
@endcomponent
