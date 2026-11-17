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
            {{-- Konfirmasi pembayaran (pola Shop "Order confirmed") --}}
            <section class="pt-1 text-center">
                <div class="min-w-0">
                    <span class="mx-auto grid size-11 place-items-center rounded-full bg-loop-soft text-loop-dark">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <h2 class="mt-3 text-2xl font-extrabold leading-tight text-ink">Pembayaran berhasil</h2>
                    <p class="mt-1 text-sm text-slate-500">Pesanan <span class="font-mono font-medium text-ink">#{{ $order->order_number }}</span></p>
                </div>
            </section>

            <p class="flex items-start gap-2 rounded-2xl bg-loop-soft px-3.5 py-3 text-xs leading-relaxed text-loop-dark">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/></svg>
                <span><b>Dana ditahan Rekber Reloop.</b> Baru diteruskan ke penjual setelah kamu memeriksa dan mengonfirmasi barang.</span>
            </p>

            <section class="divide-y divide-slate-100 rounded-2xl border border-slate-200 text-sm">
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <span class="text-slate-500">Barang</span>
                    <span class="max-w-[200px] truncate font-medium text-ink">{{ $order->listing?->title }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <span class="text-slate-500">Serah terima</span>
                    <span class="font-medium text-ink">{{ $order->deliveryLabel() }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <span class="text-slate-500">Metode bayar</span>
                    <span class="font-medium uppercase text-ink">{{ $order->payment?->method ?? 'Midtrans' }}{{ $order->payment?->bank ? ' · '.strtoupper($order->payment->bank) : '' }}</span>
                </div>
                @if ($order->payment?->paid_at)
                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                        <span class="text-slate-500">Dibayar pada</span>
                        <span class="font-medium text-ink">{{ $order->payment->paid_at->format('d M Y, H:i') }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 px-4 py-3.5">
                    <span class="font-bold text-ink">Total bayar</span>
                    <span class="text-lg font-extrabold text-ink">{{ $rupiah($order->agreed_price) }}</span>
                </div>
            </section>

            {{-- Langkah berikutnya --}}
            <section class="rounded-2xl border border-slate-200">
                <h3 class="border-b border-slate-200 px-4 py-3 text-sm font-bold text-ink">Langkah berikutnya</h3>
                <ol class="space-y-3.5 p-4 text-sm">
                    @foreach ([
                        ['Pembayaran diterima', 'Penjual sudah kami kabari untuk menyiapkan barang.', true],
                        ['Janjian serah terima', 'Atur waktu dan titik temu lewat chat dengan penjual.', false],
                        ['Periksa & konfirmasi barang', 'Setelah barang sesuai, konfirmasi agar dana diteruskan ke penjual.', false],
                    ] as $i => [$judul, $ket, $done])
                        <li class="flex items-center gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full text-xs font-bold {{ $done ? 'bg-loop-dark text-white' : 'border border-slate-300 text-slate-500' }}">
                                @if ($done)<svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>@else{{ $i + 1 }}@endif
                            </span>
                            <div>
                                <p class="font-semibold text-ink">{{ $judul }}</p>
                                <p class="text-xs leading-relaxed text-slate-500">{{ $ket }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <!-- Tombol Aksi Sukses (Fixed Bottom) -->
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-2px_8px_rgba(0,0,0,0.03)]">
                <a href="{{ route('pesan.mulai', $order->listing) }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow-md hover:bg-slate-800 transition">
                    Chat Penjual untuk Janjian
                </a>
                <a href="{{ route('order.show', $order->order_number) }}" class="mt-2 block w-full rounded-full border border-slate-200 py-3.5 text-center font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Lihat Detail Pesanan
                </a>
            </div>

        {{-- 2. STATE MENUNGGU PEMBAYARAN (ui/bayar.html) --}}
        @elseif ($isMenunggu)
            @php $batas = $order->payment?->expired_at ?? $order->created_at->copy()->addHours(24); @endphp
            <section class="text-center pt-2">
                <div class="mx-auto grid size-24 place-items-center rounded-2xl bg-amber-50 text-amber-600">
                    <svg class="size-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <h2 class="mt-3 text-xl font-bold">Menunggu pembayaran</h2>
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-800">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    Bayar sebelum <span id="countdown" data-end="{{ $batas->getTimestamp() }}">--:--:--</span>
                </p>
                <p class="mt-2 text-xs text-slate-500">Lewat batas waktu, pesanan batal otomatis.</p>
            </section>

            <!-- Ringkasan pesanan -->
            <section class="rounded-2xl border border-slate-200 p-4 shadow-card">
                <div class="flex items-center gap-3">
                    <img src="{{ $fotoUrl }}" alt="" width="48" height="48" class="size-12 shrink-0 rounded-xl bg-[#f7f8f4] object-contain p-1">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $order->listing?->title }}</p>
                        <p class="text-xs text-slate-500">#{{ $order->order_number }}</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3">
                    <p class="text-sm text-slate-500">Jumlah bayar</p>
                    <p class="text-lg font-bold">{{ $rupiah($order->agreed_price) }}</p>
                </div>
            </section>

            <section class="flex gap-3 rounded-2xl bg-loop-soft p-4">
                <svg class="size-6 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
                <p class="text-sm text-slate-600">Dana ditahan Reloop sampai kamu konfirmasi barang diterima.</p>
            </section>

            <!-- Aksi (Fixed Bottom) -->
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-2px_8px_rgba(0,0,0,0.03)]">
                <button type="button" id="btn-bayar-snap" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white hover:bg-slate-800 transition disabled:opacity-60">
                    <span id="btn-bayar-label">Lanjut bayar</span>
                </button>
                <button type="button" onclick="window.location.reload()" class="mt-2 block w-full rounded-full border border-slate-300 py-3.5 text-center font-semibold hover:bg-slate-50 transition">
                    Cek status pembayaran
                </button>
            </div>

        {{-- 3. STATE GAGAL / KEDALUWARSA / DIBATALKAN (ui/bayar.html data-state="kedaluwarsa") --}}
        @else
            <section class="pt-6 text-center">
                <div class="mx-auto grid size-24 place-items-center rounded-2xl bg-slate-100 text-slate-500">
                    <svg class="size-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                </div>
                <h2 class="mt-3 text-xl font-bold">Pesanan dibatalkan</h2>
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-600">Batas bayar 24 jam terlewat</p>
                <p class="mx-auto mt-3 max-w-[300px] text-sm text-slate-500">Pembayaran untuk {{ $order->listing?->title }} tidak kami terima, jadi pesanan batal otomatis. Kamu tidak dikenai biaya apa pun.</p>
            </section>

            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-2px_8px_rgba(0,0,0,0.03)]">
                <a href="{{ route('cari') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white hover:bg-slate-800 transition">Cari barang lain</a>
                @if ($order->listing)
                    <a href="{{ route('barang.detail', $order->listing) }}" class="mt-1 block w-full py-2 text-center text-sm font-semibold text-slate-500">Lihat barang ini lagi</a>
                @endif
            </div>
        @endif
    </main>

    @push('scripts')
        @if ($isMenunggu)
            <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
            <script>
                // Hitung mundur batas bayar
                const cd = document.getElementById('countdown');
                const tick = () => {
                    const s = Math.max(0, Number(cd.dataset.end) - Math.floor(Date.now() / 1000));
                    cd.textContent = [s / 3600, (s % 3600) / 60, s % 60].map((n) => String(Math.floor(n)).padStart(2, '0')).join(':');
                    if (s === 0) window.location.reload();
                };
                tick();
                setInterval(tick, 1000);

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
                                    label.textContent = 'Lanjut bayar';
                                }
                            });
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    } catch (e) {
                        alert(e.message);
                        btn.disabled = false;
                        label.textContent = 'Lanjut bayar';
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
