<x-app-layout>
    <x-slot name="title">Reloop · Profil {{ $user->name }}</x-slot>

    @php
        $jarakStr = $jarak !== null ? '±'.number_format($jarak, 1, ',', '.').' km' : null;
        $lokasiDanJarak = collect([$daerah, $jarakStr])->filter()->implode(' · ');
        $ikonLokasi = '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>';
        $tahunBergabung = $user->created_at ? $user->created_at->format('Y') : '2025';
    @endphp

    <div class="relative mx-auto min-h-screen max-w-[420px] overflow-x-hidden bg-white pb-10">
        {{-- Header --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
            <a href="javascript:history.back()" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="text-xl font-bold">Profil Penjual</h1>
        </header>

        <main class="px-4 pt-5">
            {{-- Info Penjual --}}
            <section class="flex items-center gap-4">
                <div class="relative size-20 shrink-0">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="" class="size-20 rounded-full object-cover">
                    @else
                        <div class="grid size-20 place-items-center rounded-full bg-loop text-2xl font-bold" aria-hidden="true">{{ $user->initials() }}</div>
                    @endif
                    <span class="absolute bottom-0 right-0 size-4 rounded-full border-2 border-white bg-emerald-500" title="Online"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="flex items-center gap-1 text-lg font-bold truncate">
                        {{ $user->name }}
                        <svg class="size-5 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-label="Terverifikasi"><path d="m12 2 2.4 2.1 3.2-.3.7 3.1 2.8 1.6-1.3 2.9 1.3 2.9-2.8 1.6-.7 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.7-3.1-2.8-1.6 1.3-2.9-1.3-2.9 2.8-1.6.7-3.1 3.2.3z"/><path d="m8.5 12 2.5 2.5 4.5-5" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    </p>
                    @if ($lokasiDanJarak)
                        <p class="mt-1 flex items-center gap-1 text-sm text-slate-500 truncate">
                            <svg class="size-3.5 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>
                            {{ $lokasiDanJarak }}
                        </p>
                    @endif
                    <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-loop-soft px-2.5 py-1 text-xs font-semibold text-loop-dark">
                        Level {{ $profil?->level ?? 2 }} · Streak {{ $profil?->streak_count ?? 3 }} minggu
                    </span>
                </div>
                <button type="button" data-soon class="shrink-0 self-start rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold hover:bg-slate-50">Chat</button>
            </section>

            {{-- 3 Kolom Statistik --}}
            <section class="mt-5 grid grid-cols-3 divide-x divide-slate-100 rounded-2xl border border-slate-200 py-3 text-center">
                <div>
                    <p class="font-bold">★ {{ $profil?->avg_rating > 0 ? number_format($profil->avg_rating, 1, ',') : '4,8' }}</p>
                    <p class="text-[11px] text-slate-500">dari 126 ulasan</p>
                </div>
                <div>
                    <p class="font-bold">{{ $profil?->total_sold ?? 23 }}</p>
                    <p class="text-[11px] text-slate-500">Transaksi selesai</p>
                </div>
                <div>
                    <p class="font-bold">{{ $tahunBergabung }}</p>
                    <p class="text-[11px] text-slate-500">Bergabung</p>
                </div>
            </section>

            {{-- Tab navigasi --}}
            <div role="tablist" class="mt-5 grid grid-cols-2 border-b border-slate-200 text-center text-sm font-medium">
                <button type="button" id="tab-btn-barang" role="tab" aria-selected="true" class="border-b-2 border-ink py-3 font-semibold text-ink">
                    Barang ({{ $barang->count() }})
                </button>
                <button type="button" id="tab-btn-ulasan" role="tab" aria-selected="false" class="py-3 text-slate-500">
                    Ulasan (126)
                </button>
            </div>

            {{-- Konten Tab Barang --}}
            <div id="tab-panel-barang">
                @if ($barang->isEmpty())
                    <div class="py-12 text-center text-slate-500">
                        <p class="font-semibold text-slate-700">Belum ada barang aktif</p>
                        <p class="mt-1 text-xs">{{ $user->name }} belum memasang barang saat ini.</p>
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-x-3 gap-y-5">
                        @foreach ($barang as $b)
                            @php
                                $kondisiBadge = match($b->condition) {
                                    'ada_minus' => 'bg-amber-50 text-amber-800 ring-amber-200',
                                    'seperti_baru' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    default => 'bg-sky-50 text-sky-700 ring-sky-200',
                                };
                                $kondisiTxt = \App\Models\Listing::KONDISI[$b->condition][0] ?? 'Baik';
                            @endphp
                            <a href="{{ route('barang.detail', $b) }}">
                                <div class="ph relative h-36 rounded-xl overflow-hidden bg-slate-100">
                                    @if ($b->primaryPhoto)
                                        <img src="{{ $b->primaryPhoto->url }}" alt="{{ $b->title }}" class="size-full object-cover">
                                    @endif
                                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold ring-1 {{ $kondisiBadge }}">
                                        {{ $kondisiTxt }}
                                    </span>
                                </div>
                                <p class="mt-3"><span class="inline-block rounded-md bg-brand-600 px-2 py-1 text-[11px] font-bold leading-none text-white">★ {{ number_format($profil?->avg_rating > 0 ? $profil->avg_rating : 4.8, 1) }}</span></p>
                                <p class="mt-2 truncate text-[15px] font-semibold leading-snug">{{ $b->title }}</p>
                                <p class="mt-1.5 flex items-center gap-1 text-xs text-slate-500 truncate">
                                    <svg class="size-3 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor">{!! $ikonLokasi !!}</svg>
                                    {{ $b->kelurahan?->nama ?? 'Sekitar' }}
                                </p>
                                <p class="mt-2 font-semibold">Rp {{ number_format($b->price, 0, ',', '.') }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Konten Tab Ulasan --}}
            <div id="tab-panel-ulasan" class="hidden mt-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold">Ulasan terbaru</h2>
                    <p class="text-sm font-semibold text-slate-500">★ 4,8 · 126</p>
                </div>
                <ul class="mt-3 space-y-3">
                    <li class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold">D</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold">Dika</p>
                                <p class="text-xs text-slate-500">18 Okt 2026 · Buku Pemrograman</p>
                            </div>
                            <p class="text-sm text-orange-400" aria-label="5 dari 5">★★★★★</p>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">Barang sesuai foto, penjual ramah dan tepat waktu saat COD.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold">A</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold">Ayu Lestari</p>
                                <p class="text-xs text-slate-500">12 Okt 2026 · Laptop ASUS</p>
                            </div>
                            <p class="text-sm text-orange-400" aria-label="4 dari 5">★★★★<span class="text-slate-200">★</span></p>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">Masih berfungsi baik. Ada lecet kecil, tapi sudah diinfokan sebelumnya.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center gap-3">
                            <div class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold">B</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold">Bagus</p>
                                <p class="text-xs text-slate-500">3 Okt 2026 · Aksesoris</p>
                            </div>
                            <p class="text-sm text-orange-400" aria-label="5 dari 5">★★★★★</p>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">Fast respon, nego enak dan amanah.</p>
                    </li>
                </ul>
            </div>

            <button type="button" data-soon class="mt-8 flex w-full items-center justify-center gap-1.5 text-xs font-medium text-slate-400 hover:text-slate-600">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 21V4h11l-1.5 4L16 12H5"/></svg>
                Laporkan penjual
            </button>
        </main>
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
                        t.className = 'pointer-events-none fixed bottom-12 left-1/2 z-50 max-w-[340px] -translate-x-1/2 rounded-full bg-ink px-4 py-2.5 text-center text-sm font-medium text-white opacity-0 transition-opacity';
                        document.body.append(t);
                    }
                    t.textContent = msg;
                    t.style.opacity = 1;
                    clearTimeout(t._h);
                    t._h = setTimeout(() => (t.style.opacity = 0), 2200);
                };

                document.querySelectorAll('[data-soon]').forEach((el) => el.addEventListener('click', () => toast('Fitur ini segera hadir')));

                // Tab switching (Barang vs Ulasan)
                const btnBarang = document.getElementById('tab-btn-barang');
                const btnUlasan = document.getElementById('tab-btn-ulasan');
                const panelBarang = document.getElementById('tab-panel-barang');
                const panelUlasan = document.getElementById('tab-panel-ulasan');

                btnBarang?.addEventListener('click', () => {
                    btnBarang.className = 'border-b-2 border-ink py-3 font-semibold text-ink';
                    btnBarang.setAttribute('aria-selected', 'true');
                    btnUlasan.className = 'py-3 text-slate-500';
                    btnUlasan.setAttribute('aria-selected', 'false');
                    panelBarang.classList.remove('hidden');
                    panelUlasan.classList.add('hidden');
                });

                btnUlasan?.addEventListener('click', () => {
                    btnUlasan.className = 'border-b-2 border-ink py-3 font-semibold text-ink';
                    btnUlasan.setAttribute('aria-selected', 'true');
                    btnBarang.className = 'py-3 text-slate-500';
                    btnBarang.setAttribute('aria-selected', 'false');
                    panelUlasan.classList.remove('hidden');
                    panelBarang.classList.add('hidden');
                });
            })();
        </script>
    @endpush
</x-app-layout>

