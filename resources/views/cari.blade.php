@php
    $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $ikonLokasi = '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>';

    $adaFilterWilayah = ! empty($provinsi) || ! empty($kabupaten) || ! empty($kecamatan) || ! empty($kelurahan);
    $adaFilter = $keyword !== '' || ! empty($kategori) || $minHarga !== null || $maxHarga !== null || $adaFilterWilayah;

    $urlTanpa = function (array $hapus) use ($keyword, $kategori, $minHarga, $maxHarga, $provinsi, $kabupaten, $kecamatan, $kelurahan) {
        $params = [
            'q'            => $keyword,
            'kategori'     => $kategori,
            'min_harga'    => $minHarga,
            'max_harga'    => $maxHarga,
            'provinsi_id'  => $provinsi,
            'kabupaten_id' => $kabupaten,
            'kecamatan_id' => $kecamatan,
            'kelurahan_id' => $kelurahan,
        ];
        foreach ($hapus as $h) {
            unset($params[$h]);
            // Jika hapus wilayah, hapus seluruh level wilayah
            if ($h === 'wilayah') {
                unset($params['provinsi_id'], $params['kabupaten_id'], $params['kecamatan_id'], $params['kelurahan_id'], $params['provinsi'], $params['kabupaten'], $params['kecamatan'], $params['kelurahan'], $params['desa']);
            }
        }
        return route('cari', array_filter($params, fn ($v) => $v !== null && $v !== ''));
    };
