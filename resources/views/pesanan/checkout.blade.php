<x-app-layout :body-class="'bg-[#e9ebe8]'" :shell-class="'relative mx-auto min-h-screen max-w-[420px] overflow-x-clip bg-white pb-40'">
    <x-slot name="title">Reloop · Checkout</x-slot>

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $fotoUrl = $listing->primaryPhoto?->url ?? asset('assets/illust/activities/kursi-jati.png');

        // Alamat bawaan dari profil pengguna atau default prototype
        $userJalan = $user->profile?->patokan ?: 'Jl. Pandu Raya No. 12';
        $userKec = $user->profile?->kelurahan?->kecamatan?->nama ?: 'Ciomas';
        $userDesa = $user->profile?->kelurahan?->nama ?: 'Sukamaju';
        $userKota = $user->profile?->kelurahan?->kecamatan?->kabupaten?->nama ?: 'Kab. Bogor';

        $alamatJalan = $userJalan;
        $alamatDaerah = "{$userDesa}, {$userKec}, {$userKota}";
        $alamatLengkap = "{$alamatJalan}, {$alamatDaerah}";
    @endphp

    <!-- Header checkout -->
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

    <form id="form-checkout" action="{{ route('order.store', $listing) }}" method="POST" novalidate class="space-y-5 px-4 pt-4">
        @csrf
        @if ($acceptedOffer ?? null)
            <input type="hidden" name="offer_id" value="{{ $acceptedOffer->id }}">
        @endif
        <input type="hidden" name="delivery_address" id="delivery_address_input" value="{{ old('delivery_address', $alamatLengkap) }}">

        <!-- Lokasi antar -->
        <section>
            <button type="button" data-open="alamat" aria-label="Lokasi antar, ubah" class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 py-3 pl-3 pr-4 text-left">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100"><svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg></span>
                <span class="min-w-0 flex-1"><span id="alamat-jalan" class="block truncate font-semibold">{{ $alamatJalan }}</span><span id="alamat-daerah" class="block truncate text-xs text-slate-500">{{ $alamatDaerah }}</span></span>
                <span class="shrink-0 text-sm font-semibold text-brand-600">Ubah</span>
            </button>
            @error('delivery_address')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <!-- Ringkasan barang -->
        <section class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3">
            <img src="{{ $fotoUrl }}" alt="{{ $listing->title }}" width="64" height="64" class="size-16 shrink-0 rounded-lg bg-[#f7f8f4] object-contain p-1">
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ $listing->title }}</p>
                <p class="text-xs text-slate-500">Penjual: {{ $listing->user?->name }} · {{ $listing->user?->profile?->kelurahan?->nama ?? 'Sukamaju' }}</p>
                <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <p data-total class="font-bold">{{ $rupiah($agreedPrice) }}</p>
                    @if ($isOffer)
                        <p data-harga="tawar" class="text-xs text-slate-400 line-through">{{ $rupiah($listing->price) }}</p>
                        <span data-harga="tawar" class="rounded-md bg-loop-soft px-2 py-0.5 text-[11px] font-semibold text-loop-dark">Harga tawaran disepakati</span>
                    @endif
                </div>
            </div>
        </section>

        <!-- Cara serah terima -->
        <fieldset>
            <legend class="text-sm font-semibold">Cara serah terima <span class="font-normal text-slate-400">(wajib)</span></legend>
            <div class="mt-2 flex flex-col gap-2">
                <label class="block cursor-pointer">
                    <input type="radio" name="delivery_method" value="cod" class="peer sr-only" {{ old('delivery_method', 'cod') === 'cod' ? 'checked' : '' }} required>
                    <span class="flex items-center gap-3 rounded-2xl border border-slate-200 py-3 pl-3 pr-5 peer-checked:border-loop-dark/40 peer-checked:bg-loop-soft peer-checked:[&>i]:border-loop-dark peer-checked:[&>i]:bg-loop-dark peer-checked:[&>i]:shadow-[inset_0_0_0_3px_#f3fbe3] peer-[:user-invalid]:border-red-300">
                        <img src="{{ asset('assets/illust/checkout/ketemuan-cod.png') }}" alt="Ilustrasi ketemuan dan serah terima barang" width="48" height="48" class="size-12 shrink-0 object-contain p-1.5">
                        <span class="min-w-0"><b class="block">Ketemuan (COD lokasi)</b><span class="block text-xs text-slate-500">Atur titik temu lewat chat</span></span>
                        <i class="ml-auto size-5 shrink-0 rounded-full border-2 border-slate-300"></i>
                    </span>
                </label>
                <label class="block cursor-pointer">
                    <input type="radio" name="delivery_method" value="ambil" class="peer sr-only" {{ old('delivery_method') === 'ambil' ? 'checked' : '' }}>
                    <span class="flex items-center gap-3 rounded-2xl border border-slate-200 py-3 pl-3 pr-5 peer-checked:border-loop-dark/40 peer-checked:bg-loop-soft peer-checked:[&>i]:border-loop-dark peer-checked:[&>i]:bg-loop-dark peer-checked:[&>i]:shadow-[inset_0_0_0_3px_#f3fbe3] peer-[:user-invalid]:border-red-300">
                        <img src="{{ asset('assets/illust/checkout/ambil-penjual.png') }}" alt="Ilustrasi ambil barang di tempat penjual" width="48" height="48" class="size-12 shrink-0 object-contain">
                        <span class="min-w-0"><b class="block">Ambil di tempat penjual</b><span class="block text-xs text-slate-500">Alamat dibagikan setelah dibayar</span></span>
                        <i class="ml-auto size-5 shrink-0 rounded-full border-2 border-slate-300"></i>
                    </span>
                </label>
            </div>
            @error('delivery_method')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>

        <!-- Rincian biaya -->
        <section class="rounded-2xl border border-slate-200 p-5 text-sm">
            <h2 class="text-base font-bold">Rincian biaya</h2>
            <dl class="mt-4 space-y-3">
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-slate-500">Harga barang</dt>
                    <dd class="font-medium" data-total>{{ $rupiah($agreedPrice) }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                    <dt class="text-slate-500">Biaya layanan</dt>
                    <dd class="font-medium">Rp 0</dd>
                </div>
            </dl>
            <div class="mt-4 flex items-baseline justify-between gap-4 border-t border-dashed border-slate-200 pt-4">
                <span class="font-semibold">Total bayar</span>
                <span class="text-xl font-bold" data-total>{{ $rupiah($agreedPrice) }}</span>
            </div>
        </section>

        <!-- Escrow -->
        <section class="flex gap-3 rounded-2xl bg-loop-soft p-4">
            <svg class="size-6 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            <p class="text-sm text-slate-600"><b class="font-semibold text-ink">Dana ditahan Reloop</b> sampai kamu konfirmasi barang diterima.</p>
        </section>

        <!-- Aksi (Bottom Bar) -->
        <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
            <div class="mb-2 flex items-end justify-between">
                <div>
                    <p class="text-xs text-slate-500">Total bayar</p>
                    <p data-total class="text-xl font-bold">{{ $rupiah($agreedPrice) }}</p>
                </div>
            </div>
            <button type="submit" id="btn-bayar-sekarang" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white flex items-center justify-center gap-2 hover:bg-slate-800 transition disabled:opacity-50">
                <span id="btn-text">Bayar sekarang</span>
            </button>
        </div>
    </form>

    <!-- Dialog Bottom Sheet Lokasi Antar -->
    <dialog id="alamat" class="m-0 mt-auto w-full max-w-[420px] rounded-t-3xl p-0 sm:mx-auto">
        <div class="p-5">
            <div class="mx-auto h-1 w-9 rounded-full bg-slate-200"></div>
            <div class="mt-4 flex items-center justify-between">
                <h2 class="text-xl font-bold">Lokasi antar</h2>
                <button type="button" data-close aria-label="Tutup" class="grid size-9 place-items-center rounded-full bg-slate-100">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div id="alamat-sel" class="mt-4 grid gap-2.5">
                <label class="block rounded-2xl border border-slate-200 px-4 pb-2 pt-2.5 focus-within:border-ink">
                    <span class="block text-xs text-slate-500">Nama jalan dan nomor</span>
                    <input id="alamat-in" value="{{ $alamatJalan }}" placeholder="mis. Jl. Pandu Raya No. 12" class="w-full bg-transparent py-1 font-medium outline-none">
                </label>
                <label class="block rounded-2xl border border-slate-200 px-4 pb-2 pt-2.5 focus-within:border-ink">
                    <span class="block text-xs text-slate-500">Kecamatan</span>
                    <select id="alamat-kec" class="w-full appearance-none bg-transparent bg-[length:18px] bg-[right_center] bg-no-repeat py-1 pr-6 font-medium outline-none" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2394a3b8%27 stroke-width=%272%27 stroke-linecap=%27round%27%3E%3Cpath d=%27m6 9 6 6 6-6%27/%3E%3C/svg%3E')">
                        @if(isset($kecamatans) && $kecamatans->isNotEmpty())
                            @foreach ($kecamatans as $kec)
                                <option value="{{ $kec->nama }}" {{ $kec->nama === $userKec ? 'selected' : '' }}>{{ $kec->nama }}</option>
                            @endforeach
                        @else
                            <option value="{{ $userKec }}" selected>{{ $userKec }}</option>
                        @endif
                    </select>
                </label>
                <label class="block rounded-2xl border border-slate-200 px-4 pb-2 pt-2.5 focus-within:border-ink">
                    <span class="block text-xs text-slate-500">Desa/Kelurahan</span>
                    <select id="alamat-desa" class="w-full appearance-none bg-transparent bg-[length:18px] bg-[right_center] bg-no-repeat py-1 pr-6 font-medium outline-none" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2394a3b8%27 stroke-width=%272%27 stroke-linecap=%27round%27%3E%3Cpath d=%27m6 9 6 6 6-6%27/%3E%3C/svg%3E')">
                        @if(isset($kelurahans) && $kelurahans->isNotEmpty())
                            @foreach ($kelurahans as $kel)
                                <option value="{{ $kel->nama }}" {{ $kel->nama === $userDesa ? 'selected' : '' }}>{{ $kel->nama }}</option>
                            @endforeach
                        @else
                            <option value="{{ $userDesa }}" selected>{{ $userDesa }}</option>
                        @endif
                    </select>
                </label>
                <label class="block rounded-2xl border border-slate-200 px-4 pb-2 pt-2.5 focus-within:border-ink">
                    <span class="block text-xs text-slate-500">Kota/Kabupaten</span>
                    <select id="alamat-kota" class="w-full appearance-none bg-transparent bg-[length:18px] bg-[right_center] bg-no-repeat py-1 pr-6 font-medium outline-none" style="background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%2394a3b8%27 stroke-width=%272%27 stroke-linecap=%27round%27%3E%3Cpath d=%27m6 9 6 6 6-6%27/%3E%3C/svg%3E')">
                        @if(isset($kabupatens) && $kabupatens->isNotEmpty())
                            @foreach ($kabupatens as $kab)
                                <option value="{{ $kab->nama }}" {{ $kab->nama === $userKota ? 'selected' : '' }}>{{ $kab->nama }}</option>
                            @endforeach
                        @else
                            <option value="{{ $userKota }}" selected>{{ $userKota }}</option>
                        @endif
                    </select>
                </label>
            </div>
            <button type="button" id="btn-gps" data-toast="Lokasi terdeteksi: {{ $userDesa }}, {{ $userKec }}" class="mt-3 flex w-full items-center gap-3 border-t border-slate-100 pt-4 text-left">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100"><svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 11l18-8-8 18-2-8-8-2z"/></svg></span>
                <span><span class="block font-semibold">Gunakan lokasi saya</span><span class="block text-xs text-slate-500">Isi alamat otomatis dari GPS.</span></span>
            </button>
            <button type="button" id="alamat-simpan" class="mt-5 w-full rounded-full bg-ink py-3.5 font-semibold text-white">Simpan lokasi</button>
        </div>
    </dialog>

    @push('scripts')
        <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
        <script>
            // Toast notification helper
            function toast(msg) {
                let t = document.getElementById('toast');
                if (!t) {
                    t = document.createElement('div');
                    t.id = 'toast';
                    t.setAttribute('role', 'status');
                    t.className = 'pointer-events-none fixed bottom-28 left-1/2 z-50 max-w-[340px] -translate-x-1/2 rounded-full bg-ink px-4 py-2.5 text-center text-sm font-medium text-white opacity-0 transition-opacity';
                    document.body.append(t);
                }
                t.textContent = msg;
                t.style.opacity = '1';
                clearTimeout(t._h);
                t._h = setTimeout(() => { t.style.opacity = '0'; }, 2200);
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('form-checkout');
                const btn = document.getElementById('btn-bayar-sekarang');
                const btnText = document.getElementById('btn-text');

                // Modal dialog open & close
                document.querySelectorAll('[data-open]').forEach((tombol) => {
                    tombol.addEventListener('click', () => {
                        const target = document.getElementById(tombol.dataset.open);
                        if (target && typeof target.showModal === 'function') target.showModal();
                    });
                });

                document.querySelectorAll('dialog').forEach((dialog) => {
                    dialog.addEventListener('click', (e) => {
                        if (e.target === dialog || e.target.closest('[data-close]')) dialog.close();
                    });
                });

                // Lokasi antar: ringkasan di form diisi dari sheet dialog
                document.getElementById('alamat-simpan')?.addEventListener('click', () => {
                    const jalan = document.getElementById('alamat-in')?.value || 'Jl. Pandu Raya No. 12';
                    const kec = document.getElementById('alamat-kec')?.value || 'Ciomas';
                    const desa = document.getElementById('alamat-desa')?.value || 'Sukamaju';
                    const kota = document.getElementById('alamat-kota')?.value || 'Kab. Bogor';

                    const elJalan = document.getElementById('alamat-jalan');
                    const elDaerah = document.getElementById('alamat-daerah');
                    const hiddenInput = document.getElementById('delivery_address_input');

                    if (elJalan) elJalan.textContent = jalan;
                    if (elDaerah) elDaerah.textContent = `${desa}, ${kec}, ${kota}`;
                    if (hiddenInput) hiddenInput.value = `${jalan}, ${desa}, ${kec}, ${kota}`;
                    document.getElementById('alamat')?.close();
                });

                // GPS button helper
                document.getElementById('btn-gps')?.addEventListener('click', () => {
                    const kec = document.getElementById('alamat-kec')?.value || 'Ciomas';
                    const desa = document.getElementById('alamat-desa')?.value || 'Sukamaju';
                    toast(`Lokasi terdeteksi: ${desa}, ${kec}`);
                });

                // Submit Form Pesanan + Midtrans Snap
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    btn.disabled = true;
                    btnText.textContent = 'Memproses Pesanan...';

                    const formData = new FormData(form);
                    const payload = {
                        delivery_method: formData.get('delivery_method') || 'cod',
                        delivery_address: formData.get('delivery_address') || document.getElementById('delivery_address_input')?.value,
                        payment_method: 'snap',
                        offer_id: formData.get('offer_id') || null,
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
                            window.location.href = `/pesanan/${orderNumber}/status`;
                            return;
                        }

                        // 3. Tampilkan Midtrans Snap Popup jika script tersedia
                        if (typeof window.snap !== 'undefined' && typeof window.snap.pay === 'function') {
                            // Mulai auto-polling deteksi pembayaran saat Snap terbuka
                            const pollPayment = setInterval(async function () {
                                try {
                                    const checkRes = await fetch(`/pesanan/${orderNumber}/status`, {
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    });
                                    if (checkRes.ok) {
                                        const checkData = await checkRes.json();
                                        if (checkData.status === '{{ \App\Models\Order::STATUS_DIBAYAR }}' || checkData.is_paid) {
                                            clearInterval(pollPayment);
                                            window.location.href = `/pesanan/${orderNumber}/status?status=sukses`;
                                        }
                                    }
                                } catch (e) {
                                    // Ignore network transient error
                                }
                            }, 2000);

                            window.snap.pay(snapData.token, {
                                onSuccess: function (result) {
                                    clearInterval(pollPayment);
                                    window.location.href = `/pesanan/${orderNumber}/status?status=sukses`;
                                },
                                onPending: function (result) {
                                    // Tetap biarkan pollPayment berjalan jika user membayar di tab lain
                                },
                                onError: function (result) {
                                    clearInterval(pollPayment);
                                    window.location.href = `/pesanan/${orderNumber}/status?status=gagal`;
                                },
                                onClose: function () {
                                    clearInterval(pollPayment);
                                    window.location.href = `/pesanan/${orderNumber}/status`;
                                },
                            });
                        } else {
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