<x-app-layout>
    <x-slot name="title">Reloop · Checkout</x-slot>

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $fotoUrl = $listing->primaryPhoto?->url ?? asset('assets/illust/activities/kursi-jati.png');

        // Alamat bawaan dari profil pengguna
        $alamatProfil = trim(($user->profile?->patokan ?? '') . ' ' .
            ($user->profile?->kelurahan?->nama ?? ''));
        if (empty($alamatProfil)) {
            $alamatProfil = 'Jl. Pandu Raya No. 12, Sukamaju, Ciomas, Kab. Bogor';
        }
    @endphp

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white pb-40">
        <!-- Header -->
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
            <a href="{{ route('barang.detail', $listing) }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="text-xl font-bold">Checkout</h1>
        </header>

        @if (session('error'))
            <div class="mx-4 mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <form id="form-checkout" action="{{ route('order.store', $listing) }}" method="POST" class="space-y-5 px-4 pt-4">
            @csrf
            @if ($acceptedOffer ?? null)
                <input type="hidden" name="offer_id" value="{{ $acceptedOffer->id }}">
            @endif

            <!-- Lokasi Antar -->
            <section>
                <div class="rounded-2xl border border-slate-200 p-3.5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alamat Pengiriman / Pertemuan</span>
                        <span class="text-xs text-brand-600 font-semibold">Wajib</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 mt-0.5">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <textarea name="delivery_address" id="delivery_address" rows="2" required
                                class="w-full resize-none rounded-xl border border-slate-200 p-2.5 text-sm font-medium text-ink focus:border-ink focus:outline-none"
                                placeholder="Masukkan detail jalan, RT/RW, nomor rumah, atau patokan">{{ old('delivery_address', $alamatProfil) }}</textarea>
                            @error('delivery_address')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ringkasan Barang -->
            <section class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3">
                <img src="{{ $fotoUrl }}" alt="{{ $listing->title }}" width="64" height="64" class="size-16 shrink-0 rounded-lg bg-[#f7f8f4] object-contain p-1">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-ink">{{ $listing->title }}</p>
                    <p class="text-xs text-slate-500">Penjual: {{ $listing->user?->name }} · {{ $listing->user?->profile?->kelurahan?->nama ?? 'Wilayah terdekat' }}</p>
                    <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                        <p class="font-bold text-ink">{{ $rupiah($agreedPrice) }}</p>
                        @if ($isOffer)
                            <p class="text-xs text-slate-400 line-through">{{ $rupiah($listing->price) }}</p>
                            <span class="rounded-md bg-loop-soft px-2 py-0.5 text-[11px] font-semibold text-loop-dark">Harga tawaran disepakati</span>
                        @endif
                    </div>
                </div>
            </section>

            <!-- Cara Serah Terima -->
            <fieldset>
                <legend class="text-sm font-semibold">Cara serah terima <span class="font-normal text-slate-400">(wajib)</span></legend>
                <div class="mt-2 flex flex-col gap-2">
                    <label class="block cursor-pointer">
                        <input type="radio" name="delivery_method" value="cod" class="peer sr-only" {{ old('delivery_method', 'cod') === 'cod' ? 'checked' : '' }} required>
                        <span class="flex items-center gap-3 rounded-2xl border border-slate-200 py-3 pl-3 pr-5 peer-checked:border-loop-dark/40 peer-checked:bg-loop-soft peer-checked:[&>i]:border-loop-dark peer-checked:[&>i]:bg-loop-dark peer-checked:[&>i]:shadow-[inset_0_0_0_3px_#f3fbe3]">
                            <img src="{{ asset('assets/illust/checkout/ketemuan-cod.png') }}" alt="COD" width="48" height="48" class="size-12 shrink-0 object-contain p-1">
                            <span class="min-w-0 flex-1">
                                <b class="block text-ink text-sm">Ketemuan (COD lokasi)</b>
                                <span class="block text-xs text-slate-500">Atur titik temu lewat chat</span>
                            </span>
                            <i class="ml-auto size-5 shrink-0 rounded-full border-2 border-slate-300"></i>
                        </span>
                    </label>
                    <label class="block cursor-pointer">
                        <input type="radio" name="delivery_method" value="ambil" class="peer sr-only" {{ old('delivery_method') === 'ambil' ? 'checked' : '' }}>
                        <span class="flex items-center gap-3 rounded-2xl border border-slate-200 py-3 pl-3 pr-5 peer-checked:border-loop-dark/40 peer-checked:bg-loop-soft peer-checked:[&>i]:border-loop-dark peer-checked:[&>i]:bg-loop-dark peer-checked:[&>i]:shadow-[inset_0_0_0_3px_#f3fbe3]">
                            <img src="{{ asset('assets/illust/checkout/ambil-penjual.png') }}" alt="Ambil Sendiri" width="48" height="48" class="size-12 shrink-0 object-contain p-1">
                            <span class="min-w-0 flex-1">
                                <b class="block text-ink text-sm">Ambil di tempat penjual</b>
                                <span class="block text-xs text-slate-500">Alamat lengkap dibuka setelah dibayar</span>
                            </span>
                            <i class="ml-auto size-5 shrink-0 rounded-full border-2 border-slate-300"></i>
                        </span>
                    </label>
                </div>
                @error('delivery_method')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <!-- Metode Pembayaran -->
            <fieldset>
                <legend class="flex w-full items-center justify-between text-sm font-semibold">
                    <span>Metode pembayaran <span class="font-normal text-slate-400">(wajib)</span></span>
                    <span class="text-xs font-medium text-slate-400">Diproses Midtrans Snap</span>
                </legend>
                <div class="mt-2 space-y-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 has-[:checked]:border-loop-dark/40 has-[:checked]:bg-loop-soft">
                        <input type="radio" name="payment_method" value="qris" class="sr-only" {{ old('payment_method', 'qris') === 'qris' ? 'checked' : '' }}>
                        <img src="{{ asset('assets/illust/checkout/qris.svg') }}" alt="QRIS" class="size-9 shrink-0 object-contain">
                        <div class="min-w-0 flex-1">
                            <b class="block text-sm text-ink">QRIS (GoPay, ShopeePay, BCA, dll)</b>
                            <span class="block text-xs text-slate-500">Scan QR instan dari semua bank & e-wallet</span>
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 has-[:checked]:border-loop-dark/40 has-[:checked]:bg-loop-soft">
                        <input type="radio" name="payment_method" value="va" class="sr-only" {{ old('payment_method') === 'va' ? 'checked' : '' }}>
                        <img src="{{ asset('assets/illust/checkout/bank.svg') }}" alt="Virtual Account" class="size-9 shrink-0 object-contain">
                        <div class="min-w-0 flex-1">
                            <b class="block text-sm text-ink">Virtual Account (BCA, BNI, BRI, Mandiri)</b>
                            <span class="block text-xs text-slate-500">Transfer otomatis verifikasi 24 jam</span>
                        </div>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 has-[:checked]:border-loop-dark/40 has-[:checked]:bg-loop-soft">
                        <input type="radio" name="payment_method" value="ewallet" class="sr-only" {{ old('payment_method') === 'ewallet' ? 'checked' : '' }}>
                        <img src="{{ asset('assets/illust/checkout/wallet.svg') }}" alt="E-Wallet" class="size-9 shrink-0 object-contain">
                        <div class="min-w-0 flex-1">
                            <b class="block text-sm text-ink">E-Wallet Direct</b>
                            <span class="block text-xs text-slate-500">Langsung bayar lewat aplikasi favorit</span>
                        </div>
                    </label>
                </div>
                <p class="mt-2 text-xs text-slate-500">Batas waktu pembayaran 24 jam. Lewat dari itu pesanan dibatalkan otomatis.</p>
            </fieldset>

            <!-- Rincian Biaya -->
            <section class="rounded-2xl border border-slate-200 p-4 text-sm">
                <h2 class="font-bold text-ink">Rincian biaya</h2>
                <dl class="mt-3 space-y-2">
                    <div class="flex justify-between text-slate-600">
                        <dt>Harga barang</dt>
                        <dd class="font-medium text-ink">{{ $rupiah($agreedPrice) }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>Biaya layanan pembeli</dt>
                        <dd class="font-medium text-brand-600">Rp 0 (Gratis)</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-dashed border-slate-200 pt-3">
                        <span class="font-semibold text-ink">Total bayar</span>
                        <span class="text-xl font-bold text-ink">{{ $rupiah($agreedPrice) }}</span>
                    </div>
                </dl>
            </section>

            <!-- Edukasi Escrow / Rekber -->
            <section class="flex gap-3 rounded-2xl bg-loop-soft p-4">
                <svg class="size-6 shrink-0 text-loop-dark mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                <p class="text-xs text-slate-600 leading-relaxed">
                    <b class="font-semibold text-ink">Dana ditahan aman oleh Reloop</b>. Penjual baru akan menerima pembayaran setelah kamu memeriksa dan mengonfirmasi barang diterima.
                </p>
            </section>

            <!-- Fixed Bottom Bar -->
            <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <div class="mb-2 flex items-end justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total bayar</p>
                        <p class="text-xl font-bold text-ink">{{ $rupiah($agreedPrice) }}</p>
                    </div>
                </div>
                <button type="submit" id="btn-bayar-sekarang" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white flex items-center justify-center gap-2 transition hover:bg-slate-800 disabled:opacity-50">
                    <span id="btn-text">Bayar sekarang</span>
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('form-checkout');
                const btn = document.getElementById('btn-bayar-sekarang');
                const btnText = document.getElementById('btn-text');

                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    btn.disabled = true;
                    btnText.textContent = 'Memproses Pesanan...';

                    const formData = new FormData(form);
                    const payload = {
                        delivery_method: formData.get('delivery_method'),
                        delivery_address: formData.get('delivery_address'),
                        payment_method: formData.get('payment_method'),
                    };

                    try {
                        // 1. Buat order di backend
                        const orderRes = await fetch("{{ route('order.store', $listing) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify(payload),
                        });

                        const orderData = await orderRes.json();

                        if (!orderRes.ok) {
                            throw new Error(orderData.message || 'Gagal membuat pesanan. Silakan coba lagi.');
                        }

                        const orderNumber = orderData.order.order_number;

                        // 2. Request Midtrans Snap Token
                        btnText.textContent = 'Membuka Midtrans Snap...';
                        const snapRes = await fetch(`/pesanan/${orderNumber}/snap-token`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                        });

                        const snapData = await snapRes.json();

                        if (!snapRes.ok || !snapData.token) {
                            // Jika Midtrans API gagal, arahkan langsung ke halaman status pesanan
                            window.location.href = `/pesanan/${orderNumber}/status`;
                            return;
                        }

                        // 3. Tampilkan Midtrans Snap Popup jika script tersedia
                        if (typeof window.snap !== 'undefined' && typeof window.snap.pay === 'function') {
                            window.snap.pay(snapData.token, {
                                onSuccess: function (result) {
                                    window.location.href = `/pesanan/${orderNumber}/status?status=sukses`;
                                },
                                onPending: function (result) {
                                    window.location.href = `/pesanan/${orderNumber}/status?status=menunggu`;
                                },
                                onError: function (result) {
                                    window.location.href = `/pesanan/${orderNumber}/status?status=gagal`;
                                },
                                onClose: function () {
                                    window.location.href = `/pesanan/${orderNumber}/status`;
                                },
                            });
                        } else {
                            // Fallback jika Snap script belum ter-load (misal offline)
                            window.location.href = `/pesanan/${orderNumber}/status`;
                        }
                    } catch (err) {
                        alert(err.message || 'Terjadi kesalahan sistem.');
                        btn.disabled = false;
                        btnText.textContent = 'Bayar sekarang';
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