@endphp
<x-app-layout :nav="'search'">
    <x-slot name="title">Reloop · {{ $keyword !== '' ? 'Cari: '.$keyword : 'Pencarian Barang' }}</x-slot>

    {{-- Top Search Bar --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div class="px-4 py-3">
            <form action="{{ route('cari') }}" method="GET" class="flex items-center gap-2">
                <a href="{{ route('beranda') }}" aria-label="Kembali ke Beranda"
                   class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200 text-ink transition-colors hover:bg-slate-50">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </a>
                <div class="relative flex-1">
                    <input type="text" name="q" value="{{ $keyword }}" placeholder="Cari kursi, buku, sepeda…" autofocus
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-9 text-sm text-ink placeholder-slate-400 focus:border-loop-dark focus:bg-white focus:outline-none focus:ring-1 focus:ring-loop-dark">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                    @if ($keyword !== '')
                        <a href="{{ $urlTanpa(['q']) }}" aria-label="Hapus kata kunci"
                           class="absolute right-3 top-1/2 grid size-5 -translate-y-1/2 place-items-center rounded-full text-slate-400 hover:text-slate-600">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>

                {{-- Sembunyikan parameter filter yang sedang aktif agar tidak hilang saat submit cari --}}
                @if (! empty($kategori))
                    <input type="hidden" name="kategori" value="{{ $kategori }}">
                @endif
                @if ($minHarga !== null)
                    <input type="hidden" name="min_harga" value="{{ $minHarga }}">
                @endif
                @if ($maxHarga !== null)
                    <input type="hidden" name="max_harga" value="{{ $maxHarga }}">
                @endif
                @if (! empty($provinsi))
                    <input type="hidden" name="provinsi_id" value="{{ $provinsi }}">
                @endif
                @if (! empty($kabupaten))
                    <input type="hidden" name="kabupaten_id" value="{{ $kabupaten }}">
                @endif
                @if (! empty($kecamatan))
                    <input type="hidden" name="kecamatan_id" value="{{ $kecamatan }}">
                @endif
                @if (! empty($kelurahan))
                    <input type="hidden" name="kelurahan_id" value="{{ $kelurahan }}">
                @endif

                <button type="submit" class="rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white transition-opacity hover:opacity-90">
                    Cari
                </button>
            </form>
        </div>

        {{-- Pills Kategori (dari 1.3) --}}
        <div class="no-scrollbar flex items-center gap-1.5 overflow-x-auto border-t border-slate-100 px-4 py-2 text-xs">
            @php($pilihSemua = empty($kategori))
            <a href="{{ $urlTanpa(['kategori']) }}"
               class="shrink-0 rounded-full px-3 py-1.5 font-semibold transition-colors {{ $pilihSemua ? 'bg-ink text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                Semua Kategori
            </a>
            @foreach ($daftarKategori as $kat)
                @php($aktif = ((string) $kategori === (string) $kat->id || (string) $kategori === (string) $kat->slug))
                <a href="{{ $aktif ? $urlTanpa(['kategori']) : route('cari', array_filter(['q' => $keyword, 'kategori' => $kat->slug, 'min_harga' => $minHarga, 'max_harga' => $maxHarga, 'provinsi_id' => $provinsi, 'kabupaten_id' => $kabupaten, 'kecamatan_id' => $kecamatan, 'kelurahan_id' => $kelurahan])) }}"
                   class="shrink-0 rounded-full px-3 py-1.5 font-medium transition-colors {{ $aktif ? 'bg-ink font-semibold text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $kat->name }}
                </a>
            @endforeach
        </div>
    </header>

    <main class="px-4 pb-8 pt-3">
        <div class="mb-3 space-y-2.5">
            {{-- Bagian Accordion Filter (Wilayah & Harga) --}}
            <div class="grid grid-cols-2 gap-2">
                {{-- Form Filter Wilayah (5.1.5) --}}
                <details class="group rounded-2xl border border-slate-200 bg-slate-50 p-3 text-xs col-span-2" @if ($adaFilterWilayah) open @endif>
                    <summary class="flex cursor-pointer items-center justify-between font-semibold text-ink">
                        <span class="flex items-center gap-1.5">
                            <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="currentColor">
                                {!! $ikonLokasi !!}
                            </svg>
                            Filter Wilayah (Provinsi – Desa)
                            @if ($adaFilterWilayah)
                                <span class="rounded-full bg-loop-dark/15 px-2 py-0.5 text-[10px] font-bold text-loop-dark">Aktif</span>
                            @endif
                        </span>
                        <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                    </summary>

                    <form action="{{ route('cari') }}" method="GET" class="mt-3 space-y-2.5 pt-2 border-t border-slate-200">
                        @if ($keyword !== '')
                            <input type="hidden" name="q" value="{{ $keyword }}">
                        @endif
                        @if (! empty($kategori))
                            <input type="hidden" name="kategori" value="{{ $kategori }}">
                        @endif
                        @if ($minHarga !== null)
                            <input type="hidden" name="min_harga" value="{{ $minHarga }}">
                        @endif
                        @if ($maxHarga !== null)
                            <input type="hidden" name="max_harga" value="{{ $maxHarga }}">
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label for="filter_provinsi" class="block font-medium text-slate-500 mb-1">Provinsi</label>
                                <select id="filter_provinsi" name="provinsi_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-loop-dark focus:outline-none">
                                    <option value="">-- Semua Provinsi --</option>
                                    @foreach ($daftarProvinsi as $prov)
                                        <option value="{{ $prov->id }}" @selected((string) $provinsi === (string) $prov->id)>{{ $prov->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="filter_kabupaten" class="block font-medium text-slate-500 mb-1">Kota / Kabupaten</label>
                                <select id="filter_kabupaten" name="kabupaten_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-loop-dark focus:outline-none">
                                    <option value="">-- Semua Kab / Kota --</option>
                                </select>
                            </div>

                            <div>
                                <label for="filter_kecamatan" class="block font-medium text-slate-500 mb-1">Kecamatan</label>
                                <select id="filter_kecamatan" name="kecamatan_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-loop-dark focus:outline-none">
                                    <option value="">-- Semua Kecamatan --</option>
                                </select>
                            </div>

                            <div>
                                <label for="filter_kelurahan" class="block font-medium text-slate-500 mb-1">Desa / Kelurahan</label>
                                <select id="filter_kelurahan" name="kelurahan_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-loop-dark focus:outline-none">
                                    <option value="">-- Semua Desa / Kelurahan --</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            @if ($adaFilterWilayah)
                                <a href="{{ $urlTanpa(['wilayah']) }}" class="text-[11px] font-semibold text-slate-500 hover:text-ink">
                                    Reset Wilayah
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button type="submit" class="rounded-xl bg-ink px-4 py-1.5 text-xs font-semibold text-white">
                                Terapkan Wilayah
                            </button>
                        </div>
                    </form>
                </details>

                {{-- Form Rentang Harga (5.1.4) --}}
                <details class="group rounded-2xl border border-slate-200 bg-slate-50 p-3 text-xs col-span-2" @if ($minHarga !== null || $maxHarga !== null) open @endif>
                    <summary class="flex cursor-pointer items-center justify-between font-semibold text-ink">
                        <span class="flex items-center gap-1.5">
                            <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>
                            </svg>
                            Filter Rentang Harga
                            @if ($minHarga !== null || $maxHarga !== null)
                                <span class="rounded-full bg-loop-dark/15 px-2 py-0.5 text-[10px] font-bold text-loop-dark">Aktif</span>
                            @endif
                        </span>
                        <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                    </summary>

                    <form action="{{ route('cari') }}" method="GET" class="mt-3 space-y-2.5 pt-2 border-t border-slate-200">
                        @if ($keyword !== '')
                            <input type="hidden" name="q" value="{{ $keyword }}">
                        @endif
                        @if (! empty($kategori))
                            <input type="hidden" name="kategori" value="{{ $kategori }}">
                        @endif
                        @if (! empty($provinsi))
                            <input type="hidden" name="provinsi_id" value="{{ $provinsi }}">
                        @endif
                        @if (! empty($kabupaten))
                            <input type="hidden" name="kabupaten_id" value="{{ $kabupaten }}">
                        @endif
                        @if (! empty($kecamatan))
                            <input type="hidden" name="kecamatan_id" value="{{ $kecamatan }}">
                        @endif
                        @if (! empty($kelurahan))
                            <input type="hidden" name="kelurahan_id" value="{{ $kelurahan }}">
                        @endif

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="min_harga" class="block font-medium text-slate-500 mb-1">Harga Minimum</label>
                                <input type="number" id="min_harga" name="min_harga" value="{{ $minHarga }}" placeholder="Rp 0" min="0" step="1000"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-loop-dark focus:outline-none">
                            </div>
                            <div>
                                <label for="max_harga" class="block font-medium text-slate-500 mb-1">Harga Maksimum</label>
                                <input type="number" id="max_harga" name="max_harga" value="{{ $maxHarga }}" placeholder="Rp Tak terhingga" min="0" step="1000"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus:border-loop-dark focus:outline-none">
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            @if ($minHarga !== null || $maxHarga !== null)
                                <a href="{{ $urlTanpa(['min_harga', 'max_harga']) }}" class="text-[11px] font-semibold text-slate-500 hover:text-ink">
                                    Reset Harga
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button type="submit" class="rounded-xl bg-ink px-4 py-1.5 text-xs font-semibold text-white">
                                Terapkan Harga
                            </button>
                        </div>
                    </form>
                </details>
            </div>

            {{-- Chip Filter Aktif --}}
            @if ($adaFilter)
                <div class="flex flex-wrap items-center gap-1.5 pt-1 text-xs">
                    <span class="text-slate-400 text-[11px]">Filter aktif:</span>

                    @if ($keyword !== '')
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-ink">
                            <span>"{{ $keyword }}"</span>
                            <a href="{{ $urlTanpa(['q']) }}" aria-label="Hapus kata kunci" class="text-slate-400 hover:text-ink">✕</a>
                        </span>
                    @endif

                    @if ($kategoriTerpilih)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-ink">
                            <span>Kategori: {{ $kategoriTerpilih->name }}</span>
                            <a href="{{ $urlTanpa(['kategori']) }}" aria-label="Hapus filter kategori" class="text-slate-400 hover:text-ink">✕</a>
                        </span>
                    @endif

                    @if ($wilayahTerpilih)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-ink" title="{{ $wilayahTerpilih['breadcrumb'] ?? '' }}">
                            <span>{{ $wilayahTerpilih['label'] }}</span>
                            <a href="{{ $urlTanpa(['wilayah']) }}" aria-label="Hapus filter wilayah" class="text-slate-400 hover:text-ink">✕</a>
                        </span>
                    @endif

                    @if ($minHarga !== null && $maxHarga !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-ink">
                            <span>{{ $rupiah($minHarga) }} – {{ $rupiah($maxHarga) }}</span>
                            <a href="{{ $urlTanpa(['min_harga', 'max_harga']) }}" aria-label="Hapus filter harga" class="text-slate-400 hover:text-ink">✕</a>
                        </span>
                    @elseif ($minHarga !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-ink">
                            <span>Min: {{ $rupiah($minHarga) }}</span>
                            <a href="{{ $urlTanpa(['min_harga']) }}" aria-label="Hapus filter harga minimum" class="text-slate-400 hover:text-ink">✕</a>
                        </span>
                    @elseif ($maxHarga !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-ink">
                            <span>Maks: {{ $rupiah($maxHarga) }}</span>
                            <a href="{{ $urlTanpa(['max_harga']) }}" aria-label="Hapus filter harga maksimum" class="text-slate-400 hover:text-ink">✕</a>
                        </span>
                    @endif

                    <a href="{{ route('cari') }}" class="ml-auto text-[11px] font-semibold text-red-600 hover:underline">
                        Hapus Semua
                    </a>
                </div>
            @endif

            <div class="flex items-baseline justify-between text-xs text-slate-500 pt-1">
                <p>Menampilkan {{ $barang->total() }} barang aktif</p>
                @if ($lokasi)
                    <p class="text-[11px]">Diurutkan dari <strong class="text-slate-700">{{ $lokasi }}</strong></p>
                @endif
            </div>
        </div>

        {{-- Grid Daftar Barang --}}
        @if ($barang->isNotEmpty())
            <div class="grid grid-cols-2 gap-3">
                @foreach ($barang as $b)
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm transition-shadow hover:shadow-md">
                        <div>
                            <div class="ph relative h-36 w-full overflow-hidden rounded-xl bg-slate-100">
                                @if ($b->primaryPhoto)
                                    <img src="{{ $b->primaryPhoto->url }}" alt="{{ $b->title }}" class="size-full object-cover">
                                @endif
                                @if (isset($kondisi[$b->condition]))
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $kondisi[$b->condition][1] }}">
                                        {{ $kondisi[$b->condition][0] }}
                                    </span>
                                @endif
                            </div>

                            @if ($b->user?->profile?->avg_rating > 0)
                                <p class="mt-2.5">
                                    <span class="inline-block rounded-md bg-brand-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
                                        ★ {{ number_format($b->user->profile->avg_rating, 1) }}
                                    </span>
                                </p>
                            @endif

                            <h2 class="mt-1.5 line-clamp-2 text-sm font-semibold leading-snug text-ink" title="{{ $b->title }}">
                                {{ $b->title }}
                            </h2>

                            @if ($b->category)
                                <p class="mt-0.5 text-[11px] font-medium text-slate-400">
                                    {{ $b->category->name }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-2 pt-2 border-t border-slate-100">
                            <p class="flex items-center gap-1 text-[11px] text-slate-500" title="{{ $b->kelurahan?->breadcrumb() ?? '' }}">
                                <svg class="size-3 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>
                                <span class="truncate">
                                    {{ $b->jarak_km !== null ? number_format($b->jarak_km, 1, ',', '.').' km' : ($b->kelurahan?->nama ?? 'Lokasi belum diatur') }}
                                </span>
                            </p>
                            <p class="mt-1 text-sm font-bold text-ink">
                                {{ $rupiah($b->price) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $barang->links() }}
            </div>
        @else
            {{-- Hasil kosong ditangani (Empty State) --}}
            <div class="py-16 text-center">
                <div class="mx-auto grid size-16 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8 11h6"/>
                    </svg>
                </div>

                <h2 class="mt-4 text-base font-bold text-ink">Barang tidak ditemukan</h2>
                <p class="mx-auto mt-1.5 max-w-[280px] text-xs leading-relaxed text-slate-500">
                    @if ($adaFilter)
                        Tidak ada barang aktif yang cocok dengan kombinasi kata kunci, kategori, wilayah, atau rentang harga yang dipilih.
                    @else
                        Belum ada barang aktif yang tersedia saat ini.
                    @endif
                </p>

                <div class="mt-6 flex flex-col items-center justify-center gap-2 sm:flex-row">
                    @if ($adaFilter)
                        <a href="{{ route('cari') }}"
                           class="w-full max-w-[200px] rounded-full border border-slate-300 py-2.5 text-xs font-semibold text-ink transition-colors hover:bg-slate-50">
                            Reset Semua Filter
                        </a>
                    @endif
                    <a href="{{ route('beranda') }}"
                       class="w-full max-w-[200px] rounded-full bg-ink py-2.5 text-xs font-semibold text-white transition-opacity hover:opacity-90">
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        @endif
    </main>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const provSelect = document.getElementById('filter_provinsi');
            const kabSelect = document.getElementById('filter_kabupaten');
            const kecSelect = document.getElementById('filter_kecamatan');
            const kelSelect = document.getElementById('filter_kelurahan');

            if (!provSelect) return;

            const selectedKab = @json($kabupaten);
            const selectedKec = @json($kecamatan);
            const selectedKel = @json($kelurahan);

            async function loadAnak(level, parentId, targetSelect, selectedVal) {
                targetSelect.innerHTML = `<option value="">-- Semua ${targetSelect.previousElementSibling.textContent} --</option>`;
                if (!parentId) return;
                try {
                    const res = await fetch(`/wilayah/${level}?parent=${parentId}`);
                    if (!res.ok) return;
                    const data = await res.json();
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.nama;
                        if (String(item.id) === String(selectedVal)) opt.selected = true;
                        targetSelect.appendChild(opt);
                    });
                } catch (e) {}
            }

            provSelect.addEventListener('change', async () => {
                await loadAnak('kabupaten', provSelect.value, kabSelect, null);
                kecSelect.innerHTML = '<option value="">-- Semua Kecamatan --</option>';
                kelSelect.innerHTML = '<option value="">-- Semua Desa / Kelurahan --</option>';
            });

            kabSelect.addEventListener('change', async () => {
                await loadAnak('kecamatan', kabSelect.value, kecSelect, null);
                kelSelect.innerHTML = '<option value="">-- Semua Desa / Kelurahan --</option>';
            });

            kecSelect.addEventListener('change', async () => {
                await loadAnak('kelurahan', kecSelect.value, kelSelect, null);
            });

            // Muat kaskade awal jika ada yang terpilih
            if (provSelect.value) {
                loadAnak('kabupaten', provSelect.value, kabSelect, selectedKab).then(() => {
                    if (kabSelect.value) {
                        loadAnak('kecamatan', kabSelect.value, kecSelect, selectedKec).then(() => {
                            if (kecSelect.value) {
                                loadAnak('kelurahan', kecSelect.value, kelSelect, selectedKel);
                            }
                        });
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
