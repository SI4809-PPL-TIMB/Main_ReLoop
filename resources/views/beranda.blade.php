<x-app-layout nav="home">
    <x-slot name="title">Reloop · Beranda</x-slot>

    @php
        $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
        $poinHari = [20, 20, 20, 40, 20, 20, 40];
        $selesai = $streak % 7;
        $ikonLokasi = '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>';
        $cari = '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>';
    @endphp

    {{-- Hero --}}
    <header class="relative h-[232px] overflow-hidden bg-slate-100">
        <img data-mode="jual" src="{{ asset('assets/illust/hero-jual.png') }}" alt="Warga bertukar barang preloved di lingkungan Reloop" class="absolute inset-0 h-full w-full object-cover">
        <img data-mode="daur" hidden src="{{ asset('assets/illust/hero-daur.png') }}" alt="Warga menyerahkan sampah terpilah kepada petugas daur ulang di lingkungan Reloop" class="absolute inset-0 h-full w-full object-cover">

        <div class="relative flex items-center justify-between gap-2 px-4 pt-5">
            <a href="{{ route('lokasi', ['from' => 'beranda']) }}" class="flex min-w-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-semibold">
                <svg class="size-4 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>
                <span class="truncate">{{ $lokasi ?? 'Atur lokasi' }}</span>
                <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </a>
            <div class="flex shrink-0 items-center gap-2">
                <a href="#" aria-label="Notifikasi" class="relative grid size-10 place-items-center rounded-full border border-slate-200 bg-white">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                </a>
                {{-- Slider mode Belanja <-> Daur Ulang --}}
                <button id="mode-toggle" type="button" role="switch" aria-checked="false" class="relative flex h-10 shrink-0 rounded-full border border-slate-200 bg-white p-[3px] text-xs font-semibold">
                    <span id="mode-knob" class="absolute left-[3px] top-[3px] h-8 rounded-full bg-ink transition-[transform,width] duration-300"></span>
                    <span data-ic="jual" class="relative flex items-center gap-1 px-3 transition-colors">
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1 12H6z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>Belanja</span>
                    <span data-ic="daur" class="relative flex items-center gap-1 px-3 transition-colors">
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.3L20 9"/><path d="M20 4v5h-5"/><path d="M20 12a8 8 0 0 1-14 5.3L4 15"/><path d="M4 20v-5h5"/></svg>Daur Ulang</span>
                </button>
            </div>
        </div>
    </header>

    <main class="relative -mt-7 space-y-4 px-4">
        {{-- Pencarian --}}
        <a href="#" data-mode="jual" class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">{!! $cari !!}</svg>
            <span class="text-sm text-slate-500">Cari kursi, buku, sepeda…</span>
        </a>
        <a href="#" data-mode="daur" hidden class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 shadow-sm">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">{!! $cari !!}</svg>
            <span class="text-sm text-slate-500">Cari pengepul atau jenis sampah…</span>
        </a>

        {{-- Poin + aksi cepat. Poin hanya untuk level, tidak bisa ditukar uang (BR-05/BR-06). --}}
        <section class="flex items-start justify-between rounded-2xl border border-slate-200 bg-gradient-to-r from-loop-soft via-white to-white p-4">
            <a href="#" class="flex flex-col justify-between self-stretch">
                <img src="{{ asset('assets/reloop-wordmark-dark.svg') }}" alt="Reloop" class="h-3 w-fit">
                <span class="mt-5 flex items-baseline gap-1">
                    <span class="text-[26px] font-semibold leading-none tracking-tight">{{ number_format($poin, 0, ',', '.') }}</span>
                    <span class="ml-1 text-xs font-medium text-slate-500">poin · Lv {{ $level }}</span>
                </span>
            </a>
            @php($aksi = fn ($ikon, $label) => '<span class="grid size-10 place-items-center rounded-full border border-slate-200 bg-white shadow-sm"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$ikon.'</svg></span>'.$label)
            <div data-mode="jual" class="flex gap-4 text-center text-xs font-medium">
                <a href="#" class="flex flex-col items-center gap-1">{!! $aksi('<path d="M12 5v14M5 12h14"/>', 'Jual') !!}</a>
                <a href="#" class="flex flex-col items-center gap-1">{!! $aksi('<path d="M5 8h14l-1 12H6z"/><path d="M9 8a3 3 0 0 1 6 0"/>', 'Pesanan') !!}</a>
            </div>
            <div data-mode="daur" hidden class="flex gap-4 text-center text-xs font-medium">
                <a href="#" class="flex flex-col items-center gap-1">{!! $aksi('<path d="M12 19V5M6 11l6-6 6 6"/>', 'Setor') !!}</a>
                <a href="#" class="flex flex-col items-center gap-1">{!! $aksi('<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>', 'Riwayat') !!}</a>
            </div>
        </section>

        {{-- Kategori barang bekas --}}
        <section aria-label="Kategori barang bekas" data-mode="jual" class="grid grid-cols-4 gap-2.5 text-center text-[13px] font-medium">
            @foreach ($kategori as $k)
                <a href="#" class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200 pb-2.5 pt-3">
                    <img src="{{ asset('assets/illust/categories/'.$k->slug.'.png') }}" class="size-12 object-contain" width="48" height="48" alt="">{{ $k->name }}
                </a>
            @endforeach
            <a href="#" class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200 pb-2.5 pt-3">
                <span class="grid size-12 grid-cols-2 gap-1 p-1.5" aria-hidden="true"><i class="rounded-md bg-loop"></i><i class="rounded-md bg-teal-600"></i><i class="rounded-md bg-teal-600"></i><i class="rotate-45 scale-75 rounded-sm bg-loop"></i></span>Lainnya
            </a>
        </section>

        {{-- Promo --}}
        <section data-mode="jual" class="relative overflow-hidden rounded-2xl border border-slate-200 bg-[#f7f8f5] p-4">
            <p class="max-w-[62%] text-2xl font-bold leading-tight text-orange-500">Mulai jualan</p>
            <p class="mt-0.5 max-w-[62%] text-sm font-medium">Dana aman sampai barang diterima pembeli</p>
            <a href="#" class="mt-3 inline-block rounded-full bg-ink px-4 py-2 text-xs font-bold uppercase tracking-wide text-white">Pasang sekarang</a>
            <img src="{{ asset('assets/illust/promo-jual.png') }}" class="absolute right-4 top-1/2 size-28 -translate-y-1/2 object-contain" width="112" height="112" alt="">
        </section>

        {{-- Streak (mode daur ulang) --}}
        <section data-mode="daur" hidden class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <a href="#" class="flex items-center gap-2 text-base font-bold">Kamu sudah setor {{ $streak }} hari</a>
            <ul class="mt-3 grid grid-cols-7 gap-1.5">
                @foreach ($poinHari as $i => $p)
                    @if ($i < $selesai)
                        <li class="flex flex-col items-center gap-1 rounded-xl bg-loop-soft pb-1.5 text-center opacity-60"><span class="block pb-0.5 pt-1 text-[10px] font-medium text-slate-500">Hari {{ $i + 1 }}</span><span class="mt-1.5 grid size-5 place-items-center rounded-full bg-brand-600"><svg class="size-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg></span><span class="text-xs font-bold text-brand-600">{{ $p }}</span></li>
                    @elseif ($i === $selesai)
                        <li class="flex flex-col overflow-hidden rounded-lg bg-brand-600 text-center"><span class="block pb-0.5 pt-1 text-[10px] font-bold text-white">Hari ini</span><span class="flex flex-1 flex-col items-center gap-1 rounded-lg border-2 border-brand-600 bg-white pb-1.5 pt-2"><img src="{{ asset('assets/illust/'.($p > 20 ? 'coin-stack-poin' : 'coin-poin').'.png') }}" class="{{ $p > 20 ? 'h-5 w-7' : 'size-5' }} shrink-0 object-contain" alt="" title="{{ $p }} poin"><span class="text-xs font-bold text-brand-600">{{ $p }}</span></span></li>
                    @else
                        <li class="flex flex-col items-center gap-1 rounded-xl bg-slate-50 pb-1.5 text-center"><span class="block pb-0.5 pt-1 text-[10px] font-medium text-slate-500">Hari {{ $i + 1 }}</span><img src="{{ asset('assets/illust/'.($p > 20 ? 'coin-stack-poin' : 'coin-poin').'.png') }}" class="mt-1.5 {{ $p > 20 ? 'h-5 w-7' : 'size-5' }} shrink-0 object-contain" alt="" title="{{ $p }} poin"><span class="text-xs font-bold text-brand-600">{{ $p }}</span></li>
                    @endif
                @endforeach
            </ul>
            <a href="#" class="mt-4 block rounded-full bg-ink py-3 text-center font-semibold text-white">Setor sekarang</a>
        </section>

        {{-- Barang di sekitarmu --}}
        <section data-mode="jual" class="pt-2">
            <h2 class="text-lg font-bold">Barang di sekitarmu</h2>
            <p class="text-sm text-slate-500">Dari tetangga dalam radius 5 km</p>
            <div class="no-scrollbar -mx-4 mt-3 flex gap-3 overflow-x-auto px-4 pb-1">
                @foreach ($barang as $b)
                    <a href="#" class="w-40 shrink-0">
                        <div class="ph relative h-36 rounded-xl"><span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold ring-1 {{ $b['kondisi'][1] }}">{{ $b['kondisi'][0] }}</span></div>
                        @if ($b['rating'] > 0)
                            <p class="mt-3"><span class="inline-block rounded-md bg-brand-600 px-2 py-1 text-[11px] font-bold leading-none text-white">★ {{ number_format($b['rating'], 1) }}</span></p>
                        @endif
                        <p class="mt-2 truncate text-[15px] font-semibold leading-snug">{{ $b['judul'] }}</p>
                        @if ($b['lokasi'])
                            <p class="mt-1.5 flex items-center gap-1 text-xs text-slate-500"><svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $ikonLokasi !!}</svg>{{ $b['lokasi'] }}</p>
                        @endif
                        <p class="mt-2 font-semibold">{{ $rupiah($b['harga']) }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Pengepul terdekat --}}
        <section data-mode="daur" hidden class="pt-2">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-lg font-bold">Pengepul terdekat</h2>
                <a href="#" class="shrink-0 text-sm font-semibold text-loop-dark">Lihat semua</a>
            </div>
            <p class="text-sm text-slate-500">Terverifikasi{{ $lokasi ? ', melayani '.$lokasi : '' }}</p>
            <ul class="mt-3 space-y-3">
                @foreach ($pengepul as $p)
                    <li class="rounded-2xl border border-slate-200">
                        <a href="#" class="flex gap-3 p-4">
                            <img src="{{ asset('assets/illust/collectors/'.$p['gambar'].'.png') }}" class="size-12 shrink-0 rounded-lg object-contain" width="48" height="48" alt="">
                            <div class="min-w-0 flex-1 space-y-1">
                                <p class="truncate font-semibold">{{ $p['nama'] }}</p>
                                <p class="flex items-center gap-1.5 text-xs text-slate-600"><svg class="size-3 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>Terverifikasi</p>
                                <p class="flex items-center gap-1.5 text-xs text-slate-500"><svg class="size-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-7.2 7-12a7 7 0 0 0-14 0c0 4.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>{{ $p['jarak'] }}</p>
                            </div>
                        </a>
                        <div class="relative border-t border-dashed border-slate-300" aria-hidden="true">
                            <span class="absolute -top-2 left-[-1px] h-4 w-[9px] overflow-hidden"><span class="absolute -left-2 top-0 size-4 rounded-full border border-slate-200 bg-white"></span></span>
                            <span class="absolute -top-2 right-[-1px] h-4 w-[9px] overflow-hidden"><span class="absolute -right-2 top-0 size-4 rounded-full border border-slate-200 bg-white"></span></span>
                        </div>
                        <div class="flex items-center justify-between p-4">
                            <p class="font-semibold">{{ $rupiah($p['harga']) }}<span class="text-xs font-normal text-slate-500">/kg</span></p>
                            <a href="#" class="rounded-full bg-ink px-5 py-2 text-sm font-semibold text-white">Setor</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </main>

    @push('scripts')
        <script>
            // Slider mode Belanja / Daur Ulang: tukar isi [data-mode] dan posisi kenop.
            const toggle = document.getElementById('mode-toggle');
            const knob = document.getElementById('mode-knob');
            let mode = 'jual';
            function setMode(m) {
                mode = m;
                document.querySelectorAll('[data-mode]').forEach((el) => (el.hidden = el.dataset.mode !== m));
                const seg = toggle.querySelector(`[data-ic=${m}]`);
                knob.style.width = seg.offsetWidth + 'px';
                knob.style.transform = `translateX(${seg.offsetLeft - 3}px)`;
                toggle.setAttribute('aria-checked', m === 'daur');
                toggle.setAttribute('aria-label', m === 'jual' ? 'Mode Belanja. Ketuk untuk pindah ke Daur Ulang' : 'Mode Daur Ulang. Ketuk untuk pindah ke Belanja');
                toggle.querySelectorAll('[data-ic]').forEach((ic) => {
                    ic.classList.toggle('text-white', ic.dataset.ic === m);
                    ic.classList.toggle('text-slate-500', ic.dataset.ic !== m);
                });
                try { localStorage.setItem('reloop-mode', m); } catch {}
            }
            toggle.addEventListener('click', () => setMode(mode === 'jual' ? 'daur' : 'jual'));
            let tersimpan = new URLSearchParams(location.search).get('mode');
            try { tersimpan ||= localStorage.getItem('reloop-mode'); } catch {}
            setMode(tersimpan === 'daur' ? 'daur' : 'jual');
            document.fonts?.ready.then(() => setMode(mode)); // ukur ulang setelah Figtree termuat
        </script>
    @endpush
</x-app-layout>
