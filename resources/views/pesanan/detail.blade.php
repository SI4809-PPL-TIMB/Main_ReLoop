<x-app-layout>
    <x-slot name="title">Reloop · Detail Pesanan {{ $order->order_number }}</x-slot>

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $isBuyer = auth()->id() === $order->buyer_id;
        $statusLabel = [
            \App\Models\Order::STATUS_MENUNGGU_BAYAR => 'Menunggu Pembayaran',
            \App\Models\Order::STATUS_DIBAYAR        => 'Sudah Dibayar (Dana Ditahan)',
            \App\Models\Order::STATUS_DISERAHKAN     => 'Barang Diserahkan',
            \App\Models\Order::STATUS_SELESAI        => 'Selesai',
            \App\Models\Order::STATUS_DIBATALKAN     => 'Dibatalkan',
        ][$order->status] ?? ucfirst($order->status);

        $fotoUrl = $order->listing?->primaryPhoto?->url ?? asset('assets/illust/activities/kursi-jati.png');
    @endphp

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white pb-28">
        <!-- Header -->
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
            <a href="{{ url()->previous() }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold leading-tight">Detail Pesanan</h1>
                <p class="text-xs text-slate-500">{{ $order->order_number }}</p>
            </div>
        </header>

        <main class="space-y-4 px-4 pt-4">
            <!-- Status Card -->
            <section class="rounded-2xl border border-slate-200 bg-gradient-to-r from-loop-soft via-white to-white p-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-full bg-loop text-ink font-bold">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-loop-dark">{{ $statusLabel }}</p>
                        <p class="text-2xl font-bold">{{ $rupiah($order->agreed_price) }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    @if($isBuyer)
                        Dana pembayaran kamu ditahan aman di Reloop sampai kamu mengonfirmasi barang diterima dengan baik.
                    @else
                        Dana pembeli tertahan aman di rekening bersama Reloop dan akan diteruskan ke kamu setelah pembeli mengonfirmasi barang diterima.
                    @endif
                </p>
            </section>

            <!-- Ringkasan Barang -->
            <section class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3">
                <img src="{{ $fotoUrl }}" alt="{{ $order->listing?->title }}" class="size-16 shrink-0 rounded-lg bg-slate-100 object-contain p-1">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ $order->listing?->title }}</p>
                    <p class="text-xs text-slate-500">Penjual: {{ $order->seller?->name }}</p>
                    <p class="mt-1 font-bold text-ink">{{ $rupiah($order->agreed_price) }}</p>
                </div>
            </section>

            <!-- Detail Pengiriman / Serah Terima -->
            <section class="rounded-2xl border border-slate-200 p-4 text-sm">
                <h2 class="font-bold">Serah Terima</h2>
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Metode</span>
                        <span class="font-semibold text-ink">{{ $order->delivery_method === \App\Models\Order::DELIVERY_COD ? 'Ketemuan (COD)' : 'Ambil di Tempat Penjual' }}</span>
                    </div>
                    @if($order->delivery_address)
                        <div class="border-t border-slate-100 pt-2">
                            <span class="text-xs text-slate-500 block">Alamat / Catatan Lokasi:</span>
                            <span class="font-medium text-ink">{{ $order->delivery_address }}</span>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Rincian Pembayaran -->
            <section class="rounded-2xl border border-slate-200 p-4 text-sm">
                <h2 class="font-bold">Rincian Biaya</h2>
                <dl class="mt-3 space-y-2">
                    <div class="flex justify-between text-slate-600">
                        <dt>Harga Barang</dt>
                        <dd class="font-medium text-ink">{{ $rupiah($order->agreed_price) }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <dt>Biaya Layanan Pembeli</dt>
                        <dd class="font-medium text-ink">Rp 0</dd>
                    </div>
                    <div class="flex justify-between border-t border-dashed border-slate-200 pt-3 text-base font-bold text-ink">
                        <dt>Total Bayar</dt>
                        <dd>{{ $rupiah($order->agreed_price) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Aksi Pembayaran jika Masih Menunggu Pembayaran -->
            @if ($isBuyer && $order->status === \App\Models\Order::STATUS_MENUNGGU_BAYAR)
                <div class="pt-4">
                    <button type="button" id="btn-bayar-snap" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white shadow-lg flex items-center justify-center gap-2 hover:bg-slate-800 transition">
                        <span id="btn-bayar-label">Bayar Sekarang</span>
                    </button>
                    <p class="mt-2 text-center text-xs text-slate-500">Batas waktu bayar 24 jam sebelum pesanan batal otomatis.</p>
                </div>
            @endif
        </main>
    </div>

    @if ($isBuyer && $order->status === \App\Models\Order::STATUS_MENUNGGU_BAYAR)
        @push('scripts')
            <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
            <script>
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
                                    window.location.href = "{{ route('order.show', $order->order_number) }}?status=sukses";
                                },
                                onPending: function () {
                                    window.location.href = "{{ route('order.show', $order->order_number) }}?status=menunggu";
                                },
                                onError: function () {
                                    window.location.href = "{{ route('order.show', $order->order_number) }}?status=gagal";
                                },
                                onClose: function () {
                                    btn.disabled = false;
                                    label.textContent = 'Bayar Sekarang';
                                }
                            });
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    } catch (e) {
                        alert(e.message);
                        btn.disabled = false;
                        label.textContent = 'Bayar Sekarang';
                    }
                });
            </script>
        @endpush
    @endif
</x-app-layout>
