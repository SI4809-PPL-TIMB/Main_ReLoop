@php
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $gambar = $mitra->gambar();
    $kel = $mitra->kelurahan;
    $rating = $mitra->rating();
    $defaultMetode = old('metode', $bisaJemput ? 'jemput' : 'antar');
    $defaultKategoriId = (int) old('category_id', $kategoriPilihan?->category_id);
@endphp

@component('layouts.app', ['title' => 'Reloop · Setor ke '.$mitra->nama_usaha])
    <x-top-bar title="Setor Sampah" :back="route('pengepul.show', $mitra)" />

    <!-- Info Singkat Mitra -->
    <section class="flex items-center gap-3 border-b border-slate-100 bg-[#fbfdfa] px-4 py-3.5">
        @if ($gambar)
            <img src="{{ asset('assets/illust/collectors/'.$gambar.'.png') }}" class="size-12 shrink-0 rounded-xl border border-slate-200 bg-white p-1 object-contain" width="48" height="48" alt="">
        @else
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-loop-soft text-loop-dark font-bold text-lg border border-slate-200">♻️</span>
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
                <h2 class="truncate text-sm font-bold">{{ $mitra->nama_usaha }}</h2>
                <span class="text-xs font-bold text-brand-700" title="Terverifikasi">✓</span>
            </div>
            <p class="truncate text-xs text-slate-500">Lapak: {{ $kel ? $kel->nama.', Kec. '.$kel->kecamatan->nama : 'Belum diatur' }}</p>
            <p class="text-xs font-semibold text-brand-700">
                @if ($rating) ★ {{ number_format($rating, 1) }} · @endif
                {{ $mitra->layananLabel() }}
            </p>
        </div>
    </section>

    @if ($errors->any())
        <div class="mx-4 mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-700">
            <p class="font-bold">Periksa kembali data setoran Anda:</p>
            <ul class="mt-1.5 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="form-setor" method="POST" action="{{ route('setor.store', $mitra) }}" class="space-y-5 px-4 pb-36 pt-4">
        @csrf

        <!-- 1. Pilihan Jenis Sampah (Harga Terkunci) -->
        <fieldset>
            <div class="flex items-baseline justify-between">
                <legend class="text-sm font-semibold">Jenis Sampah <span class="text-red-500">*</span></legend>
                <span class="text-xs text-slate-500">Harga per kg dikunci</span>
            </div>

            <div class="mt-2 grid grid-cols-2 gap-2.5" id="pilihan-jenis">
                @foreach ($daftarHarga as $h)
                    @php
                        $cat = $h->category;
                        $slug = $cat?->slug;
                        $hasImg = $slug && file_exists(public_path('assets/illust/categories/'.$slug.'.png'));
                        $isSelected = $h->category_id === $defaultKategoriId;
                    @endphp
                    <label class="relative block cursor-pointer">
                        <input type="radio" name="category_id" value="{{ $h->category_id }}"
                               data-harga="{{ $h->harga_per_kg }}"
                               data-label="{{ $cat?->name ?? 'Daur Ulang' }}"
                               {{ $isSelected ? 'checked' : '' }} required class="peer sr-only">
                        <span class="flex flex-col gap-1 rounded-2xl border border-slate-200 p-3 transition peer-checked:border-ink peer-checked:bg-slate-50 peer-checked:ring-1 peer-checked:ring-ink">
                            <span class="flex items-center justify-between">
                                <span class="text-sm font-bold truncate">{{ $cat?->name ?? 'Daur Ulang' }}</span>
                                @if ($hasImg)
                                    <img src="{{ asset('assets/illust/categories/'.$slug.'.png') }}" class="size-7 object-contain" alt="">
                                @else
                                    <span class="grid size-7 place-items-center rounded-lg bg-loop-soft text-xs">♻️</span>
                                @endif
                            </span>
                            <span class="text-xs text-slate-500">Sampah terpilah</span>
                            <span class="mt-1 text-sm font-bold text-brand-700">{{ $rupiah($h->harga_per_kg) }}<span class="text-xs font-normal text-slate-500">/kg</span></span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('category_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>

        <!-- 2. Perkiraan Berat -->
        <fieldset>
            <div class="flex items-baseline justify-between">
                <legend class="text-sm font-semibold">Perkiraan Berat <span class="text-red-500">*</span></legend>
                <span class="text-xs text-slate-500">Ditimbang ulang saat setor</span>
            </div>

            <div class="mt-2 flex items-center rounded-2xl border border-slate-200 px-4 py-3 focus-within:border-ink">
                <input id="berat" name="perkiraan_berat" type="number" step="0.1" min="0.1" max="5000"
                       value="{{ old('perkiraan_berat', 5) }}" required inputmode="decimal"
                       class="w-full text-xl font-bold outline-none" aria-label="Perkiraan berat dalam kilogram">
                <span class="font-semibold text-slate-500">kg</span>
            </div>

            <div id="berat-chips" class="mt-2 flex gap-2 text-xs font-semibold">
                @foreach ([2, 5, 10, 20] as $ch)
                    <button type="button" data-val="{{ $ch }}"
                            class="flex-1 rounded-xl border border-slate-200 py-2 hover:bg-slate-50 {{ (float) old('perkiraan_berat', 5) == $ch ? 'bg-slate-100 font-bold' : '' }}">
                        {{ $ch }} kg
                    </button>
                @endforeach
            </div>
            @error('perkiraan_berat')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>

        <!-- 3. Metode Penyerahan: Jemput atau Antar Sendiri -->
        <fieldset id="metode-fs">
            <legend class="text-sm font-semibold">Metode Penyerahan <span class="text-red-500">*</span></legend>

            <div class="mt-2 flex flex-col gap-2.5">
                <!-- Opsi Jemput -->
                @if ($bisaJemput)
                    <label id="opt-jemput" class="block cursor-pointer">
                        <input type="radio" name="metode" value="jemput" {{ $defaultMetode === 'jemput' ? 'checked' : '' }} required class="peer sr-only">
                        <span class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3.5 transition peer-checked:border-loop-dark/60 peer-checked:bg-loop-soft peer-checked:[&>i]:border-loop-dark peer-checked:[&>i]:bg-loop-dark peer-checked:[&>i]:shadow-[inset_0_0_0_3px_#f3fbe3]">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-lg">🚚</span>
                            <span class="min-w-0 flex-1">
                                <b class="block text-sm">Jemput ke Rumah</b>
                                <span class="mt-0.5 block text-xs text-slate-500">Pengepul datang ke alamatmu sesuai jadwal</span>
                                <span class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                    ✓ Alamatmu di area layanan ({{ $kelurahan?->kecamatan?->nama }})
                                </span>
                            </span>
                            <i class="size-5 shrink-0 rounded-full border-2 border-slate-300"></i>
                        </span>
                    </label>
                @else
                    <div class="opacity-75">
                        <div class="flex items-start gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3.5 cursor-not-allowed">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-200 text-lg opacity-70">🚚</span>
                            <div class="min-w-0 flex-1">
                                <b class="block text-sm text-slate-600">Jemput ke Rumah (Tidak Tersedia)</b>
                                @if (! $mitra->layanan_jemput)
                                    <span class="mt-0.5 block text-xs font-medium text-slate-500">Mitra ini tidak menyediakan layanan jemput.</span>
                                @elseif (! $kelurahan)
                                    <span class="mt-0.5 block text-xs font-medium text-amber-700">Alamatmu belum diatur di profil. Silakan atur lokasi terlebih dahulu.</span>
                                @else
                                    <span class="mt-0.5 block text-xs font-medium text-red-600">Alamatmu (Kec. {{ $kelurahan->kecamatan->nama }}) di luar area layanan jemput mitra ini.</span>
                                    <span class="mt-1 block text-[11px] text-slate-500">Area jemput mitra: Kec. {{ $mitra->area->pluck('nama')->implode(', ') ?: '-' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Opsi Antar Sendiri -->
                @if ($mitra->layanan_antar)
                    <label id="opt-antar" class="block cursor-pointer">
                        <input type="radio" name="metode" value="antar" {{ $defaultMetode === 'antar' ? 'checked' : '' }} required class="peer sr-only">
                        <span class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3.5 transition peer-checked:border-loop-dark/60 peer-checked:bg-loop-soft peer-checked:[&>i]:border-loop-dark peer-checked:[&>i]:bg-loop-dark peer-checked:[&>i]:shadow-[inset_0_0_0_3px_#f3fbe3]">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-lg">🏪</span>
                            <span class="min-w-0 flex-1">
                                <b class="block text-sm">Antar Sendiri ke Lapak</b>
                                <span class="mt-0.5 block text-xs text-slate-500">Bawa sampah langsung ke lapak pengepul</span>
                                <span class="mt-1 block text-[11px] text-slate-500">Lokasi: {{ $kel ? $kel->nama : 'Sesuai profil mitra' }}</span>
                            </span>
                            <i class="size-5 shrink-0 rounded-full border-2 border-slate-300"></i>
                        </span>
                    </label>
                @endif
            </div>
            @error('metode')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>

        <!-- 4. Alamat & Catatan Penjemputan -->
        <section id="alamat-section" class="space-y-3 rounded-2xl border border-slate-200 p-4 {{ $defaultMetode === 'antar' ? 'hidden' : '' }}">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">Alamat Penjemputan</h3>
                <a href="{{ route('lokasi') }}" class="text-xs font-semibold text-brand-600 hover:underline">Ubah Lokasi</a>
            </div>
            <p class="text-sm font-medium text-slate-800">
                {{ $kelurahan ? $kelurahan->breadcrumb() : 'Alamat belum diatur di profil Anda.' }}
            </p>
            <label class="block">
                <span class="text-xs font-medium text-slate-500">Patokan rumah / catatan untuk kurir (opsional)</span>
                <input name="patokan" value="{{ old('patokan', $patokan) }}" placeholder="Contoh: Pagar hitam depan pos satpam"
                       class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-ink">
            </label>
            <label class="block">
                <span class="text-xs font-medium text-slate-500">Catatan tambahan (opsional)</span>
                <input name="catatan" value="{{ old('catatan') }}" placeholder="Contoh: Tolong bawa timbangan besar"
                       class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-ink">
            </label>
        </section>

        <!-- 5. Banner Edukasi Bebas Komisi & Harga Terkunci -->
        <section class="flex gap-3 rounded-2xl bg-loop-soft p-4">
            <svg class="size-6 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2a8 8 0 0 1 14-5.3L20 9"/><path d="M20 4v5h-5"/><path d="M20 12a8 8 0 0 1-14 5.3L4 15"/><path d="M4 20v-5h5"/>
            </svg>
            <div class="space-y-1 text-xs text-slate-700">
                <p class="font-bold text-ink">Bebas komisi &amp; harga terkunci</p>
                <p>Harga per kg dikunci saat order dibuat. Kamu menerima pembayaran penuh sesuai berat hasil timbangan pengepul tanpa potongan biaya.</p>
            </div>
        </section>

        <!-- Fixed Bottom Action Bar -->
        <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            <div class="mb-2 flex items-baseline justify-between">
                <div>
                    <p class="text-xs text-slate-500">Estimasi Pendapatan</p>
                    <p id="total-estimasi" class="text-xl font-bold text-ink">Rp 0</p>
                </div>
                <div class="text-right text-xs text-slate-500">
                    <span id="detail-kalkulasi">-</span>
                </div>
            </div>
            <button type="submit" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white transition-transform active:scale-[0.99]">
                Kirim Order Setoran
            </button>
        </div>
    </form>
@endcomponent

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-setor');
    const inputBerat = document.getElementById('berat');
    const totalEstimasi = document.getElementById('total-estimasi');
    const detailKalkulasi = document.getElementById('detail-kalkulasi');
    const alamatSection = document.getElementById('alamat-section');

    function formatRupiah(num) {
        return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function hitungEstimasi() {
        const selectedRadio = form.querySelector('input[name="category_id"]:checked');
        const hargaPerKg = selectedRadio ? Number(selectedRadio.dataset.harga) : 0;
        const berat = Math.max(0, parseFloat(inputBerat.value) || 0);
        const total = Math.round(hargaPerKg * berat);

        if (hargaPerKg > 0 && berat > 0) {
            totalEstimasi.textContent = formatRupiah(total);
            detailKalkulasi.textContent = berat + ' kg × ' + formatRupiah(hargaPerKg);
        } else {
            totalEstimasi.textContent = 'Rp 0';
            detailKalkulasi.textContent = selectedRadio ? (berat + ' kg × ' + formatRupiah(hargaPerKg)) : '-';
        }
    }

    // Chips perkiraan berat (2kg, 5kg, 10kg, 20kg)
    const chips = document.querySelectorAll('#berat-chips button');
    chips.forEach(function (btn) {
        btn.addEventListener('click', function () {
            inputBerat.value = btn.dataset.val;
            chips.forEach(function (b) {
                b.classList.remove('bg-slate-100', 'ring-1', 'ring-ink', 'border-ink');
                b.classList.add('border-slate-200');
            });
            btn.classList.add('bg-slate-100', 'ring-1', 'ring-ink', 'border-ink');
            btn.classList.remove('border-slate-200');
            hitungEstimasi();
        });
    });

    // Highlight chip yang sesuai nilai awal
    chips.forEach(function (btn) {
        if (parseFloat(btn.dataset.val) === parseFloat(inputBerat.value)) {
            btn.classList.add('bg-slate-100', 'ring-1', 'ring-ink', 'border-ink');
            btn.classList.remove('border-slate-200');
        }
    });

    // Jenis sampah change
    form.querySelectorAll('input[name="category_id"]').forEach(function (radio) {
        radio.addEventListener('change', hitungEstimasi);
    });

    // Input berat manual
    inputBerat.addEventListener('input', function () {
        // Hapus highlight chip jika nilai diubah manual
        chips.forEach(function (b) {
            b.classList.remove('bg-slate-100', 'ring-1', 'ring-ink', 'border-ink');
            b.classList.add('border-slate-200');
            if (parseFloat(b.dataset.val) === parseFloat(inputBerat.value)) {
                b.classList.add('bg-slate-100', 'ring-1', 'ring-ink', 'border-ink');
                b.classList.remove('border-slate-200');
            }
        });
        hitungEstimasi();
    });

    // Metode jemput/antar toggle
    form.querySelectorAll('input[name="metode"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (alamatSection) {
                alamatSection.classList.toggle('hidden', radio.value !== 'jemput');
            }
        });
    });

    // Hitung saat halaman pertama kali load
    hitungEstimasi();
});
</script>
@endpush
