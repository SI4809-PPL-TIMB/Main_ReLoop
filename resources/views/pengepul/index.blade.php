@php
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $angka = fn ($n) => number_format($n, 0, ',', '.');
    $namaDaerah = $kecamatan ? 'Kec. '.$kecamatan->nama.', '.$kecamatan->kabupaten->nama : null;
    // URL dengan filter saat ini, diganti sebagian.
    $url = fn (array $ganti) => route('pengepul.index', array_filter(array_merge([
        'kecamatan_id' => $dariProfil ? null : $kecamatan?->id,
        'jenis'        => $jenis?->slug,
    ], $ganti)));
@endphp

@component('layouts.app', ['title' => 'Reloop · Cari Pengepul'])
    <x-slot name="header">
        <header class="sticky top-0 z-20 space-y-3 border-b border-slate-100 bg-white px-4 pb-3 pt-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('beranda', ['mode' => 'daur']) }}" aria-label="Kembali" class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="flex-1 text-xl font-bold">Cari Pengepul</h1>
            </div>

            <button type="button" data-open="daerah" class="flex w-full items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-left text-sm">
                <svg class="size-4 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                <span class="min-w-0 flex-1 truncate"><span class="text-slate-500">Daerah</span> <b>{{ $namaDaerah ?? 'Semua daerah' }}</b></span>
                <span class="shrink-0 font-semibold text-brand-700">Ubah</span>
            </button>

            <nav aria-label="Jenis sampah" class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-0.5 text-sm font-semibold">
                <a href="{{ $url(['jenis' => null]) }}" data-busy-target="#hasil-pengepul" @if (! $jenis) aria-current="true" @endif
                   @class(['shrink-0 rounded-full px-4 py-2', 'bg-ink text-white' => ! $jenis, 'border border-slate-200' => $jenis])>Semua</a>
                @foreach ($jenisDaftar as $j)
                    @php $aktif = $jenis?->id === $j->id; @endphp
                    <a href="{{ $url(['jenis' => $j->slug]) }}" data-busy-target="#hasil-pengepul" @if ($aktif) aria-current="true" @endif
                       @class(['shrink-0 rounded-full px-4 py-2', 'bg-ink text-white' => $aktif, 'border border-slate-200' => ! $aktif])>{{ $j->name }}</a>
                @endforeach
            </nav>
        </header>
    </x-slot>

    <main id="hasil-pengepul" data-busy-label="Memuat pengepul…" class="space-y-3 px-4 pb-10 pt-4">
        <div data-busy-show hidden class="space-y-3">
            @for ($i = 0; $i < 3; $i++)<div class="reloop-skeleton h-36 rounded-2xl"></div>@endfor
        </div>

        <div data-busy-hide class="space-y-3">
            @if ($mitra->isNotEmpty())
                <p class="text-sm"><b>{{ $mitra->count() }} pengepul</b> <span class="text-slate-500">terverifikasi{{ $kecamatan ? ' melayani Kec. '.$kecamatan->nama : '' }}{{ $jenis ? ', menerima '.mb_strtolower($jenis->name) : '' }}</span></p>
            @endif

            @forelse ($mitra as $m)
                @php
                    $hargaList = $m->harga->pluck('harga_per_kg');
                    $rating = $m->rating();
                    $gambar = $m->gambar();
                @endphp
                <a href="{{ route('pengepul.show', $m) }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:bg-slate-50">
                    <div class="flex gap-3">
                        @if ($gambar)
                            <img src="{{ asset('assets/illust/collectors/'.$gambar.'.png') }}" class="size-12 shrink-0 rounded-lg object-contain" width="48" height="48" alt="">
                        @else
                            <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-loop-soft text-loop-dark" aria-hidden="true"><x-category-icon name="recycle" class="size-6" /></span>
                        @endif
                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="truncate font-semibold">{{ $m->nama_usaha }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $m->kelurahan ? 'Lapak di '.$m->kelurahan->nama.', '.$m->kelurahan->kecamatan->nama : 'Lokasi lapak belum diatur' }}
                                · <b class="font-semibold text-ink">{{ \App\Models\MitraPengepul::KEDEKATAN[$m->jarak] }}</b>
                            </p>
                            <p class="text-xs">
                                @if ($rating)
                                    <span class="rounded-md bg-brand-600 px-1.5 py-0.5 font-bold text-white">★ {{ number_format($rating, 1) }}</span>
                                @else
                                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">Belum ada rating</span>
                                @endif
                                <span class="text-slate-500">· {{ $m->layananLabel() }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-dashed border-slate-300 pt-3">
                        <p class="shrink-0 font-semibold">
                            @if ($m->harga_jenis)
                                {{ $rupiah($m->harga_jenis) }}<span class="text-xs font-normal text-slate-500">/kg {{ mb_strtolower($jenis->name) }}</span>
                            @elseif ($hargaList->isNotEmpty())
                                {{ $hargaList->min() === $hargaList->max() ? $rupiah($hargaList->min()) : $rupiah($hargaList->min()).'–'.$angka($hargaList->max()) }}<span class="text-xs font-normal text-slate-500">/kg</span>
                            @else
                                <span class="text-sm font-normal text-slate-500">Harga belum diatur</span>
                            @endif
                        </p>
                        <p class="truncate text-right text-xs text-slate-500">{{ $m->harga->map(fn ($h) => $h->category?->name)->filter()->implode(', ') }}</p>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-10 text-center">
                    <p class="font-semibold">Belum ada pengepul {{ $jenis ? 'yang menerima '.mb_strtolower($jenis->name).' ' : '' }}di daerah ini</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ganti daerah{{ $jenis ? ' atau pilih semua jenis sampah' : '' }}.</p>
                    <div class="mt-5 flex flex-col items-center gap-2">
                        <button type="button" data-open="daerah" class="w-full max-w-xs rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">Ubah daerah</button>
                        @if ($jenis)
                            <a href="{{ $url(['jenis' => null]) }}" data-busy-target="#hasil-pengepul" class="py-2 text-sm font-semibold text-slate-500 hover:text-ink">Semua jenis sampah</a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
    </main>

    {{-- Pilih daerah: cari desa/kecamatan/kota, hasil dikelompokkan per kecamatan (area layanan mitra = kecamatan). --}}
    <dialog id="daerah" class="m-0 mt-auto max-h-[85dvh] w-full max-w-[420px] rounded-t-3xl p-0 sm:mx-auto">
        <div class="flex max-h-[85dvh] flex-col p-5">
            <div class="mx-auto mb-4 h-1 w-10 shrink-0 rounded-full bg-slate-200"></div>
            <h2 class="shrink-0 text-lg font-bold">Pilih daerah</h2>
            <input id="daerah-cari" type="search" placeholder="Cari kecamatan atau kota, mis. Beji" autocomplete="off"
                   class="mt-3 w-full shrink-0 rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-ink">
            <ul id="daerah-hasil" class="mt-3 min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto text-sm" aria-live="polite"></ul>
            @if (! $dariProfil)
                <a href="{{ route('pengepul.index', array_filter(['jenis' => $jenis?->slug])) }}" class="mt-3 shrink-0 text-center text-sm font-semibold text-brand-700">Pakai lokasi di profilku</a>
            @endif
            <button type="button" data-close class="mt-3 w-full shrink-0 rounded-full border border-slate-300 py-3 font-semibold">Batal</button>
        </div>
    </dialog>

    @push('scripts')
        <script>
            (() => {
                const dialog = document.getElementById('daerah');
                const input = document.getElementById('daerah-cari');
                const hasil = document.getElementById('daerah-hasil');
                const tujuan = new URL(@json($url(['kecamatan_id' => null])));
                let timer;

                document.querySelectorAll('[data-open="daerah"]').forEach((b) => b.addEventListener('click', () => { dialog.showModal(); input.focus(); }));
                dialog.addEventListener('click', (e) => { if (e.target === dialog || e.target.closest('[data-close]')) dialog.close(); });

                const baris = (teks) => { hasil.innerHTML = `<li class="py-3 text-slate-500">${teks}</li>`; };

                input.addEventListener('input', () => {
                    clearTimeout(timer);
                    const q = input.value.trim();
                    if (q.length < 2) { hasil.innerHTML = ''; return; }
                    timer = setTimeout(async () => {
                        baris('Mencari…');
                        try {
                            const res = await fetch(@json(route('wilayah.cari')) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                            if (!res.ok) throw new Error(res.status);
                            // Satu baris per kecamatan, walau hasilnya beberapa desa di kecamatan yang sama.
                            const unik = new Map();
                            (await res.json()).forEach((w) => unik.has(w.kecamatan.id) || unik.set(w.kecamatan.id, w));
                            if (!unik.size) { baris('Daerah tidak ditemukan.'); return; }
                            hasil.innerHTML = '';
                            unik.forEach((w) => {
                                const url = new URL(tujuan);
                                url.searchParams.set('kecamatan_id', w.kecamatan.id);
                                const li = document.createElement('li');
                                const a = Object.assign(document.createElement('a'), { href: url, className: 'block py-3' });
                                a.append(Object.assign(document.createElement('b'), { textContent: 'Kec. ' + w.kecamatan.nama }));
                                a.append(Object.assign(document.createElement('span'), { className: 'block text-xs text-slate-500', textContent: w.kabupaten.nama + ', ' + w.provinsi.nama }));
                                li.append(a);
                                hasil.append(li);
                            });
                        } catch {
                            baris('Gagal memuat daerah. Coba lagi.');
                        }
                    }, 250);
                });
            })();
        </script>
    @endpush
@endcomponent
