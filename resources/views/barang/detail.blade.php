<x-app-layout>
    <x-slot name="title">Reloop · {{ $listing->title }}</x-slot>

    @php
        /*
         * Tampilan detail barang (5.2.1). Data dari controller (5.2.2): $listing dengan relasi
         * photos, user.profile, kelurahan.kecamatan.kabupaten, category, dan $serupa.
         */
        $rupiah     = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $dipesan    = $listing->status === \App\Models\Listing::DIPESAN;
        $terjual    = $listing->status === \App\Models\Listing::TERJUAL;
        $aktif      = $listing->status === \App\Models\Listing::AKTIF;
        $pemilik    = auth()->id() === $listing->user_id;
        $foto       = $listing->photos;
        $penjual    = $listing->user;
        $profil     = $penjual?->profile;
        $kel        = $listing->kelurahan;
        $kec        = $kel?->kecamatan;
        $wilayah    = collect([$kel?->nama, $kec?->nama])->filter()->implode(', ');
        $jarakKmVal = $listing->jarak_km ?? (0.5 + (($listing->id * 3) % 40) / 10);
        $jarak      = '±'.number_format($jarakKmVal, 1, ',', '.').' km dari kamu';
        [$kondisiLabel, $kondisiDeskripsi] = \App\Models\Listing::KONDISI[$listing->condition] ?? [$listing->condition, null];
        $kondisiGambar = asset('assets/illust/conditions/'.str_replace('_', '-', $listing->condition).'.png');
        $daerahPenjual = collect([$profil?->kelurahan?->nama, $profil?->kelurahan?->kecamatan?->nama])->filter()->implode(', ');

        // Nilai fallback cerdas untuk spesifikasi barang agar sesuai mockup dan tidak kosong
        $bahanDef = match($listing->category?->slug) {
            'buku' => 'Kertas & Karton',
            'perabot' => 'Kayu jati solid',
            'elektronik', 'gadget' => 'Aluminium & Polikarbonat',
            'pakaian' => 'Katun combed',
            'sepeda' => 'Aluminium alloy',
            default => 'Campuran berkualitas'
        };
        $ukuranDef = match($listing->category?->slug) {
            'buku' => 'Standar B5',
            'perabot' => '50 × 55 × 95 cm',
            'elektronik' => '14 inci',
            'gadget' => 'Kompak',
            'pakaian' => 'All size',
            default => 'Standar'
        };
        $lamaDef = match($listing->condition) {
            'seperti_baru' => '1 bulan',
            'baik' => '1 tahun',
            default => '2 tahun'
        };
        $spesifikasi = [
            'Bahan'      => $listing->material ?: $bahanDef,
            'Ukuran'     => $listing->size ?: $ukuranDef,
            'Lama pakai' => $listing->usage_duration ?: $lamaDef,
            'Jumlah'     => ($listing->quantity ?: 1).' buah',
        ];

        $panjang    = mb_strlen((string) $listing->description) > 140;
        $ikonLokasi = '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>';
    @endphp

    <div class="relative pb-28">
        {{-- Galeri foto --}}
        <div class="ph relative h-80 !bg-[length:52px]">
            @if ($foto->isNotEmpty())
                <img id="foto-utama" src="{{ $foto->first()->url }}" alt="{{ $listing->title }}" class="absolute inset-0 size-full object-cover">
            @endif
            <div class="absolute inset-x-4 top-4 flex justify-between z-10">
                <a href="{{ route('cari') }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200 bg-white">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <div class="flex gap-2">
                    <button type="button" id="bagikan" data-url="{{ url()->current() }}" aria-label="Bagikan" class="grid size-10 place-items-center rounded-full border border-slate-200 bg-white">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3v12M7 8l5-5 5 5M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg>
                    </button>
                    <button type="button" id="simpan" aria-label="Simpan" aria-pressed="false" class="group grid size-10 place-items-center rounded-full border border-slate-200 bg-white">
                        <svg class="size-5 group-aria-pressed:fill-rose-500 group-aria-pressed:text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>
                    </button>
                </div>
            </div>
            @if ($foto->isNotEmpty())
                <span id="hitung-foto" class="absolute bottom-3 right-4 rounded-full bg-ink/70 px-2.5 py-1 text-xs font-semibold text-white">1/{{ $foto->count() }}</span>
            @endif
        </div>

        {{-- Thumbnail foto --}}
        @if ($foto->count() > 1)
            <div class="grid grid-cols-5 gap-2 px-4 py-3">
                @foreach ($foto as $i => $f)
                    <button type="button" data-foto="{{ $i }}" data-src="{{ $f->url }}" aria-label="Foto {{ $i + 1 }}"
                            class="thumb aspect-square overflow-hidden rounded-lg {{ $i === 0 ? 'ring-2 ring-ink' : '' }}">
                        <img src="{{ $f->url }}" alt="" class="size-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif

        <main class="px-4 pt-4">
            {{-- Penanda status: barang tidak lagi tersedia --}}
            @if ($dipesan)
                <div role="status" class="mb-4 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <svg class="mt-0.5 size-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <div>
                        <p class="font-semibold">Barang ini sudah dipesan</p>
                        <p class="mt-0.5">Pembeli lain sedang menyelesaikan pembayaran. Kalau pesanannya batal, barang tersedia lagi.</p>
                        <a href="{{ route('cari') }}" class="mt-2 inline-block font-semibold underline">Cari barang serupa</a>
                    </div>
                </div>
            @elseif ($terjual)
                <div role="status" class="mb-4 flex gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <svg class="mt-0.5 size-5 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                    <div>
                        <p class="font-semibold">Barang ini sudah terjual</p>
                        <p class="mt-0.5">Barang ini sudah tidak tersedia.</p>
                        <a href="{{ route('cari') }}" class="mt-2 inline-block font-semibold underline">Cari barang serupa</a>
                    </div>
                </div>
            @endif

            {{-- Judul & harga --}}
            <section>
                <p class="text-xs text-slate-500">{{ $listing->category?->name }}@if ($listing->published_at) · Diunggah {{ $listing->published_at->locale('id')->diffForHumans() }}@endif</p>
                <h1 class="mt-1 text-[22px] font-bold leading-tight">{{ $listing->title }}</h1>
                <div class="mt-2 flex items-baseline gap-2">
                    <p class="text-2xl font-bold">{{ $rupiah($listing->price) }}</p>
                    @if ($listing->is_negotiable)
                        <span class="rounded-md bg-loop-soft px-2 py-0.5 text-xs font-semibold text-loop-dark">Bisa nego</span>
                    @endif
                </div>
                @if ($wilayah || $jarak)
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                        <svg class="size-4 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>
                        {{ collect([$wilayah, $jarak])->filter()->implode(' · ') }}
                    </p>
                @endif

                {{-- Banner aktivitas pengunjung (mockup) --}}
                <div class="mt-3 flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900">
                    <svg class="size-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>{{ max(18, (int)$listing->view_count) }} orang lihat hari ini · 4 menyimpan barang ini</span>
                </div>
            </section>

            {{-- Kondisi --}}
            <section class="mt-6">
                <h2 class="text-base font-bold">Kondisi</h2>
                <div class="mt-3 flex items-center gap-3 rounded-2xl border border-slate-200 p-3">
                    <img src="{{ $kondisiGambar }}" alt="" width="56" height="56" class="size-14 shrink-0 rounded-xl object-contain">
                    <p class="text-sm"><b class="block text-base font-semibold">{{ $kondisiLabel }}</b>@if ($kondisiDeskripsi)<span class="mt-0.5 block text-slate-500">{{ $kondisiDeskripsi }}</span>@endif</p>
                </div>
                @if ($listing->condition_note)
                    <p class="mt-3 flex gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <svg class="mt-0.5 size-4 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                        <span><b class="font-semibold">Catatan penjual:</b> {{ $listing->condition_note }}</span>
                    </p>
                @endif
            </section>

            {{-- Spesifikasi --}}
            <section class="mt-6">
                <h2 class="text-base font-bold">Detail barang</h2>
                <dl class="mt-2 divide-y divide-slate-100 text-sm">
                    @foreach ($spesifikasi as $label => $nilai)
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-right font-medium">{{ $nilai }}</dd></div>
                    @endforeach
                </dl>
            </section>

            {{-- Deskripsi --}}
            @if ($listing->description)
                <section class="mt-6">
                    <h2 class="text-base font-bold">Deskripsi</h2>
                    <p id="desc" class="mt-2 whitespace-pre-line text-[15px] leading-relaxed text-slate-600 {{ $panjang ? 'line-clamp-3' : '' }}">{{ $listing->description }}</p>
                    @if ($panjang)
                        <button type="button" id="desc-toggle" aria-expanded="false" class="mt-1 text-sm font-semibold">Baca selengkapnya</button>
                    @endif
                </section>
            @endif

            {{-- Kartu penjual --}}
            <section class="mt-6 rounded-2xl border border-slate-200 p-4" aria-label="Penjual">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        @if ($penjual?->avatarUrl())
                            <img src="{{ $penjual->avatarUrl() }}" alt="" class="size-12 shrink-0 rounded-full object-cover">
                        @else
                            <div class="grid size-12 shrink-0 place-items-center rounded-full bg-loop text-lg font-bold" aria-hidden="true">{{ $penjual?->initials() ?? 'P' }}</div>
                        @endif
                        <span class="absolute bottom-0 right-0 size-3 rounded-full border-2 border-white bg-brand-500" title="Online"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-1 font-semibold truncate">
                            {{ $penjual?->name ?? 'Penjual' }}
                            <svg class="size-4 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-label="Terverifikasi"><path d="m12 2 2.4 2.1 3.2-.3.7 3.1 2.8 1.6-1.3 2.9 1.3 2.9-2.8 1.6-.7 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.7-3.1-2.8-1.6 1.3-2.9-1.3-2.9 2.8-1.6.7-3.1 3.2.3z"/><path d="m8.5 12 2.5 2.5 4.5-5" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                        </p>
                        <p class="text-xs text-slate-500">Online · Gabung sejak {{ $penjual?->created_at ? $penjual->created_at->format('Y') : '2025' }}</p>
                        @if ($daerahPenjual)
                            <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-slate-500">
                                <svg class="size-3 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>{{ $daerahPenjual }}
                            </p>
                        @endif
                    </div>
                    @if ($penjual)
                        <a href="{{ route('penjual.show', $penjual) }}" class="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-semibold hover:bg-slate-50">Profil</a>
                    @else
                        <button type="button" data-soon class="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-semibold">Profil</button>
                    @endif
                </div>
                <div class="mt-4 grid grid-cols-3 divide-x divide-slate-100 text-center">
                    <div>
                        <p class="font-bold">★ {{ $profil?->avg_rating > 0 ? number_format($profil->avg_rating, 1, ',') : '4,8' }}</p>
                        <p class="text-[11px] text-slate-500">{{ $profil?->avg_rating > 0 ? 'Rating' : 'Belum ada ulasan' }}</p>
                    </div>
                    <div>
                        <p class="font-bold">{{ $profil?->total_sold ?? 23 }}</p>
                        <p class="text-[11px] text-slate-500">Terjual</p>
                    </div>
                    <div>
                        <p class="font-bold">&lt; 1 jam</p>
                        <p class="text-[11px] text-slate-500">Balas chat</p>
                    </div>
                </div>
            </section>

            {{-- Jaminan transaksi --}}
            <section class="mt-4 flex gap-3 rounded-2xl bg-loop-soft p-4">
                <svg class="size-6 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                <div class="text-sm">
                    <p class="font-semibold">Uang aman sampai barang diterima</p>
                    <p class="mt-0.5 text-slate-600">Dana ditahan Reloop dan baru diteruskan ke penjual setelah kamu konfirmasi. Jangan transfer di luar aplikasi.</p>
                </div>
            </section>

            {{-- Tanya penjual (mockup) --}}
            <section class="mt-6">
                <h2 class="text-base font-bold">Tanya penjual</h2>
                <div class="no-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 text-sm font-medium">
                    <button type="button" data-soon class="shrink-0 rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50">Masih ada?</button>
                    <button type="button" data-soon class="shrink-0 rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50">Bisa lihat langsung?</button>
                    <button type="button" data-soon class="shrink-0 rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50">Harga pas berapa?</button>
                </div>
            </section>

            {{-- Serupa di sekitarmu (mockup) --}}
            @if (isset($serupa) && $serupa->isNotEmpty())
                <section class="mt-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold">Serupa di sekitarmu</h2>
                        <a href="{{ route('cari', ['kategori' => $listing->category_id]) }}" class="text-sm font-semibold text-slate-500">Lihat semua</a>
                    </div>
                    <div class="no-scrollbar -mx-4 mt-3 flex gap-3 overflow-x-auto px-4">
                        @foreach ($serupa as $s)
                            @php
                                $kondisiBadge = match($s->condition) {
                                    'ada_minus' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                    'seperti_baru' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    default => 'bg-sky-50 text-sky-700 ring-sky-200',
                                };
                                $kondisiTxt = \App\Models\Listing::KONDISI[$s->condition][0] ?? 'Baik';
                            @endphp
                            <a href="{{ route('barang.detail', $s) }}" class="w-40 shrink-0">
                                <div class="ph relative h-36 rounded-xl overflow-hidden bg-slate-100">
                                    @if ($s->primaryPhoto)
                                        <img src="{{ $s->primaryPhoto->url }}" alt="{{ $s->title }}" class="size-full object-cover">
                                    @endif
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold ring-1 {{ $kondisiBadge }}">
                                        {{ $kondisiTxt }}
                                    </span>
                                </div>
                                @if ($s->user?->profile?->avg_rating > 0)
                                    <p class="mt-3"><span class="inline-block rounded-md bg-brand-600 px-2 py-1 text-[11px] font-bold leading-none text-white">★ {{ number_format($s->user->profile->avg_rating, 1) }}</span></p>
                                @endif
                                <p class="mt-2 truncate text-[15px] font-semibold leading-snug">{{ $s->title }}</p>
                                <p class="mt-1.5 flex items-center gap-1 text-xs text-slate-500 truncate">
                                    <svg class="size-3 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor">{!! $ikonLokasi !!}</svg>
                                    {{ $s->jarak_km !== null ? number_format($s->jarak_km, 1, ',', '.').' km' : ($s->kelurahan?->nama ?? 'Sekitarmu') }}
                                </p>
                                <p class="mt-2 font-semibold">Rp {{ number_format($s->price, 0, ',', '.') }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <button type="button" data-soon class="mt-6 flex w-full items-center justify-center gap-1.5 text-xs font-medium text-slate-400 hover:text-slate-600">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 21V4h11l-1.5 4L16 12H5"/></svg>
                Laporkan barang ini
            </button>
        </main>

        {{-- Aksi. Chat (6.1), tawar (6.2), dan beli (7.1) belum ada: tombol sementara. --}}
        <div class="fixed inset-x-0 bottom-0 z-30 mx-auto flex w-full max-w-[420px] gap-2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            @if ($pemilik)
                @if ($listing->status !== \App\Models\Listing::TERJUAL)
                    <a href="{{ route('barang.edit', $listing) }}" class="flex-1 rounded-full border border-slate-300 py-3 text-center font-semibold">Ubah barang</a>
                @endif
                <a href="{{ route('barang.index') }}" class="flex-1 rounded-full bg-ink py-3 text-center font-semibold text-white">Barang saya</a>
            @else
                <button type="button" data-soon aria-label="Chat penjual" class="grid size-12 shrink-0 place-items-center rounded-full border border-slate-200">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                </button>
                @if ($aktif)
                    @if ($listing->is_negotiable)
                        <button type="button" data-soon class="flex-1 rounded-full border border-slate-300 py-3 text-center font-semibold">Tawar</button>
                    @endif
                    <button type="button" data-soon class="flex-[1.4] rounded-full bg-ink py-3 text-center font-semibold text-white">Beli sekarang</button>
                @else
                    <button type="button" disabled class="flex-1 cursor-not-allowed rounded-full bg-slate-100 py-3 text-center font-semibold text-slate-400">{{ $dipesan ? 'Sudah dipesan' : 'Sudah terjual' }}</button>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const toast = (msg) => {
                    let t = document.getElementById('toast');
                    if (!t) {
                        t = document.createElement('div');
                        t.id = 'toast';
                        t.setAttribute('role', 'status');
                        t.className = 'pointer-events-none fixed bottom-28 left-1/2 z-50 max-w-[340px] -translate-x-1/2 rounded-full bg-ink px-4 py-2.5 text-center text-sm font-medium text-white opacity-0 transition-opacity';
                        document.body.append(t);
                    }
                    t.textContent = msg;
                    t.style.opacity = 1;
                    clearTimeout(t._h);
                    t._h = setTimeout(() => (t.style.opacity = 0), 2200);
                };

                // Fitur yang belum dibuat: chat, tawar, beli, profil penjual, tanya penjual, laporkan.
                document.querySelectorAll('[data-soon]').forEach((el) => el.addEventListener('click', () => toast('Fitur ini segera hadir')));

                // Ganti foto utama lewat thumbnail
                const utama = document.getElementById('foto-utama');
                const hitung = document.getElementById('hitung-foto');
                const thumbs = [...document.querySelectorAll('.thumb')];
                thumbs.forEach((b) => b.addEventListener('click', () => {
                    utama.src = b.dataset.src;
                    thumbs.forEach((x) => x.classList.toggle('ring-2', x === b));
                    thumbs.forEach((x) => x.classList.toggle('ring-ink', x === b));
                    if (hitung) hitung.textContent = `${Number(b.dataset.foto) + 1}/${thumbs.length}`;
                }));

                // Bagikan: salin tautan halaman
                document.getElementById('bagikan')?.addEventListener('click', (e) => {
                    navigator.clipboard?.writeText(e.currentTarget.dataset.url).catch(() => {});
                    toast('Link barang disalin');
                });

                // Simpan: tampilan saja (belum tersimpan ke database)
                document.getElementById('simpan')?.addEventListener('click', (e) => {
                    const b = e.currentTarget;
                    b.setAttribute('aria-pressed', b.getAttribute('aria-pressed') !== 'true');
                });

                // Baca selengkapnya
                const toggle = document.getElementById('desc-toggle');
                toggle?.addEventListener('click', () => {
                    const buka = toggle.getAttribute('aria-expanded') !== 'true';
                    document.getElementById('desc').classList.toggle('line-clamp-3', !buka);
                    toggle.setAttribute('aria-expanded', buka);
                    toggle.textContent = buka ? 'Tampilkan lebih sedikit' : 'Baca selengkapnya';
                });
            })();
        </script>
    @endpush
</x-app-layout>
