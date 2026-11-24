@php
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $rating = $mitra->rating();
    $gambar = $mitra->gambar();
    $kel = $mitra->kelurahan;
    $jamPerHari = $mitra->jam->keyBy('hari');
    $hariIni = now()->dayOfWeekIso;
    // Kembali ke daftar dengan filter terakhir bila datang dari sana.
    $kembali = str_starts_with(url()->previous(), route('pengepul.index')) && url()->previous() !== url()->current()
        ? url()->previous() : route('pengepul.index');
@endphp

@component('layouts.app', ['title' => 'Reloop · '.$mitra->nama_usaha])
    <x-top-bar title="Profil Pengepul" :back="$kembali" />

    <main class="space-y-5 px-4 pb-28 pt-5">
        <section class="flex gap-4">
            @if ($gambar)
                <img src="{{ asset('assets/illust/collectors/'.$gambar.'.png') }}" class="size-16 shrink-0 rounded-xl object-contain" width="64" height="64" alt="">
            @else
                <span class="grid size-16 shrink-0 place-items-center rounded-xl bg-loop-soft text-loop-dark" aria-hidden="true"><x-category-icon name="recycle" class="size-8" /></span>
            @endif
            <div class="min-w-0 space-y-1">
                <h1 class="text-2xl font-bold leading-tight">{{ $mitra->nama_usaha }}</h1>
                <p class="flex items-center gap-1.5 text-xs font-semibold text-brand-700">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>Terverifikasi
                </p>
                <p class="text-xs">
                    @if ($rating)
                        <span class="rounded-md bg-brand-600 px-1.5 py-0.5 font-bold text-white">★ {{ number_format($rating, 1) }}</span>
                        @if ($jumlahUlasan)<span class="text-slate-500">dari {{ $jumlahUlasan }} ulasan</span>@endif
                    @else
                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">Belum ada rating</span>
                    @endif
                </p>
            </div>
        </section>

        <dl class="space-y-3 rounded-2xl border border-slate-200 p-4 text-sm">
            <div>
                <dt class="text-slate-500">Lapak</dt>
                <dd class="font-semibold">{{ $kel ? $kel->nama.', Kec. '.$kel->kecamatan->nama.', '.$kel->kecamatan->kabupaten->nama : 'Belum diatur' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Layanan</dt>
                <dd class="font-semibold">{{ $mitra->layananLabel() }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Melayani</dt>
                <dd class="font-semibold">{{ $mitra->area->isNotEmpty() ? 'Kec. '.$mitra->area->pluck('nama')->implode(', ') : 'Belum diatur' }}</dd>
            </div>
        </dl>

        <section>
            <h2 class="font-bold">Jenis sampah &amp; harga</h2>
            @if ($mitra->harga->isNotEmpty())
                <ul class="mt-2 divide-y divide-slate-100 rounded-2xl border border-slate-200 text-sm">
                    @foreach ($mitra->harga as $h)
                        <li class="flex justify-between gap-3 px-4 py-3"><span>{{ $h->jenis }}</span><b>{{ $rupiah($h->harga_per_kg) }}/kg</b></li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-slate-500">Harga perkiraan dari pengepul, bisa berubah saat penimbangan.</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Pengepul belum mengatur harga.</p>
            @endif
        </section>

        <section>
            <h2 class="font-bold">Jam operasional</h2>
            <ul class="mt-2 divide-y divide-slate-100 rounded-2xl border border-slate-200 text-sm">
                @foreach (\App\Models\MitraPengepul::HARI as $no => $hari)
                    @php $jam = $jamPerHari->get($no); @endphp
                    <li @class(['flex justify-between gap-3 px-4 py-2.5', 'bg-loop-soft font-semibold' => $no === $hariIni, 'text-slate-400' => ! $jam && $no !== $hariIni])>
                        <span>{{ $hari }}{{ $no === $hariIni ? ' (hari ini)' : '' }}</span>
                        <span>{{ $jam ? $jam->rentang() : 'Libur' }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </main>

    {{-- 9.1 menyambungkan tombol ini ke form setoran. --}}
    <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
        <button type="button" disabled class="w-full rounded-full bg-ink py-3.5 font-semibold text-white disabled:opacity-40">Setor ke pengepul ini</button>
        <p class="mt-1.5 text-center text-xs text-slate-500">Setoran segera tersedia.</p>
    </div>
@endcomponent
