@php
    $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $ikonLokasi = '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>';
@endphp
<x-app-layout :nav="'search'">
    <x-slot name="title">Reloop · {{ $keyword !== '' ? 'Cari: '.$keyword : 'Cari Barang' }}</x-slot>

    <!-- (c) Cari dan filter per daerah -->
    <header class="sticky top-0 z-20 bg-white px-4 pb-3 pt-4">
        <h1 class="sr-only">Pencarian Barang</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('beranda') }}" aria-label="Kembali" class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <!-- GET biasa: kirim ke cari?q=… -->
            <form role="search" action="{{ route('cari') }}" method="GET" class="flex flex-1 items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 focus-within:border-ink">
                <svg class="size-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="q" name="q" type="search" value="{{ $keyword }}" enterkeyhint="search" placeholder="Cari barang bekas" class="w-full bg-transparent text-sm outline-none" aria-label="Kata kunci">
                <span class="sr-only">Cari kursi, buku, sepeda…</span>
                <button type="submit" class="sr-only">Cari</button>

                {{-- Preservasi parameter filter --}}
                @if (! empty($kategori))<input type="hidden" name="kategori" value="{{ $kategori }}">@endif
                @if ($minHarga !== null)<input type="hidden" name="min_harga" value="{{ $minHarga }}">@endif
                @if ($maxHarga !== null)<input type="hidden" name="max_harga" value="{{ $maxHarga }}">@endif
                @if (! empty($provinsi))<input type="hidden" name="provinsi_id" value="{{ $provinsi }}">@endif
                @if (! empty($kabupaten))<input type="hidden" name="kabupaten_id" value="{{ $kabupaten }}">@endif
                @if (! empty($kecamatan))<input type="hidden" name="kecamatan_id" value="{{ $kecamatan }}">@endif
                @if (! empty($kelurahan))<input type="hidden" name="kelurahan_id" value="{{ $kelurahan }}">@endif
                @if ($urut && $urut !== 'dekat')<input type="hidden" name="urut" value="{{ $urut }}">@endif
                @if (! empty($kondisiFilter))
                    @foreach ($kondisiFilter as $kVal)
                        <input type="hidden" name="kondisi[]" value="{{ $kVal }}">
                    @endforeach
                @endif
            </form>
            <button type="button" data-open="filter" aria-label="Filter" class="relative grid size-10 shrink-0 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                @if ($jumlahFilterAktif > 0)
                    <span class="absolute -right-0.5 -top-0.5 grid size-4 place-items-center rounded-full bg-ink text-[10px] font-bold text-white">{{ $jumlahFilterAktif }}</span>
                @endif
            </button>
        </div>

        <!-- Wilayah berjenjang -->
        <button type="button" id="wilayah" data-open="lokasi" class="no-scrollbar mt-3 flex w-full items-center gap-1.5 overflow-x-auto whitespace-nowrap text-xs font-medium text-slate-500">
            <svg class="size-3.5 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor">{!! $ikonLokasi !!}</svg>
            {!! $breadcrumbHtml !!}
        </button>
    </header>

    <main>
        <div data-state="loading" data-skeleton="grid" class="mt-2" hidden></div>
        <div data-state="error" data-blank="Gagal memuat barang" data-desc="Periksa koneksi internetmu, lalu coba lagi." hidden></div>

        <div data-state="empty" @class(['px-8 py-12 text-center', 'hidden' => $barang->isNotEmpty()])>
            <div class="ph mx-auto size-28 rounded-3xl bg-slate-100"></div>
            <p class="mt-4 text-lg font-bold">Barang tidak ditemukan</p>
            <p class="mt-1 text-sm text-slate-500">Coba kata kunci lain, longgarkan filter, atau perluas daerah pencarian.</p>
            @if ($keyword !== '')
                <p class="mt-2 text-xs text-slate-400">Tidak ada hasil untuk kata kunci "{{ $keyword }}"</p>
                <a href="{{ route('cari', array_filter(['kategori' => $kategori, 'min_harga' => $minHarga, 'max_harga' => $maxHarga, 'provinsi_id' => $provinsi, 'kabupaten_id' => $kabupaten, 'kecamatan_id' => $kecamatan, 'kelurahan_id' => $kelurahan, 'urut' => $urut])) }}" class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:underline">Hapus kata kunci</a>
            @endif
            <div class="mt-5 flex flex-col items-center gap-2">
                <button type="button" data-open="filter" class="w-full max-w-xs rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">Ubah filter</button>
                <a href="{{ route('cari') }}" class="block py-2 text-sm font-semibold text-slate-500 hover:text-ink">
                    @if ($wilayahTerpilih && isset($wilayahTerpilih['induk']))
                        Perluas ke {{ $wilayahTerpilih['induk'] }}
                    @else
                        Reset pencarian
                    @endif
                </a>
                <a href="{{ route('cari') }}" class="text-xs text-slate-400 hover:underline">Reset Semua Filter</a>
                <a href="{{ route('beranda') }}" class="text-xs text-slate-400 hover:underline">Kembali ke Beranda</a>
            </div>
        </div>

        <div data-state="ok" @class(['px-4', 'hidden' => $barang->isEmpty()])>
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                <p><b>{{ $barang->total() }} barang</b> <span class="text-slate-500">di sekitar {{ $namaDaerah }}</span></p>
                <button type="button" data-open="urut" class="flex items-center gap-1 font-semibold">
                    <span>{{ ['dekat' => 'Terdekat', 'baru' => 'Terbaru', 'murah' => 'Termurah', 'mahal' => 'Termahal'][$urut] ?? 'Terdekat' }}</span>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </div>

            <div id="hasil" class="mt-4 grid grid-cols-2 gap-x-3 gap-y-5">
                @foreach ($barang as $b)
                    <a href="{{ route('barang.detail', $b) }}" data-km="{{ $b->jarak_km ?? '' }}" data-harga="{{ (int)$b->price }}">
                        <div class="ph relative h-36 rounded-xl overflow-hidden bg-slate-100">
                            @if ($b->primaryPhoto)
                                <img src="{{ $b->primaryPhoto->url }}" alt="{{ $b->title }}" class="size-full object-cover">
                            @endif
                            @if (isset($kondisi[$b->condition]))
                                <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold ring-1 {{ $kondisi[$b->condition][1] }}">
                                    {{ $kondisi[$b->condition][0] }}
                                </span>
                            @endif
                        </div>
                        @if ($b->user?->profile?->avg_rating > 0)
                            <p class="mt-3"><span class="inline-block rounded-md bg-brand-600 px-2 py-1 text-[11px] font-bold leading-none text-white">★ {{ number_format($b->user->profile->avg_rating, 1) }}</span></p>
                        @endif
                        <p class="mt-2 truncate text-[15px] font-semibold leading-snug">{{ $b->title }}</p>
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-slate-500">
                            <svg class="size-3 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor">{!! $ikonLokasi !!}</svg>
                            {{ $b->jarak_km !== null ? number_format($b->jarak_km, 1, ',', '.').' km' : '' }}{{ $b->jarak_km !== null && $b->kelurahan ? ' · ' : '' }}{{ $b->kelurahan?->nama }}
                        </p>
                        <p class="mt-2 font-semibold">{{ $rupiah($b->price) }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $barang->links() }}
            </div>
        </div>
    </main>

    <!-- Bottom sheet urutkan -->
    <dialog id="urut" class="m-0 mt-auto w-full max-w-[420px] rounded-t-3xl p-0 sm:mx-auto">
        <div class="p-5">
            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-slate-200"></div>
            <h2 class="text-lg font-bold">Urutkan</h2>
            <div id="urut-opsi" class="mt-3 flex flex-col gap-2 text-sm font-semibold">
                @php
                    $opsiUrut = [
                        'dekat' => 'Terdekat dari lokasimu',
                        'baru'  => 'Terbaru diunggah',
                        'murah' => 'Harga termurah',
                        'mahal' => 'Harga termahal',
                    ];
                @endphp
                @foreach ($opsiUrut as $uVal => $uLabel)
                    @php
                        $paramsUrut = array_filter([
                            'q'            => $keyword,
                            'kategori'     => $kategori,
                            'min_harga'    => $minHarga,
                            'max_harga'    => $maxHarga,
                            'provinsi_id'  => $provinsi,
                            'kabupaten_id' => $kabupaten,
                            'kecamatan_id' => $kecamatan,
                            'kelurahan_id' => $kelurahan,
                            'kondisi'      => $kondisiFilter,
                            'urut'         => $uVal,
                        ]);
                    @endphp
                    <a href="{{ route('cari', $paramsUrut) }}"
                       class="rounded-2xl border p-3.5 text-left {{ $urut === $uVal ? 'border-ink ring-1 ring-ink' : 'border-slate-200' }}">
                        {{ $uLabel }}
                    </a>
                @endforeach
            </div>
        </div>
    </dialog>

    <!-- Bottom sheet filter -->
    <dialog id="filter" class="m-0 mt-auto w-full max-w-[420px] rounded-t-3xl p-0 sm:mx-auto">
        <form method="GET" action="{{ route('cari') }}" class="p-5">
            @if ($keyword !== '')<input type="hidden" name="q" value="{{ $keyword }}">@endif
            @if (! empty($kategori))<input type="hidden" name="kategori" value="{{ $kategori }}">@endif
            @if (! empty($provinsi))<input type="hidden" name="provinsi_id" value="{{ $provinsi }}">@endif
            @if (! empty($kabupaten))<input type="hidden" name="kabupaten_id" value="{{ $kabupaten }}">@endif
            @if (! empty($kecamatan))<input type="hidden" name="kecamatan_id" value="{{ $kecamatan }}">@endif
            @if (! empty($kelurahan))<input type="hidden" name="kelurahan_id" value="{{ $kelurahan }}">@endif
            @if ($urut && $urut !== 'dekat')<input type="hidden" name="urut" value="{{ $urut }}">@endif

            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-slate-200"></div>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Filter</h2>
                <a href="{{ route('cari', array_filter(['q' => $keyword])) }}" class="text-sm font-semibold text-slate-400">Reset</a>
            </div>

            <p class="mt-4 text-sm font-semibold">Harga</p>
            <div class="mt-2 flex items-center gap-2 text-sm">
                <input name="min_harga" value="{{ $minHarga }}" class="w-full rounded-xl border border-slate-200 px-3 py-3 font-medium" placeholder="Min" inputmode="numeric" aria-label="Harga minimum">
                <span class="text-slate-400">–</span>
                <input name="max_harga" value="{{ $maxHarga }}" class="w-full rounded-xl border border-slate-200 px-3 py-3 font-medium" placeholder="Maks" inputmode="numeric" aria-label="Harga maksimum">
            </div>

            <p class="mt-4 text-sm font-semibold">Kondisi</p>
            <div class="mt-2 flex flex-wrap gap-2 text-sm font-medium">
                @foreach (['seperti_baru' => 'Seperti baru', 'baik' => 'Baik', 'ada_minus' => 'Ada minus'] as $kVal => $kLabel)
                    @php
                        $isCheck = in_array($kVal, $kondisiFilter, true);
                    @endphp
                    <label class="relative cursor-pointer">
                        <input type="checkbox" name="kondisi[]" value="{{ $kVal }}" class="peer sr-only" @checked($isCheck)>
                        <span class="block rounded-full bg-slate-100 px-4 py-2 peer-checked:bg-ink peer-checked:text-white">{{ $kLabel }}</span>
                    </label>
                @endforeach
            </div>

            <button type="submit" class="mt-6 w-full rounded-full bg-ink py-3.5 font-semibold text-white">
                Tampilkan {{ $barang->total() }} barang
            </button>
        </form>
    </dialog>

    <!-- Cari lokasi -->
    <dialog id="lokasi" class="m-0 h-full max-h-full w-full max-w-[420px] p-0 sm:mx-auto">
        <div class="flex h-full flex-col bg-white px-4 pt-4">
            <div class="flex items-center gap-3">
                <button type="button" data-close aria-label="Kembali" class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </button>
                <h2 class="text-base font-bold">Cari lokasi</h2>
            </div>
            <div class="mt-4 flex items-center gap-2 rounded-xl bg-slate-100 px-3">
                <svg class="size-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                <input id="lok-q" type="search" placeholder="Cari desa, kecamatan, atau kota" class="w-full bg-transparent py-3 text-sm font-medium outline-none" autocomplete="off">
            </div>
            <button type="button" id="lok-gps" class="mt-3 flex items-center gap-2 border-b border-slate-100 pb-3 text-sm font-semibold text-brand-600">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 11l18-8-8 18-2-8-8-2z"/></svg>
                Gunakan lokasi saya
            </button>
            <p class="mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Hasil pencarian</p>
            <div id="lok-list" class="no-scrollbar flex-1 overflow-y-auto">
                @foreach ($daftarKelurahanRekomendasi as $itemKel)
                    @php
                        $linkWilayah = route('cari', array_filter([
                            'q'            => $keyword,
                            'kategori'     => $kategori,
                            'min_harga'    => $minHarga,
                            'max_harga'    => $maxHarga,
                            'kelurahan_id' => $itemKel->id,
                            'urut'         => $urut,
                            'kondisi'      => $kondisiFilter,
                        ]));
                    @endphp
                    <a href="{{ $linkWilayah }}" data-p="{{ $itemKel->breadcrumb_pipe }}" class="flex w-full flex-col items-start border-b border-slate-100 py-3 text-left">
                        <span class="text-sm font-semibold">{{ $itemKel->nama }}</span>
                        <span class="text-xs text-slate-500">{{ $itemKel->kecamatan?->nama }}, {{ $itemKel->kecamatan?->kabupaten?->nama }}, {{ $itemKel->kecamatan?->kabupaten?->provinsi?->nama }}</span>
                    </a>
                @endforeach
                <p id="lok-kosong" hidden class="py-10 text-center text-sm text-slate-500">Lokasi tidak ditemukan. Coba nama desa atau kecamatan lain.</p>
            </div>
        </div>
    </dialog>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Dialog Open / Close
            document.querySelectorAll('[data-open]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-open');
                    const dlg = document.getElementById(id);
                    if (dlg) dlg.showModal();
                });
            });

            document.querySelectorAll('[data-close]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const dlg = btn.closest('dialog');
                    if (dlg) dlg.close();
                });
            });

            // Close dialog when clicking outside
            document.querySelectorAll('dialog').forEach(dlg => {
                dlg.addEventListener('click', (e) => {
                    const rect = dlg.getBoundingClientRect();
                    if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
                        dlg.close();
                    }
                });
            });

            // Live filter pencarian lokasi di sheet dialog
            const lokQ = document.getElementById('lok-q');
            const lokList = document.getElementById('lok-list');
            const lokKosong = document.getElementById('lok-kosong');

            if (lokQ && lokList) {
                lokQ.addEventListener('input', () => {
                    const q = lokQ.value.trim().toLowerCase();
                    const localItems = lokList.querySelectorAll('a[data-p]');

                    if (q.length === 0) {
                        localItems.forEach(el => el.hidden = false);
                        if (lokKosong) lokKosong.hidden = true;
                        return;
                    }

                    localItems.forEach(el => {
                        const txt = el.textContent.toLowerCase();
                        el.hidden = !txt.includes(q);
                    });

                    const anyVisible = Array.from(localItems).some(el => !el.hidden);
                    if (lokKosong) lokKosong.hidden = anyVisible;
                });
            }

            // GPS di sheet dialog lokasi
            const lokGps = document.getElementById('lok-gps');
            if (lokGps && navigator.geolocation) {
                lokGps.addEventListener('click', () => {
                    lokGps.textContent = 'Mencari lokasi GPS...';
                    navigator.geolocation.getCurrentPosition(async (pos) => {
                        try {
                            const res = await fetch('/lokasi/gps', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    latitude: pos.coords.latitude,
                                    longitude: pos.coords.longitude,
                                })
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (data.kelurahan && data.kelurahan.id) {
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('kelurahan_id', data.kelurahan.id);
                                    window.location.href = url.toString();
                                }
                            }
                        } catch (e) {
                            lokGps.textContent = 'Gunakan lokasi saya';
                        }
                    }, () => {
                        lokGps.textContent = 'Gunakan lokasi saya';
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
