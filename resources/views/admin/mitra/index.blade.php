@php
    $tab = ['' => 'Semua'] + \App\Models\MitraPengepul::STATUS;
    $tab[\App\Models\MitraPengepul::MENUNGGU] = 'Menunggu';
@endphp

@component('layouts.app', ['title' => 'ReLoop · Kelola Mitra Pengepul'])
    <x-slot name="header">
        <x-top-bar title="Mitra Pengepul" :back="route('account')">
            <x-slot name="actions">
                <a href="{{ route('admin.mitra.create') }}" class="flex items-center gap-1.5 rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah
                </a>
            </x-slot>
        </x-top-bar>
    </x-slot>

    <main class="space-y-4 px-4 pb-10 pt-4">
        @if (session('sukses'))
            <div role="alert" class="rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-medium text-brand-800">{{ session('sukses') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.mitra.index') }}" role="search">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <label class="flex items-center gap-2 rounded-full border border-slate-200 px-4 focus-within:border-ink">
                <svg class="size-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="sr-only">Cari mitra</span>
                <input type="search" name="q" value="{{ $cari }}" placeholder="Cari nama usaha, pemilik, atau email" class="w-full bg-transparent py-3 text-sm outline-none">
            </label>
        </form>

        <nav aria-label="Filter status" class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4">
            @foreach ($tab as $kode => $label)
                @php($aktif = ($status ?? '') === $kode)
                <a href="{{ route('admin.mitra.index', array_filter(['status' => $kode, 'q' => $cari])) }}" @if ($aktif) aria-current="page" @endif
                   class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold {{ $aktif ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600' }}">
                    {{ $label }}
                    <span class="ml-1 {{ $aktif ? 'text-white/70' : 'text-slate-400' }}">{{ $kode === '' ? $jumlah->sum() : ($jumlah[$kode] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>

        @forelse ($mitra as $m)
            <a href="{{ route('admin.mitra.show', $m) }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 p-4 transition-colors hover:bg-slate-50">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-loop-soft text-loop-dark">
                    <x-category-icon name="recycle" class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold">{{ $m->nama_usaha }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $m->user->name }} · {{ $m->kelurahan ? $m->kelurahan->nama.', '.$m->kelurahan->kecamatan->nama : 'Lokasi belum diatur' }}</span>
                </span>
                <x-status-mitra :status="$m->status" />
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center">
                <p class="font-semibold">{{ $cari !== '' ? 'Mitra tidak ditemukan' : 'Belum ada mitra' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $cari !== '' ? 'Coba kata kunci lain atau ganti filter status.' : 'Tambahkan mitra pengepul pertama beserta akunnya.' }}</p>
            </div>
        @endforelse
    </main>
@endcomponent
