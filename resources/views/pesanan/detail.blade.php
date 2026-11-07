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
        <header class="sticky top-0 z-20 grid grid-cols-[40px_1fr_40px] items-center gap-3 border-b border-slate-100 bg-white px-4 py-4">
            <a href="{{ route('beranda') }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="text-center text-xl font-bold leading-tight">Detail Pesanan</h1>
            <span aria-hidden="true"></span>
        </header>

        <main class="space-y-4 px-4 pt-5">
            @php
                $pil = [
                    \App\Models\Order::STATUS_MENUNGGU_BAYAR => 'bg-amber-50 text-amber-800',
                    \App\Models\Order::STATUS_DIBAYAR => 'bg-loop-soft text-loop-dark',
                    \App\Models\Order::STATUS_DISERAHKAN => 'bg-loop-soft text-loop-dark',
                    \App\Models\Order::STATUS_SELESAI => 'bg-loop-soft text-loop-dark',
                    \App\Models\Order::STATUS_DIBATALKAN => 'bg-slate-100 text-slate-600',
                ][$order->status] ?? 'bg-slate-100 text-slate-600';
                $lawan = $isBuyer ? $order->seller : $order->buyer;
                $ikon = 'size-5';
            @endphp

            {{-- Status + judul (struktur Fresha, gaya Reloop) --}}
            <section>
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold {{ $pil }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/></svg>
                    {{ $statusLabel }}
                </span>
                <h2 class="mt-3 text-[22px] font-bold leading-tight">{{ $order->created_at->locale('id')->translatedFormat('l, j F Y') }}, {{ $order->created_at->format('H.i') }}</h2>
                <p class="mt-1 text-sm text-slate-500">Pesanan <span class="font-mono text-ink">#{{ $order->order_number }}</span></p>
            </section>

            {{-- Escrow --}}
            @if (in_array($order->status, [\App\Models\Order::STATUS_MENUNGGU_BAYAR, \App\Models\Order::STATUS_DIBAYAR, \App\Models\Order::STATUS_DISERAHKAN]))
                <section class="flex gap-3 rounded-2xl bg-loop-soft p-4">
                    <svg class="size-6 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                    <p class="text-sm text-slate-600">
                        <b class="font-semibold text-ink">Dana ditahan Reloop</b>
                        {{ $isBuyer ? 'sampai kamu konfirmasi barang diterima.' : 'dan diteruskan ke kamu setelah pembeli mengonfirmasi barang.' }}
                    </p>
                </section>
            @endif

            {{-- Aksi cepat --}}
            <section class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                <a href="{{ $isBuyer && $order->listing ? route('pesan.mulai', $order->listing) : route('pesan.index') }}" class="flex items-center gap-3 px-3 py-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100"><svg class="{{ $ikon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg></span>
                    <span class="min-w-0 flex-1"><b class="block">Chat {{ $isBuyer ? 'penjual' : 'pembeli' }}</b><span class="block truncate text-xs text-slate-500">Atur serah terima dengan {{ $lawan?->name }}</span></span>
                    <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </a>
            </section>

            {{-- Rincian pesanan --}}
            <section class="rounded-2xl border border-slate-200">
                <h2 class="border-b border-slate-200 px-4 py-3 text-base font-bold">Rincian pesanan</h2>
                <div class="p-4">
                <div class="flex items-center gap-3">
                    <img src="{{ $fotoUrl }}" alt="" width="56" height="56" class="size-14 shrink-0 rounded-lg bg-[#f7f8f4] object-contain p-1">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $order->listing?->title }}</p>
                        <p class="text-xs text-slate-500">Penjual: {{ $order->seller?->name }}</p>
                    </div>
                    <p class="shrink-0 font-bold">{{ $rupiah($order->agreed_price) }}</p>
                </div>
                <dl class="mt-4 space-y-2 border-t border-dashed border-slate-200 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Harga barang</dt><dd class="font-medium">{{ $rupiah($order->agreed_price) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Biaya layanan</dt><dd class="font-medium">Rp 0</dd></div>
                    <div class="flex justify-between pt-1"><dt class="font-semibold">Total bayar</dt><dd class="text-lg font-bold">{{ $rupiah($order->agreed_price) }}</dd></div>
                </dl>
                    <div class="mt-4 border-t border-slate-200 pt-4 text-sm">
                        <p class="text-xs text-slate-500">Metode serah terima</p>
                        <p class="mt-1 font-semibold">{{ $order->deliveryLabel() }}</p>
                        @if ($order->delivery_address)
                            <p class="mt-3 text-xs text-slate-500">Alamat</p>
                            <p class="mt-1 font-semibold">{{ $order->delivery_address }}</p>
                        @endif
                    </div>
                </div>
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
