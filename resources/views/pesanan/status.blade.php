<x-app-layout :body-class="'bg-[#e9ebe8]'" :shell-class="'relative mx-auto min-h-screen max-w-[420px] overflow-x-clip bg-white pb-40'">
    <x-slot name="title">Reloop · Pembayaran #{{ $order->order_number }}</x-slot>

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $fotoUrl = $order->listing?->primaryPhoto?->url ?? asset('assets/illust/activities/kursi-jati.png');

        $isSukses = $order->status === \App\Models\Order::STATUS_DIBAYAR || request('status') === 'sukses';
        $isGagal = $order->status === \App\Models\Order::STATUS_DIBATALKAN || request('status') === 'gagal' || ($order->payment && in_array($order->payment->status, [\App\Models\Payment::STATUS_GAGAL, \App\Models\Payment::STATUS_KEDALUWARSA]));
        $isMenunggu = !$isSukses && !$isGagal;
    @endphp

    <!-- Header pembayaran sesuai ui/bayar.html -->
    <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
        <a href="{{ route('order.show', $order->order_number) }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold leading-tight">Pembayaran</h1>
            <p class="text-xs text-slate-500">#{{ $order->order_number }}</p>
        </div>
    </header>

    <main class="space-y-4 px-4 pt-5">
        {{-- 1. STATE SUKSES / BERHASIL (ui/bayar.html modal lunas & ringkasan) --}}
        @if ($isSukses)
            <section class="text-center pt-2">
                <div class="mx-auto grid size-20 place-items-center rounded-full bg-loop-soft text-loop-dark">
                    <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-2xl font-extrabold text-ink">Pembayaran Berhasil!</h2>
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-loop-soft px-3 py-1 text-xs font-semibold text-loop-dark">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/></svg>
                    Dana Ditahan Rekber Reloop
                </p>
                <p class="mx-auto mt-2 max-w-[320px] text-xs text-slate-500 leading-relaxed">
                    Pembayaran kamu telah kami terima. Dana aman di rekening bersama Reloop sampai kamu memeriksa dan mengonfirmasi barang.
                </p>
            </section>

            <!-- Ringkasan Transaksi Sukses -->
            <section class="rounded-2xl border border-slate-200 p-4 text-sm shadow-card">
                <h3 class="font-bold text-ink mb-3">Rincian Transaksi</h3>
                <dl class="space-y-2.5">
                    <div class="flex justify-between text-slate-600">
                        <dt>Nomor Pesanan</dt>
                        <dd class="font-mono font-medium text-ink">{{ $order->order_number }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>Barang</dt>
                        <dd class="font-medium text-ink truncate max-w-[180px]">{{ $order->listing?->title }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>Metode Bayar</dt>
                        <dd class="font-medium uppercase text-ink">{{ $order->payment?->method ?? 'Midtrans' }} {{ $order->payment?->bank ? '('.strtoupper($order->payment->bank).')' : '' }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-dashed border-slate-200 pt-2.5 font-bold text-base text-ink">
                        <dt>Total Bayar</dt>
                        <dd>{{ $rupiah($order->agreed_price) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Tombol Aksi Sukses (Fixed Bottom) -->
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
                <a href="{{ route('pesan.mulai', $order->listing) }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow-md hover:bg-slate-800 transition">
                    Chat Penjual untuk Janjian
                </a>
                <a href="{{ route('order.show', $order->order_number) }}" class="mt-2 block w-full rounded-full border border-slate-200 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Lihat Detail Pesanan
                </a>
            </div>

        {{-- 2. STATE MENUNGGU PEMBAYARAN (ui/bayar.html) --}}
        @elseif ($isMenunggu)
            <section class="text-center pt-2">
                <div class="mx-auto grid size-20 place-items-center rounded-full bg-amber-50 text-amber-600">
                    <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-2xl font-extrabold text-ink">Menunggu Pembayaran</h2>
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3.5 py-1 text-xs font-semibold text-amber-800">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    Bayar sebelum <span>{{ $order->payment?->expired_at ? $order->payment->expired_at->format('H:i') : '24 Jam' }}</span>
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Selesaikan pembayaran sebelum batas waktu terlewat agar pesanan tidak batal otomatis.
                </p>
            </section>

            <!-- Kartu Pembayaran VA / QRIS / E-Wallet -->
            @if ($order->payment && ($order->payment->va_number || $order->payment->method === 'va'))
                <section class="rounded-2xl border border-slate-200 p-4 shadow-card">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('assets/illust/checkout/bank.svg') }}" alt="Bank" class="size-9 shrink-0 object-contain">
                        <div class="flex-1">
                            <p class="font-semibold text-sm">{{ strtoupper($order->payment->bank ?? 'BCA') }} Virtual Account</p>
                            <p class="text-xs text-slate-500">Diproses Midtrans</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-slate-500">Nomor virtual account</p>
                    <div class="mt-1 flex items-center justify-between gap-3">
                        <p id="va-number" class="text-xl font-bold tracking-wide font-mono">{{ $order->payment->va_number ?? '8077 0812 3456 7890' }}</p>
                        <button type="button" id="btn-copy-va" data-va="{{ $order->payment->va_number ?? '8077081234567890' }}" class="shrink-0 rounded-full border border-slate-300 px-4 py-1.5 text-xs font-semibold hover:bg-slate-50">
                            Salin
                        </button>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3">
                        <p class="text-xs text-slate-500">Jumlah bayar</p>
                        <p class="text-lg font-bold text-ink">{{ $rupiah($order->agreed_price) }}</p>
                    </div>
                </section>

                <!-- Cara Bayar (Accordion) sesuai ui/bayar.html -->
                <section id="cara-bayar" class="rounded-2xl border border-slate-200 text-sm">
                    <h2 class="px-4 pt-4 font-bold">Cara bayar</h2>
                    <details class="group border-b border-slate-100 px-4 py-3" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold [&::-webkit-details-marker]:hidden">
                            m-Banking ({{ strtoupper($order->payment->bank ?? 'BCA') }})
                            <svg class="size-4 transition group-open:rotate-180 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-slate-600">
                            <li>Buka aplikasi m-Banking, pilih menu Pembayaran / Transfer.</li>
                            <li>Pilih Virtual Account.</li>
                            <li>Masukkan nomor Virtual Account di atas.</li>
                            <li>Cek rincian pesanan dan jumlah bayar, lalu masukkan PIN transaksi.</li>
                        </ol>
                    </details>
                    <details class="group border-b border-slate-100 px-4 py-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold [&::-webkit-details-marker]:hidden">
                            ATM ({{ strtoupper($order->payment->bank ?? 'BCA') }})
                            <svg class="size-4 transition group-open:rotate-180 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-slate-600">
                            <li>Masukkan kartu ATM dan PIN kamu.</li>
                            <li>Pilih Transaksi Lainnya, lalu Transfer / Pembayaran.</li>
                            <li>Pilih ke Rekening Virtual Account.</li>
                            <li>Masukkan nomor VA dan konfirmasi nominal pembayaran.</li>
                        </ol>
                    </details>
                    <details class="group px-4 py-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold [&::-webkit-details-marker]:hidden">
                            Internet Banking
                            <svg class="size-4 transition group-open:rotate-180 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-slate-600">
                            <li>Login ke akun Internet Banking kamu.</li>
                            <li>Pilih menu Transfer Dana ke Virtual Account.</li>
                            <li>Masukkan nomor VA dan otentikasi transaksi kamu.</li>
                        </ol>
                    </details>
                </section>
            @else
                <section class="rounded-2xl border border-slate-200 p-4 shadow-card text-center">
                    <p class="font-semibold text-sm">Pembayaran Instan Midtrans Snap</p>
                    <p class="text-xs text-slate-500 mt-1">Gunakan QRIS, Virtual Account, atau E-Wallet pilihanmu</p>
                    <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-left">
                        <p class="text-xs text-slate-500">Jumlah bayar</p>
                        <p class="text-lg font-bold text-ink">{{ $rupiah($order->agreed_price) }}</p>
                    </div>
                </section>
            @endif

            <!-- Banner Garansi Rekber Reloop sesuai ui/bayar.html -->
            <section class="flex gap-3 rounded-2xl bg-loop-soft p-4">
                <svg class="size-6 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                <p class="text-sm text-slate-600"><b class="font-semibold text-ink">Dana ditahan Reloop</b> sampai kamu konfirmasi barang diterima.</p>
            </section>

            <!-- Aksi Bayar & Cek Status (Fixed Bottom) -->
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
                <button type="button" id="btn-bayar-snap" class="block w-full rounded-full bg-ink py-3.5 font-semibold text-white shadow-md flex items-center justify-center gap-2 hover:bg-slate-800 transition">
                    <span id="btn-bayar-label">Bayar Sekarang via Snap</span>
                </button>
                <button type="button" onclick="window.location.reload()" class="mt-2 block w-full py-2 text-center text-sm font-semibold text-slate-500 hover:text-ink transition flex items-center justify-center gap-2">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                    Cek Status Pembayaran
                </button>
            </div>

        {{-- 3. STATE GAGAL / KEDALUWARSA / DIBATALKAN (ui/bayar.html data-state="kedaluwarsa") --}}
        @else
            <section class="text-center pt-2">
                <div class="mx-auto grid size-20 place-items-center rounded-full bg-slate-100 text-slate-500">
                    <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <h2 class="mt-4 text-2xl font-extrabold text-ink">Pesanan Dibatalkan</h2>
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3.5 py-1 text-xs font-semibold text-slate-600">
                    Batas Waktu Bayar Terlewat / Dibatalkan
                </p>
                <p class="mx-auto mt-2 max-w-[320px] text-xs text-slate-500 leading-relaxed">
                    Pembayaran untuk <b>{{ $order->listing?->title }}</b> tidak diterima atau dibatalkan. Kamu tidak dikenai biaya apa pun, dan barang telah dibuka kembali ke katalog.
                </p>
            </section>

            <!-- Aksi Dibatalkan (Fixed Bottom) -->
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)]">
                <a href="{{ route('cari') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow-md hover:bg-slate-800 transition">
                    Cari Barang Lain
                </a>
                <a href="{{ route('beranda') }}" class="mt-2 block w-full rounded-full border border-slate-200 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Kembali ke Beranda
                </a>
            </div>
        @endif
    </main>

    @push('scripts')
        @if ($isMenunggu)
            <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
            <script>
                // Salin nomor VA
                document.getElementById('btn-copy-va')?.addEventListener('click', function () {
                    const va = this.getAttribute('data-va');
                    navigator.clipboard.writeText(va).then(() => {
                        this.textContent = 'Tersalin!';
                        setTimeout(() => this.textContent = 'Salin', 2000);
                    });
                });

                // Trigger Snap Pay
                document.getElementById('btn-bayar-snap')?.addEventListener('click', async function () {
                    const btn = this;
                    const label = document.getElementById('btn-bayar-label');
                    btn.disabled = true;
                    label.textContent = 'Menghubungkan Midtrans...';

                    try {
                        const res = await fetch("{{ route('order.snap_token', $order->order_number) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                        });

                        const data = await res.json();

                        if (!res.ok || !data.token) {
                            throw new Error(data.message || 'Gagal memuat token pembayaran.');
                        }

                        if (typeof window.snap !== 'undefined' && typeof window.snap.pay === 'function') {
                            window.snap.pay(data.token, {
                                onSuccess: function () {
                                    window.location.href = "{{ route('order.status', $order->order_number) }}?status=sukses";
                                },
                                onPending: function () {
                                    window.location.href = "{{ route('order.status', $order->order_number) }}?status=menunggu";
                                },
                                onError: function () {
                                    window.location.href = "{{ route('order.status', $order->order_number) }}?status=gagal";
                                },
                                onClose: function () {
                                    btn.disabled = false;
                                    label.textContent = 'Bayar Sekarang via Snap';
                                }
                            });
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    } catch (e) {
                        alert(e.message);
                        btn.disabled = false;
                        label.textContent = 'Bayar Sekarang via Snap';
                    }
                });

                // Auto-polling deteksi otomatis pembayaran via server & Midtrans status API
                const statusPoll = setInterval(async function () {
                    try {
                        const res = await fetch("{{ route('order.status', $order->order_number) }}", {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.status === '{{ \App\Models\Order::STATUS_DIBAYAR }}' || data.is_paid) {
                                clearInterval(statusPoll);
                                window.location.href = "{{ route('order.status', $order->order_number) }}?status=sukses";
                            } else if (data.status === '{{ \App\Models\Order::STATUS_DIBATALKAN }}') {
                                clearInterval(statusPoll);
                                window.location.reload();
                            }
                        }
                    } catch (e) {
                        // Ignore network transient error
                    }
                }, 2000);
            </script>
        @endif
    @endpush
</x-app-layout>
