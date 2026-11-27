<x-app-layout>
    <x-slot name="title">Reloop · Detail Pesanan {{ $order->order_number }}</x-slot>

    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $isBuyer = auth()->id() === $order->buyer_id;
        $isSeller = auth()->id() === $order->seller_id;
        $lawan = $isBuyer ? $order->seller : $order->buyer;

        $fotoUrl = $order->listing?->primaryPhoto?->url ?? asset('assets/illust/activities/kursi-jati.png');

        // Nilai komisi 3% dan nominal bersih penjual
        $platformFee = $order->platformFee();
        $netSellerAmount = $order->netSellerAmount();

        // Hitung sisa detik auto complete (jika diserahkan)
        $autoCompleteDiff = $order->auto_complete_at ? max(0, now()->diffInSeconds($order->auto_complete_at, false)) : 0;
        $autoHours = floor($autoCompleteDiff / 3600);
        $autoMins = floor(($autoCompleteDiff % 3600) / 60);

        // URL Chat lawan transaksi (mengarah ke room obrolan spesifik atau mulai obrolan)
        $chatUrl = route('pesan.index');
        if ($order->listing) {
            $conversation = \App\Models\Conversation::where('listing_id', $order->listing_id)
                ->where('buyer_id', $order->buyer_id)
                ->first();
            if ($conversation) {
                $chatUrl = route('pesan.show', $conversation);
            } elseif ($isBuyer) {
                $chatUrl = route('pesan.mulai', $order->listing);
            }
        }

        // Langkah timeline aktif
        $stepIndex = match ($order->status) {
            \App\Models\Order::STATUS_MENUNGGU_BAYAR => 0,
            \App\Models\Order::STATUS_DIBAYAR        => 1,
            \App\Models\Order::STATUS_DISERAHKAN     => 2,
            \App\Models\Order::STATUS_SELESAI        => 3,
            default                                  => -1, // dibatalkan
        };
    @endphp

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white pb-36 text-ink antialiased">
        <!-- Header -->
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-4">
            <a href="{{ $isBuyer ? route('aktivitas') : route('barang.index', ['tab' => $order->status === \App\Models\Order::STATUS_SELESAI ? 'terjual' : 'dipesan']) }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <div class="text-center">
                <h1 class="text-lg font-bold leading-tight">Detail Pesanan</h1>
                <p class="font-mono text-xs text-slate-500">#{{ $order->order_number }}</p>
            </div>
            <a href="{{ $chatUrl }}" aria-label="Chat" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
            </a>
        </header>

        <main class="space-y-4 px-4 pt-4">
            {{-- Flash Alert Messages --}}
            @if (session('success'))
                <div role="status" class="rounded-xl border border-brand-300 bg-brand-50 p-3.5 text-sm text-brand-900 flex items-start gap-2.5">
                    <svg class="size-5 shrink-0 text-brand-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm text-red-800">
                    <p class="font-semibold">{{ $errors->first() }}</p>
                </div>
            @endif

            {{-- 1. State Banners (Dibatalkan / Selesai / Dana Tertahan / Menunggu Bayar) --}}
            @if ($order->status === \App\Models\Order::STATUS_DIBATALKAN)
                <section class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <svg class="size-9 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pesanan dibatalkan</p>
                        <p class="text-2xl font-bold">{{ $rupiah($order->agreed_price) }}</p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            Dibatalkan sebelum barang diserahkan. Dana kembali ke rekening {{ $isBuyer ? 'asal' : 'pembeli' }} dalam 1 sampai 3 hari kerja.{{ $isSeller ? ' Barangmu aktif lagi di marketplace.' : '' }}
                        </p>
                        @if ($order->cancel_reason)
                            <p class="mt-1 text-xs italic text-slate-500">Alasan: {{ $order->cancel_reason }}</p>
                        @endif
                    </div>
                </section>
            @elseif ($order->status === \App\Models\Order::STATUS_SELESAI)
                <section class="flex items-center gap-4 rounded-2xl border border-loop bg-loop-soft p-4">
                    <svg class="size-9 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-loop-dark">Pesanan selesai</p>
                        <p class="text-2xl font-bold">{{ $rupiah($order->agreed_price) }}</p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            @if ($isBuyer)
                                Dana sudah diteruskan ke penjual. Streak +1
                            @else
                                {{ $rupiah($netSellerAmount) }} masuk saldomu (setelah komisi 3%). Streak +1
                            @endif
                        </p>
                    </div>
                </section>
            @elseif ($order->isEscrowHeld())
                {{-- Escrow Dana Tertahan --}}
                <section class="flex items-center gap-4 rounded-2xl border border-loop bg-loop-soft p-4">
                    <svg class="size-9 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-loop-dark">Dana tertahan</p>
                        <p class="text-2xl font-bold">{{ $rupiah($order->agreed_price) }}</p>
                        <p class="mt-0.5 text-xs text-slate-600">
                            @if ($isBuyer)
                                Aman di Reloop sampai kamu konfirmasi barang diterima.
                            @else
                                Cair ke saldomu setelah pembeli konfirmasi barang diterima.
                            @endif
                        </p>
                    </div>
                </section>
            @elseif ($order->status === \App\Models\Order::STATUS_MENUNGGU_BAYAR)
                <section class="flex items-center gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <svg class="size-9 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Menunggu pembayaran</p>
                        <p class="text-2xl font-bold text-amber-900">{{ $rupiah($order->agreed_price) }}</p>
                        <p class="mt-0.5 text-xs text-amber-800">
                            {{ $isBuyer ? 'Selesaikan pembayaran dalam 24 jam agar pesanan tidak dibatalkan otomatis.' : 'Menunggu pembeli menyelesaikan pembayaran.' }}
                        </p>
                    </div>
                </section>
            @endif

            {{-- 2. Timeline Status Pesanan (4 Langkah) --}}
            @if ($order->status !== \App\Models\Order::STATUS_DIBATALKAN)
                <section class="rounded-2xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold">Status</h2>
                        @if ($order->status === \App\Models\Order::STATUS_DIBAYAR && $isSeller)
                            <button type="button" onclick="document.getElementById('modal-serah').showModal()" class="text-xs font-semibold text-loop-dark hover:underline">Perbarui</button>
                        @endif
                    </div>
                    <ol class="mt-3 space-y-4 text-sm">
                        {{-- Step 0: Dibayar --}}
                        @php
                            $step0Done = in_array($order->status, [\App\Models\Order::STATUS_DIBAYAR, \App\Models\Order::STATUS_DISERAHKAN, \App\Models\Order::STATUS_SELESAI]);
                            $step0Now  = $order->status === \App\Models\Order::STATUS_MENUNGGU_BAYAR;
                        @endphp
                        <li class="flex gap-3 {{ $step0Done ? 'text-ink' : ($step0Now ? 'text-ink font-semibold' : 'text-slate-400') }}">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step0Done ? 'border-ink bg-ink text-white' : ($step0Now ? 'border-ink text-ink' : 'border-slate-200 text-slate-300') }}">
                                @if ($step0Done)
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="m5 12 5 5 9-10"/></svg>
                                @elseif ($step0Now)
                                    <span class="size-2 rounded-full bg-ink"></span>
                                @endif
                            </span>
                            <div>
                                <p class="font-semibold">Dibayar</p>
                                @if ($step0Done)
                                    <p class="text-xs text-slate-500">
                                        {{ $order->payment?->paid_at ? $order->payment->paid_at->locale('id')->translatedFormat('j M, H.i') : $order->created_at->locale('id')->translatedFormat('j M, H.i') }} · 
                                        <span>{{ $order->payment?->bank ? strtoupper($order->payment->bank) . ' Virtual Account' : ($order->payment?->method === 'qris' ? 'QRIS' : 'Pembayaran Lunas') }}</span>
                                    </p>
                                @else
                                    <p class="text-xs text-amber-600">Batas waktu bayar 24 jam</p>
                                @endif
                            </div>
                        </li>

                        {{-- Step 1: Diserahkan Penjual --}}
                        @php
                            $step1Done = in_array($order->status, [\App\Models\Order::STATUS_DISERAHKAN, \App\Models\Order::STATUS_SELESAI]);
                            $step1Now  = $order->status === \App\Models\Order::STATUS_DIBAYAR;
                        @endphp
                        <li class="flex gap-3 {{ $step1Done ? 'text-ink' : ($step1Now ? 'text-ink' : 'text-slate-400') }}">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step1Done ? 'border-ink bg-ink text-white' : ($step1Now ? 'border-ink text-ink' : 'border-slate-200 text-slate-300') }}">
                                @if ($step1Done)
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="m5 12 5 5 9-10"/></svg>
                                @elseif ($step1Now)
                                    <span class="size-2 rounded-full bg-ink"></span>
                                @endif
                            </span>
                            <div>
                                <p class="font-semibold">Diserahkan penjual</p>
                                @if ($step1Now)
                                    <p class="text-xs text-orange-500">
                                        @if ($isBuyer)
                                            Menunggu penjual menyerahkan barang · atur titik temu lewat chat
                                        @else
                                            Serahkan barang ke pembeli, lalu tandai di bawah
                                        @endif
                                    </p>
                                @elseif ($step1Done)
                                    <p class="text-xs text-slate-500">
                                        {{ $order->seller_confirmed_at ? $order->seller_confirmed_at->locale('id')->translatedFormat('j M, H.i') : '-' }} · {{ $order->deliveryLabel() }}
                                    </p>
                                @else
                                    <p class="text-xs text-slate-400">Menunggu konfirmasi pembayaran</p>
                                @endif
                            </div>
                        </li>

                        {{-- Step 2: Diterima Pembeli --}}
                        @php
                            $step2Done = $order->status === \App\Models\Order::STATUS_SELESAI;
                            $step2Now  = $order->status === \App\Models\Order::STATUS_DISERAHKAN;
                        @endphp
                        <li class="flex gap-3 {{ $step2Done ? 'text-ink' : ($step2Now ? 'text-ink' : 'text-slate-400') }}">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step2Done ? 'border-ink bg-ink text-white' : ($step2Now ? 'border-ink text-ink' : 'border-slate-200 text-slate-300') }}">
                                @if ($step2Done)
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="m5 12 5 5 9-10"/></svg>
                                @elseif ($step2Now)
                                    <span class="size-2 rounded-full bg-ink"></span>
                                @endif
                            </span>
                            <div>
                                <p class="font-semibold">Diterima pembeli</p>
                                @if ($step2Now)
                                    <p class="text-xs text-orange-500">
                                        @if ($isBuyer)
                                            Menunggu konfirmasimu
                                        @else
                                            Menunggu konfirmasi pembeli
                                        @endif
                                        · otomatis selesai dalam <span id="countdown-auto" class="font-mono font-bold tabular-nums">{{ sprintf('%02d:%02d:00', $autoHours, $autoMins) }}</span>
                                    </p>
                                @elseif ($step2Done)
                                    <p class="text-xs text-slate-500">
                                        {{ $order->buyer_confirmed_at ? $order->buyer_confirmed_at->locale('id')->translatedFormat('j M, H.i') : '-' }} · dikonfirmasi {{ $isBuyer ? 'kamu' : 'pembeli' }}
                                    </p>
                                @else
                                    <p class="text-xs text-slate-400">Konfirmasi setelah barang dicek</p>
                                @endif
                            </div>
                        </li>

                        {{-- Step 3: Selesai --}}
                        @php
                            $step3Done = $order->status === \App\Models\Order::STATUS_SELESAI;
                        @endphp
                        <li class="flex gap-3 {{ $step3Done ? 'text-ink font-semibold' : 'text-slate-400' }}">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step3Done ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-300' }}">
                                @if ($step3Done)
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="m5 12 5 5 9-10"/></svg>
                                @endif
                            </span>
                            <div>
                                <p class="font-semibold">Selesai</p>
                                @if ($step3Done)
                                    <p class="text-xs text-slate-500">
                                        {{ $order->buyer_confirmed_at ? $order->buyer_confirmed_at->locale('id')->translatedFormat('j M, H.i') : '-' }} · dana cair ke {{ $isBuyer ? 'penjual' : 'saldomu' }}
                                    </p>
                                @else
                                    <p class="text-xs text-slate-400">Dana cair ke {{ $isBuyer ? 'penjual' : 'saldomu' }}</p>
                                @endif
                            </div>
                        </li>
                    </ol>
                </section>
            @endif

            {{-- 3. Kartu Barang & Lawan Transaksi --}}
            <section class="flex items-center gap-3 rounded-2xl border border-slate-200 p-3">
                <img src="{{ $fotoUrl }}" alt="{{ $order->listing?->title }}" width="56" height="56" class="size-14 shrink-0 rounded-lg bg-slate-50 object-contain p-1">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ $order->listing?->title ?? 'Barang Pesanan' }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $isBuyer ? 'Penjual: ' . ($order->seller?->name ?? 'Penjual') : 'Pembeli: ' . ($order->buyer?->name ?? 'Pembeli') }}
                    </p>
                </div>
                <a href="{{ $chatUrl }}" aria-label="Chat lawan transaksi" class="grid size-10 place-items-center rounded-full border border-slate-200 hover:bg-slate-50">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/></svg>
                </a>
            </section>

            {{-- 4. Rincian Pembayaran & Komisi 3% --}}
            <section class="rounded-2xl border border-slate-200 p-4 text-sm">
                <h2 class="font-bold">Rincian pembayaran</h2>
                <dl class="mt-2 space-y-1.5">
                    @if ($order->listing && (float) $order->listing->price > (float) $order->agreed_price)
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Harga awal</dt>
                            <dd class="text-slate-400 line-through">{{ $rupiah($order->listing->price) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Harga kesepakatan</dt>
                            <dd class="font-semibold">{{ $rupiah($order->agreed_price) }}</dd>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Harga barang</dt>
                            <dd class="font-semibold">{{ $rupiah($order->agreed_price) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Biaya layanan</dt>
                        <dd class="font-medium text-emerald-600">Rp 0 (Gratis)</dd>
                    </div>
                    <div class="flex justify-between border-t border-dashed border-slate-200 pt-2 font-bold text-base">
                        <dt>Total dibayar</dt>
                        <dd>{{ $rupiah($order->agreed_price) }}</dd>
                    </div>
                </dl>

                {{-- Penjelasan Komisi 3% (usulan BR / kriteria penerimaan) --}}
                <div class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 space-y-1">
                    <p>Penjual menerima <b>{{ $rupiah($netSellerAmount) }}</b> setelah komisi platform 3% ({{ $rupiah($platformFee) }}).</p>
                    <p class="text-slate-400">Pembeli tidak dikenai biaya tambahan apapun.</p>
                </div>

                {{-- Informasi Metode Pengiriman --}}
                <div class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    <p class="font-semibold text-slate-700">Metode serah terima:</p>
                    <p class="mt-0.5">{{ $order->deliveryLabel() }}</p>
                    @if ($order->delivery_address)
                        <p class="mt-1 font-semibold text-slate-700">Titik temu / Alamat:</p>
                        <p class="mt-0.5">{{ $order->delivery_address }}</p>
                    @endif
                </div>
            </section>
        </main>

        {{-- 5. Fixed Bottom Action Bar --}}
        <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            @if ($order->status === \App\Models\Order::STATUS_MENUNGGU_BAYAR)
                {{-- Menunggu Bayar --}}
                @if ($isBuyer)
                    <button type="button" id="btn-bayar-snap" class="w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Bayar Sekarang ({{ $rupiah($order->agreed_price) }})
                    </button>
                    <div class="mt-1 flex text-center">
                        <button type="button" onclick="document.getElementById('modal-batal').showModal()" class="flex-1 py-2 text-sm font-semibold text-slate-500 hover:text-red-600">Batalkan pesanan</button>
                    </div>
                @else
                    <button type="button" disabled class="w-full cursor-not-allowed rounded-full bg-slate-100 py-3.5 text-center font-semibold text-slate-400">
                        Menunggu pembayaran pembeli
                    </button>
                    <div class="mt-1 flex text-center">
                        <button type="button" onclick="document.getElementById('modal-batal').showModal()" class="flex-1 py-2 text-sm font-semibold text-slate-500 hover:text-red-600">Batalkan pesanan</button>
                    </div>
                @endif
            @elseif ($order->status === \App\Models\Order::STATUS_DIBAYAR)
                {{-- Status Dibayar: Siap Serah Terima --}}
                @if ($isBuyer)
                    <a href="{{ $chatUrl }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Chat penjual
                    </a>
                    <div class="mt-1 flex">
                        <button type="button" onclick="document.getElementById('modal-batal').showModal()" class="flex-1 py-2 text-center text-sm font-semibold text-slate-500 hover:text-red-600">Batalkan pesanan</button>
                        <a href="{{ route('lapor.create', ['tipe' => 'order', 'id' => $order->id]) }}" class="flex-1 py-2 text-center text-sm font-semibold text-red-500">Laporkan</a>
                    </div>
                @else
                    <button type="button" onclick="document.getElementById('modal-serah').showModal()" class="w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Tandai sudah diserahkan
                    </button>
                    <div class="mt-1 flex">
                        <button type="button" onclick="document.getElementById('modal-batal').showModal()" class="flex-1 py-2 text-center text-sm font-semibold text-slate-500 hover:text-red-600">Batalkan pesanan</button>
                        <a href="{{ $chatUrl }}" class="flex-1 py-2 text-center text-sm font-semibold text-slate-500">Chat pembeli</a>
                    </div>
                @endif
            @elseif ($order->status === \App\Models\Order::STATUS_DISERAHKAN)
                {{-- Status Diserahkan: Menunggu Konfirmasi Penerimaan --}}
                @if ($isBuyer)
                    <button type="button" onclick="document.getElementById('modal-konfirmasi').showModal()" class="w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Barang sudah diterima
                    </button>
                    <a href="{{ route('lapor.create', ['tipe' => 'order', 'id' => $order->id]) }}" class="mt-1 block w-full py-2 text-center text-sm font-semibold text-red-500">
                        Ada masalah? Laporkan
                    </a>
                @else
                    <button type="button" disabled class="w-full cursor-not-allowed rounded-full bg-slate-100 py-3.5 text-center font-semibold text-slate-400">
                        Menunggu konfirmasi pembeli
                    </button>
                    <a href="{{ $chatUrl }}" class="mt-1 block w-full py-2 text-center text-sm font-semibold text-slate-500">
                        Chat pembeli
                    </a>
                @endif
            @elseif ($order->status === \App\Models\Order::STATUS_SELESAI)
                @php
                    $userReview = $order->reviews->firstWhere('reviewer_id', auth()->id());
                @endphp
                {{-- Status Selesai --}}
                @if ($isBuyer)
                    @if ($userReview)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                            <p class="text-xs font-semibold text-emerald-800">Ulasanmu telah terkirim</p>
                            <p class="mt-1 text-sm font-bold text-amber-500">★ {{ $userReview->rating }}/5</p>
                            @if ($userReview->body)
                                <p class="mt-1 text-xs text-slate-600 italic">"{{ $userReview->body }}"</p>
                            @endif
                        </div>
                    @else
                        <a href="{{ route('ulasan.create', $order->order_number) }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                            Beri ulasan
                        </a>
                    @endif
                    <a href="{{ route('cari') }}" class="mt-1 block w-full py-2 text-center text-sm font-semibold text-slate-500">
                        Cari barang lain
                    </a>
                @else
                    <a href="{{ route('barang.create') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Pasang barang lain
                    </a>
                    <a href="{{ route('barang.index') }}" class="mt-1 block w-full py-2 text-center text-sm font-semibold text-slate-500">
                        Lihat barang saya
                    </a>
                @endif
            @elseif ($order->status === \App\Models\Order::STATUS_DIBATALKAN)
                {{-- Status Dibatalkan --}}
                @if ($isBuyer)
                    <a href="{{ route('cari') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Cari barang lain
                    </a>
                @else
                    <a href="{{ route('barang.index') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white shadow hover:bg-slate-800 transition">
                        Ke barang saya
                    </a>
                @endif
                <a href="{{ route('aktivitas') }}" class="mt-1 block w-full py-2 text-center text-sm font-semibold text-slate-500">
                    Kembali ke Aktivitas
                </a>
            @endif
        </div>

        {{-- =================== MODAL DIALOGS =================== --}}

        {{-- Modal Batal Pesanan --}}
        <dialog id="modal-batal" class="w-[min(92vw,360px)] rounded-3xl p-0 shadow-2xl backdrop:bg-black/50">
            <form method="POST" action="{{ route('order.batal', $order->order_number) }}" class="p-5 text-center">
                @csrf
                <div class="mx-auto grid size-12 place-items-center rounded-full bg-red-100 text-red-600">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>
                </div>
                <h3 class="mt-3 text-lg font-bold">Batalkan pesanan?</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Barang belum diserahkan, jadi {{ $rupiah($order->agreed_price) }} dikembalikan penuh ke rekening {{ $isBuyer ? 'asal' : 'pembeli' }} dalam 1 sampai 3 hari kerja.
                </p>

                <div class="mt-3 text-left">
                    <label for="cancel-reason" class="block text-xs font-semibold text-slate-600">Alasan pembatalan (opsional):</label>
                    <input type="text" id="cancel-reason" name="reason" placeholder="Misal: Salah pilih barang / berubah pikiran" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:border-ink outline-none">
                </div>

                <div class="mt-5 flex gap-2">
                    <button type="button" onclick="document.getElementById('modal-batal').close()" class="flex-1 rounded-full border border-slate-300 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Tidak
                    </button>
                    <button type="submit" class="flex-1 rounded-full bg-red-600 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition">
                        Batalkan
                    </button>
                </div>
            </form>
        </dialog>

        {{-- Modal Serah Terima Barang (Penjual) --}}
        <dialog id="modal-serah" class="w-[min(92vw,360px)] rounded-3xl p-0 shadow-2xl backdrop:bg-black/50">
            <form method="POST" action="{{ route('order.diserahkan', $order->order_number) }}" class="p-5 text-center">
                @csrf
                <div class="mx-auto grid size-12 place-items-center rounded-full bg-loop-soft text-loop-dark">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <h3 class="mt-3 text-lg font-bold">Barang sudah diserahkan?</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Pembeli punya waktu 3 hari (72 jam) untuk cek dan konfirmasi. Tanpa komplain, dana otomatis cair ke saldomu.
                </p>
                <div class="mt-5 flex gap-2">
                    <button type="button" onclick="document.getElementById('modal-serah').close()" class="flex-1 rounded-full border border-slate-300 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Belum
                    </button>
                    <button type="submit" class="flex-1 rounded-full bg-ink py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Sudah
                    </button>
                </div>
            </form>
        </dialog>

        {{-- Modal Konfirmasi Penerimaan Barang (Pembeli) --}}
        <dialog id="modal-konfirmasi" class="w-[min(92vw,360px)] rounded-3xl p-0 shadow-2xl backdrop:bg-black/50">
            <form method="POST" action="{{ route('order.terima', $order->order_number) }}" class="p-6 text-center">
                @csrf
                <div class="mx-auto grid size-14 place-items-center rounded-full bg-brand-50 text-brand-600">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <h3 class="mt-3 text-xl font-bold">Barang sudah kamu terima?</h3>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $rupiah($order->agreed_price) }} akan diteruskan ke {{ $order->seller?->name ?? 'penjual' }}. Setelah dikonfirmasi, transaksi selesai dan tidak dapat dibatalkan.
                </p>
                <div class="mt-5 space-y-2">
                    <button type="submit" class="w-full rounded-full bg-ink py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        Ya, sudah diterima
                    </button>
                    <button type="button" onclick="document.getElementById('modal-konfirmasi').close()" class="w-full py-2 text-xs font-semibold text-slate-500">
                        Cek lagi dulu
                    </button>
                </div>
            </form>
        </dialog>

        {{-- Modal Rating / Ulasan Selesai --}}
        <dialog id="modal-rating" class="w-[min(92vw,360px)] rounded-3xl p-0 shadow-2xl backdrop:bg-black/50">
            <div class="p-6 text-center">
                <div class="mx-auto grid size-14 place-items-center rounded-full bg-amber-50 text-amber-500">
                    <svg class="size-7" viewBox="0 0 24 24" fill="currentColor"><path d="m12 17.27 4.15 2.51c.76.46 1.69-.22 1.49-1.08l-1.1-4.72 3.67-3.18c.67-.58.31-1.68-.57-1.75l-4.83-.41-1.89-4.46c-.34-.81-1.5-.81-1.84 0L9.19 8.63l-4.83.41c-.88.07-1.24 1.17-.57 1.75l3.67 3.18-1.1 4.72c-.2.86.73 1.54 1.49 1.08l4.15-2.5Z"/></svg>
                </div>
                <h3 class="mt-3 text-xl font-bold">Pesanan selesai!</h3>
                <p class="mt-1 text-xs text-slate-500">Dana telah diteruskan ke penjual. Streak +1!</p>
                <p class="mt-4 text-xs font-semibold text-slate-700">Beri rating untuk {{ $order->seller?->name ?? 'penjual' }}</p>
                <div class="mt-2 flex justify-center gap-1.5 text-3xl text-amber-400">
                    <a href="{{ route('ulasan.create', ['orderNumber' => $order->order_number, 'rating' => 1]) }}" title="1 bintang" class="hover:scale-110 transition">★</a>
                    <a href="{{ route('ulasan.create', ['orderNumber' => $order->order_number, 'rating' => 2]) }}" title="2 bintang" class="hover:scale-110 transition">★</a>
                    <a href="{{ route('ulasan.create', ['orderNumber' => $order->order_number, 'rating' => 3]) }}" title="3 bintang" class="hover:scale-110 transition">★</a>
                    <a href="{{ route('ulasan.create', ['orderNumber' => $order->order_number, 'rating' => 4]) }}" title="4 bintang" class="hover:scale-110 transition">★</a>
                    <a href="{{ route('ulasan.create', ['orderNumber' => $order->order_number, 'rating' => 5]) }}" title="5 bintang" class="hover:scale-110 transition">★</a>
                </div>
                <a href="{{ route('ulasan.create', $order->order_number) }}" class="mt-4 block w-full rounded-full bg-ink py-2.5 text-xs font-semibold text-white">
                    Tulis ulasan
                </a>
                <button type="button" onclick="document.getElementById('modal-rating').close()" class="mt-2 w-full py-2 text-xs font-semibold text-slate-500">
                    Nanti saja
                </button>
            </div>
        </dialog>
    </div>

    {{-- Script untuk Snap Payment (jika status menunggu_bayar) dan hitung mundur otomatis --}}
    @push('scripts')
        @if ($isBuyer && $order->status === \App\Models\Order::STATUS_MENUNGGU_BAYAR)
            <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
            <script>
                document.getElementById('btn-bayar-snap')?.addEventListener('click', async function () {
                    const btn = this;
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btn.textContent = 'Membuka pembayaran…';

                    try {
                        const res = await fetch("{{ route('order.snap_token', $order->order_number) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                        });
                        const data = await res.json().catch(() => ({}));

                        if (!res.ok || !data.token) {
                            btn.disabled = false;
                            btn.textContent = 'Bayar Sekarang';
                            alert(data.message || 'Pembayaran belum bisa dibuka. Coba lagi beberapa saat lagi.');
                            return;
                        }

                        if (typeof window.snap?.pay === 'function') {
                            window.snap.pay(data.token, {
                                onSuccess: function () {
                                    window.location.href = "{{ route('order.show', $order->order_number) }}";
                                },
                                onPending: function () {
                                    window.location.href = "{{ route('order.show', $order->order_number) }}";
                                },
                                onError: function () {
                                    window.location.href = "{{ route('order.show', $order->order_number) }}";
                                },
                                onClose: function () {
                                    btn.disabled = false;
                                    btn.textContent = 'Bayar Sekarang';
                                }
                            });
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                    } catch (e) {
                        btn.disabled = false;
                        btn.textContent = 'Bayar Sekarang';
                        alert('Gagal terhubung ke server.');
                    }
                });
            </script>
        @endif

        @if ($order->status === \App\Models\Order::STATUS_DISERAHKAN && $autoCompleteDiff > 0)
            <script>
                let s = {{ (int) $autoCompleteDiff }};
                const cdEl = document.getElementById('countdown-auto');
                if (cdEl) {
                    const tick = () => {
                        const p = (x) => String(x).padStart(2, '0');
                        cdEl.textContent = `${p(Math.floor(s / 3600))}:${p(Math.floor((s % 3600) / 60))}:${p(s % 60)}`;
                        if (s-- > 0) {
                            setTimeout(tick, 1000);
                        } else {
                            window.location.reload();
                        }
                    };
                    tick();
                }
            </script>
        @endif

        <script>
            document.querySelectorAll('dialog').forEach(dialog => {
                dialog.addEventListener('click', (e) => {
                    if (e.target === dialog) dialog.close();
                });
            });
        </script>
    @endpush
</x-app-layout>
