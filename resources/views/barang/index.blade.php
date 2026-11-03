@php
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $tabs = ['aktif' => 'Aktif', 'dipesan' => 'Dipesan', 'terjual' => 'Terjual'];
@endphp
<x-app-layout>
    <x-slot name="title">Reloop · Barang Saya</x-slot>

    <header class="sticky top-0 z-20 bg-white px-4 pt-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('account') }}" aria-label="Kembali" class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="flex-1 text-xl font-bold">Barang Saya</h1>
            <a href="{{ route('barang.create') }}" class="rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white">+ Pasang</a>
        </div>
        <nav role="tablist" class="mt-4 grid grid-cols-3 border-b border-slate-200 text-center text-sm font-medium">
            @foreach ($tabs as $kode => $label)
                <a role="tab" aria-selected="{{ $tab === $kode ? 'true' : 'false' }}" href="{{ route('barang.index', ['tab' => $kode]) }}"
                   class="py-3 {{ $tab === $kode ? 'border-b-2 border-ink font-semibold text-ink' : 'text-slate-500' }}">{{ $label }} ({{ $jumlah[$kode] }})</a>
            @endforeach
        </nav>
    </header>

    @if (session('success'))
        <p role="status" class="mx-4 mt-4 rounded-2xl border border-loop bg-loop-soft px-4 py-3 text-sm font-medium">{{ session('success') }}</p>
    @endif

    <main class="pb-6">
        <ul class="space-y-3 px-4 pt-4">
            @forelse ($barang as $b)
                <li class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex gap-3">
                        @if ($b->primaryPhoto)
                            <img src="{{ $b->primaryPhoto->url }}" alt="" class="size-16 shrink-0 rounded-lg object-cover">
                        @else
                            <div class="ph size-16 shrink-0 rounded-lg"></div>
                        @endif
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="truncate font-semibold">{{ $b->title ?: 'Tanpa judul' }}@if ($b->status === 'draft') <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Draft</span>@endif</p>
                            <p class="font-semibold">{{ $b->price ? $rupiah($b->price) : '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $b->kelurahan?->nama ?? 'Lokasi belum diatur' }} · {{ $b->view_count }} dilihat</p>
                        </div>
                    </div>

                    @if ($b->status === 'dipesan')
                        <p class="mt-3 flex gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Sedang dipesan, tidak bisa dihapus.</p>
                    @endif

                    @if ($b->status !== 'terjual')
                        <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                            <a href="{{ route('barang.edit', $b) }}" class="flex-1 rounded-full border border-slate-300 py-2 text-center text-sm font-semibold">Ubah</a>
                            @can('markSold', $b)
                                <form method="POST" action="{{ route('barang.terjual', $b) }}" class="flex-1">@csrf
                                    <button class="w-full rounded-full bg-ink py-2 text-sm font-semibold text-white">Tandai terjual</button>
                                </form>
                            @endcan
                            @can('delete', $b)
                                <form method="POST" action="{{ route('barang.destroy', $b) }}" onsubmit="return confirm('Hapus barang ini? Tindakan ini tidak bisa dibatalkan.')">@csrf @method('DELETE')
                                    <button aria-label="Hapus" class="grid size-9 shrink-0 place-items-center rounded-full border border-red-200 text-red-600">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @endif
                </li>
            @empty
                <li class="py-16 text-center">
                    <p class="font-semibold">{{ ['aktif' => 'Tidak ada barang aktif', 'dipesan' => 'Belum ada barang dipesan', 'terjual' => 'Belum ada barang terjual'][$tab] }}</p>
                    <p class="mx-auto mt-1 max-w-[260px] text-sm text-slate-500">Barang bekas yang kamu pasang akan muncul di sini.</p>
                    @if ($tab === 'aktif')
                        <a href="{{ route('barang.create') }}" class="mt-4 inline-block rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white">Pasang barang</a>
                    @endif
                </li>
            @endforelse
        </ul>
    </main>
</x-app-layout>
