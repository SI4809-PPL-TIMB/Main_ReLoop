@use('App\Models\MitraPengepul')
@php
    $jam = $mitra->jam->keyBy('hari');
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
@endphp

@component('layouts.app', ['title' => 'ReLoop · '.$mitra->nama_usaha])
    <x-slot name="header">
        <x-top-bar title="Detail Mitra" :back="route('admin.mitra.index')">
            <x-slot name="actions">
                <a href="{{ route('admin.mitra.edit', $mitra) }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Ubah</a>
            </x-slot>
        </x-top-bar>
    </x-slot>

    <main class="space-y-5 px-4 pb-10 pt-4">
        @if (session('sukses'))
            <div role="alert" class="rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-medium text-brand-800">{{ session('sukses') }}</div>
        @endif
        @if ($errors->any())
            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        {{-- Identitas --}}
        <section class="rounded-2xl border border-slate-200 p-4">
            <div class="flex items-start gap-3">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-loop-soft text-loop-dark"><x-category-icon name="recycle" class="size-6" /></span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold leading-tight">{{ $mitra->nama_usaha }}</h2>
                    <p class="text-sm text-slate-500">{{ $mitra->user->name }}</p>
                </div>
                <x-status-mitra :status="$mitra->status" />
            </div>
            <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex gap-3"><dt class="w-20 shrink-0 text-slate-500">Email</dt><dd class="min-w-0 break-words">{{ $mitra->user->email }}</dd></div>
                <div class="flex gap-3"><dt class="w-20 shrink-0 text-slate-500">Nomor HP</dt><dd>{{ $mitra->telepon }}</dd></div>
                <div class="flex gap-3"><dt class="w-20 shrink-0 text-slate-500">Alamat</dt><dd class="min-w-0">{{ $mitra->alamat }}@if ($mitra->kelurahan)<span class="block text-slate-500">{{ $mitra->kelurahan->breadcrumb() }}</span>@endif</dd></div>
            </dl>
        </section>

        {{-- Verifikasi --}}
        <section class="space-y-3">
            <h2 class="px-1 text-xs font-medium text-slate-500">Verifikasi</h2>
            @if ($mitra->status === MitraPengepul::TERVERIFIKASI)
                <p class="rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">Terverifikasi {{ $mitra->diverifikasi_pada?->translatedFormat('j M Y, H.i') }}{{ $mitra->verifikator ? ' oleh '.$mitra->verifikator->name : '' }}. Mitra ini tampil di pencarian pengepul.</p>
            @elseif ($mitra->status === MitraPengepul::DITOLAK)
                <div class="rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-semibold">Ditolak {{ $mitra->diverifikasi_pada?->translatedFormat('j M Y') }}</p>
                    <p class="mt-1">{{ $mitra->catatan_verifikasi }}</p>
                </div>
            @else
                <p class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800">Belum diverifikasi. Periksa data usaha sebelum memverifikasi. Mitra belum muncul di pencarian.</p>
            @endif

            <div class="grid grid-cols-2 gap-2">
                @if ($mitra->status !== MitraPengepul::TERVERIFIKASI)
                    <form method="POST" action="{{ route('admin.mitra.verifikasi', $mitra) }}" class="contents">
                        @csrf
                        <input type="hidden" name="status" value="{{ MitraPengepul::TERVERIFIKASI }}">
                        <button type="submit" data-loading-text="Memverifikasi…" class="rounded-full bg-ink py-3 text-sm font-semibold text-white">Verifikasi</button>
                    </form>
                @endif
                @if ($mitra->status !== MitraPengepul::DITOLAK)
                    <button type="button" data-open="tolak" class="rounded-full border border-red-200 py-3 text-sm font-semibold text-red-600">Tolak</button>
                @endif
                @if ($mitra->status !== MitraPengepul::MENUNGGU)
                    <form method="POST" action="{{ route('admin.mitra.verifikasi', $mitra) }}" class="contents">
                        @csrf
                        <input type="hidden" name="status" value="{{ MitraPengepul::MENUNGGU }}">
                        <button type="submit" class="rounded-full border border-slate-300 py-3 text-sm font-semibold">Kembalikan ke menunggu</button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Pengaturan dari pengepul (hanya baca) --}}
        <section class="space-y-3">
            <h2 class="px-1 text-xs font-medium text-slate-500">Pengaturan layanan <span class="font-normal">· diisi pengepul</span></h2>
            <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200 text-sm">
                <div class="p-4">
                    <p class="text-slate-500">Layanan</p>
                    <p class="mt-1 font-semibold">{{ $mitra->layananLabel() }}</p>
                </div>
                <div class="p-4">
                    <p class="text-slate-500">Area layanan</p>
                    @if ($mitra->area->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($mitra->area as $kec)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium">{{ $kec->nama }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-1 text-slate-400">Belum diatur</p>
                    @endif
                </div>
                <div class="p-4">
                    <p class="text-slate-500">Harga per kg</p>
                    @forelse ($mitra->harga->sortBy('category.sort_order') as $h)
                        <div class="mt-2 flex items-center justify-between"><span>{{ $h->category->name }}</span><span class="font-semibold">{{ $rp($h->harga_per_kg) }}/kg</span></div>
                    @empty
                        <p class="mt-1 text-slate-400">Belum diatur</p>
                    @endforelse
                </div>
                <div class="p-4">
                    <p class="text-slate-500">Jam operasional</p>
                    @if ($jam->isNotEmpty())
                        @foreach (MitraPengepul::HARI as $no => $hari)
                            <div class="mt-2 flex items-center justify-between"><span>{{ $hari }}</span><span class="{{ isset($jam[$no]) ? 'font-semibold' : 'text-slate-400' }}">{{ isset($jam[$no]) ? $jam[$no]->rentang() : 'Libur' }}</span></div>
                        @endforeach
                    @else
                        <p class="mt-1 text-slate-400">Belum diatur</p>
                    @endif
                </div>
            </div>
        </section>

        <button type="button" data-open="hapus" class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 text-left text-sm font-semibold text-red-500">Hapus mitra</button>
    </main>

    <dialog id="tolak" class="m-auto w-[min(92vw,360px)] rounded-3xl p-0" @if ($errors->has('catatan')) data-auto-open @endif>
        <form method="POST" action="{{ route('admin.mitra.verifikasi', $mitra) }}" class="p-6">
            @csrf
            <input type="hidden" name="status" value="{{ MitraPengepul::DITOLAK }}">
            <h2 class="text-lg font-bold">Tolak mitra ini?</h2>
            <p class="mt-1 text-sm text-slate-500">Alasan akan dilihat pengepul di halaman pengaturannya.</p>
            <label class="mt-4 block">
                <span class="text-sm font-semibold">Alasan penolakan *</span>
                <textarea name="catatan" rows="3" maxlength="255" required placeholder="mis. Alamat lapak tidak bisa dihubungi" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-ink">{{ old('catatan') }}</textarea>
            </label>
            <div class="mt-5 grid gap-2">
                <button type="submit" data-loading-text="Menolak…" class="rounded-full bg-red-500 py-3 font-semibold text-white">Tolak mitra</button>
                <button type="button" data-close class="rounded-full border border-slate-300 py-3 font-semibold">Batal</button>
            </div>
        </form>
    </dialog>

    <dialog id="hapus" class="m-auto w-[min(92vw,360px)] rounded-3xl p-0">
        <div class="p-6 text-center">
            <h2 class="text-lg font-bold">Hapus {{ $mitra->nama_usaha }}?</h2>
            <p class="mt-1 text-sm text-slate-500">Data mitra, harga, area, dan jam operasional dihapus. Akun {{ $mitra->user->email }} tetap ada sebagai pengguna biasa.</p>
            <div class="mt-5 grid gap-2">
                <form method="POST" action="{{ route('admin.mitra.destroy', $mitra) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" data-loading-text="Menghapus…" class="w-full rounded-full bg-red-500 py-3 font-semibold text-white">Ya, hapus</button>
                </form>
                <button type="button" data-close class="rounded-full border border-slate-300 py-3 font-semibold">Batal</button>
            </div>
        </div>
    </dialog>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-open]').forEach((tombol) =>
                tombol.addEventListener('click', () => document.getElementById(tombol.dataset.open)?.showModal()));
            document.querySelectorAll('dialog').forEach((dialog) => {
                if (dialog.hasAttribute('data-auto-open')) dialog.showModal();
                dialog.addEventListener('click', (e) => {
                    if (e.target === dialog || e.target.closest('[data-close]')) dialog.close();
                });
            });
        </script>
    @endpush
@endcomponent
