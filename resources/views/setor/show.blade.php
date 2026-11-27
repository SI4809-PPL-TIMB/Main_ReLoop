@php
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $mitra = $order->mitra;
    $cat = $order->category;
    $slug = $cat?->slug;
    $hasImg = $slug && file_exists(public_path('assets/illust/categories/'.$slug.'.png'));
    $user = auth()->user();
    $isCustomer = $order->isCustomer($user);

    $step = match($order->status) {
        \App\Models\OrderSetor::STATUS_MENUNGGU => 1,
        \App\Models\OrderSetor::STATUS_DITERIMA => 2,
        \App\Models\OrderSetor::STATUS_DITIMBANG => 3,
        \App\Models\OrderSetor::STATUS_SELESAI => 4,
        default => 0,
    };
@endphp

@component('layouts.app', ['title' => 'Reloop · Detail Setoran #'.$order->order_number])
    <x-top-bar title="Detail Setoran" :back="route('aktivitas', ['tab' => $order->isSelesai() || $order->isDibatalkan() ? 'riwayat' : 'berjalan', 'f' => 'setor'])" />

    <main class="space-y-4 px-4 pb-36 pt-4">
        <!-- Subheader Nomor Order -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
            <div>
                <p class="text-xs text-slate-500">Nomor Pesanan</p>
                <p class="font-mono text-sm font-bold text-ink">#{{ $order->order_number }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500">Waktu Order</p>
                <p class="text-xs font-semibold text-slate-700">{{ $order->created_at->translatedFormat('d M Y, H.i') }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-3.5 text-xs font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($order->isDibatalkan())
            <!-- Banner Dibatalkan -->
            <section class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <svg class="size-9 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>
                </svg>
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Dibatalkan</p>
                    <p class="text-base font-bold text-ink">Setoran Tidak Dilanjutkan</p>
                    <p class="mt-0.5 text-xs text-slate-600">
                        {{ $order->cancel_reason ?: 'Order dibatalkan sebelum diproses oleh mitra pengepul.' }}
                    </p>
                    @if ($order->cancelled_at)
                        <p class="mt-1 text-[11px] text-slate-400">Dibatalkan pada {{ $order->cancelled_at->translatedFormat('d M Y, H.i') }}</p>
                    @endif
                </div>
            </section>
        @else
            <!-- Banner Status Normal -->
            @if ($step === 1)
                <section class="flex items-center gap-3.5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    <svg class="size-7 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold">Menunggu Konfirmasi Pengepul</p>
                        <p class="mt-0.5 text-xs text-amber-800">Mitra pengepul akan meninjau jadwal penjemputan dan kuota setoran.</p>
                    </div>
                </section>
            @elseif ($step === 2)
                <section class="flex items-center gap-3.5 rounded-2xl border border-loop bg-loop-soft p-4">
                    <svg class="size-7 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold">Order Diterima Mitra</p>
                        <p class="mt-0.5 text-xs text-slate-600">Pengepul telah menerima pesanan dan siap melakukan {{ $order->metode === 'jemput' ? 'penjemputan' : 'penerimaan di lapak' }}.</p>
                    </div>
                </section>
            @elseif ($step === 3)
                <section class="flex items-center gap-3.5 rounded-2xl border border-loop bg-loop-soft p-4">
                    <svg class="size-7 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold">Sedang Ditimbang &amp; Dihitung</p>
                        <p class="mt-0.5 text-xs text-slate-600">Sampah sedang ditimbang di hadapan customer untuk menghitung pembayaran final.</p>
                    </div>
                </section>
            @elseif ($step === 4)
                <section class="flex items-center gap-3.5 rounded-2xl border border-loop bg-loop-soft p-4">
                    <svg class="size-7 shrink-0 text-loop-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="m5 12 5 5 9-10"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold">Setoran Selesai!</p>
                        <p class="mt-0.5 text-xs text-slate-600">Pembayaran telah diterima penuh. Terima kasih sudah mendaur ulang!</p>
                    </div>
                </section>
            @endif

            <!-- Linimasa Status -->
            <section class="rounded-2xl border border-slate-200 p-4">
                <h2 class="text-sm font-bold">Status Progres</h2>
                <ol class="mt-3.5 space-y-4 text-sm">
                    <!-- Step 1 -->
                    <li class="group flex gap-3">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step > 1 ? 'border-ink bg-ink text-white' : ($step === 1 ? 'border-ink' : 'border-slate-200 text-slate-400') }}">
                            @if ($step > 1)
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            @else
                                <span class="size-2 rounded-full bg-ink"></span>
                            @endif
                        </span>
                        <div>
                            <p class="font-semibold">Order Diteruskan ke Mitra</p>
                            <p class="text-xs text-slate-500">{{ $order->created_at->translatedFormat('d M, H.i') }} · Status awal: Menunggu</p>
                        </div>
                    </li>

                    <!-- Step 2 -->
                    <li class="group flex gap-3 {{ $step < 2 ? 'text-slate-400' : '' }}">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step > 2 ? 'border-ink bg-ink text-white' : ($step === 2 ? 'border-ink' : 'border-slate-200') }}">
                            @if ($step > 2)
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            @elseif ($step === 2)
                                <span class="size-2 rounded-full bg-ink"></span>
                            @endif
                        </span>
                        <div>
                            <p class="font-semibold">Konfirmasi Mitra</p>
                            @if ($step === 1)
                                <p class="text-xs text-amber-600">Menunggu mitra menerima pesanan</p>
                            @elseif ($step >= 2)
                                <p class="text-xs text-slate-500">Pesanan telah dikonfirmasi mitra</p>
                            @endif
                        </div>
                    </li>

                    <!-- Step 3 -->
                    <li class="group flex gap-3 {{ $step < 3 ? 'text-slate-400' : '' }}">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step > 3 ? 'border-ink bg-ink text-white' : ($step === 3 ? 'border-ink' : 'border-slate-200') }}">
                            @if ($step > 3)
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            @elseif ($step === 3)
                                <span class="size-2 rounded-full bg-ink"></span>
                            @endif
                        </span>
                        <div>
                            <p class="font-semibold">Penimbangan &amp; Pembayaran</p>
                            @if ($step < 3)
                                <p class="text-xs">Ditimbang saat penjemputan / di lapak</p>
                            @else
                                <p class="text-xs text-slate-500">
                                    {{ $order->berat_asli ?: $order->perkiraan_berat }} kg × {{ $rupiah($order->harga_per_kg) }}
                                </p>
                            @endif
                        </div>
                    </li>

                    <!-- Step 4 -->
                    <li class="group flex gap-3 {{ $step < 4 ? 'text-slate-400' : '' }}">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full border-2 {{ $step === 4 ? 'border-ink bg-ink text-white' : 'border-slate-200' }}">
                            @if ($step === 4)
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            @endif
                        </span>
                        <div>
                            <p class="font-semibold">Selesai</p>
                            <p class="text-xs {{ $step === 4 ? 'text-slate-500' : '' }}">
                                {{ $step === 4 ? 'Transaksi tuntas, uang diterima customer' : 'Uang diterima penuh, streak bertambah' }}
                            </p>
                        </div>
                    </li>
                </ol>
            </section>
        @endif

        <!-- Rincian Sampah & Nilai Transaksi -->
        <section class="space-y-3 rounded-2xl border border-slate-200 p-4 text-sm">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                @if ($hasImg)
                    <img src="{{ asset('assets/illust/categories/'.$slug.'.png') }}" class="size-11 shrink-0 rounded-lg bg-slate-50 p-1 object-contain" width="44" height="44" alt="">
                @else
                    <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-loop-soft text-lg">♻️</span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-base font-bold">{{ $cat?->name ?? 'Daur Ulang' }}</p>
                    <a href="{{ route('pengepul.show', $mitra) }}" class="text-xs text-brand-700 hover:underline">
                        Mitra: {{ $mitra->nama_usaha }}
                    </a>
                </div>
            </div>

            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Harga Terkunci</dt>
                    <dd class="font-semibold text-brand-700">{{ $rupiah($order->harga_per_kg) }} / kg</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Perkiraan Berat</dt>
                    <dd class="font-semibold">{{ $order->perkiraan_berat }} kg</dd>
                </div>
                @if ($order->berat_asli)
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Berat Timbangan Asli</dt>
                        <dd class="font-bold text-ink">{{ $order->berat_asli }} kg</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-500">Metode Penyerahan</dt>
                    <dd class="font-semibold">{{ $order->metodeLabel() }}</dd>
                </div>
                <div class="flex justify-between border-t border-dashed border-slate-200 pt-2 text-base font-bold">
                    <dt>{{ $order->total_final ? 'Total Pembayaran' : 'Estimasi Pendapatan' }}</dt>
                    <dd class="text-brand-700">{{ $rupiah($order->total_final ?: $order->total_estimasi) }}</dd>
                </div>
            </dl>

            <p class="rounded-xl bg-[#f7f8f4] p-2.5 text-xs text-slate-600">
                * Nilai final mengikuti hasil timbangan asli saat serah terima. Bebas komisi platform.
            </p>
        </section>

        <!-- Detail Lokasi -->
        <section class="space-y-1.5 rounded-2xl border border-slate-200 p-4 text-sm">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-400">
                {{ $order->metode === 'jemput' ? 'Lokasi Penjemputan' : 'Lokasi Lapak Pengepul' }}
            </h3>
            @if ($order->metode === 'jemput')
                <p class="font-medium text-ink">{{ $order->alamat_jemput ?: 'Sesuai profil pemesan' }}</p>
                @if ($order->patokan)
                    <p class="text-xs text-slate-500"><b class="text-slate-700">Patokan:</b> {{ $order->patokan }}</p>
                @endif
            @else
                <p class="font-medium text-ink">{{ $mitra->alamat }}</p>
                <p class="text-xs text-slate-500">{{ $mitra->kelurahan?->breadcrumb() }}</p>
            @endif

            @if ($order->catatan)
                <div class="mt-2 border-t border-slate-100 pt-2 text-xs">
                    <span class="font-semibold text-slate-600">Catatan:</span> {{ $order->catatan }}
                </div>
            @endif
        </section>
    </main>

    <!-- Bottom Actions -->
    <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
        @if ($order->canBeCancelled() && $isCustomer)
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('dlg-batal').showModal()"
                        class="flex-1 rounded-full border border-slate-300 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Batalkan Order
                </button>
                <a href="{{ route('pengepul.show', $mitra) }}"
                   class="flex-1 rounded-full bg-ink py-3 text-center text-sm font-semibold text-white">
                    Lihat Lapak
                </a>
            </div>
        @elseif ($order->isDibatalkan())
            <a href="{{ route('setor.create', $mitra) }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white">
                Buat Order Baru
            </a>
        @else
            <a href="{{ route('aktivitas', ['tab' => 'berjalan', 'f' => 'setor']) }}"
               class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white">
                Kembali ke Aktivitas
            </a>
        @endif
    </div>

    <!-- Modal Dialog Batal Order -->
    @if ($order->canBeCancelled() && $isCustomer)
        <dialog id="dlg-batal" class="w-[min(92vw,360px)] rounded-3xl p-0">
            <form method="POST" action="{{ route('setor.batal', $order->order_number) }}" class="p-6 text-center">
                @csrf
                <h2 class="text-lg font-bold">Batalkan order setoran?</h2>
                <p class="mt-1 text-sm text-slate-500">Order yang dibatalkan tidak akan dilanjutkan oleh pengepul.</p>
                
                <div class="mt-4 text-left">
                    <label class="block text-xs font-semibold text-slate-600">Alasan pembatalan (opsional):</label>
                    <input type="text" name="alasan" placeholder="Misal: Salah pilih jenis sampah"
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs outline-none focus:border-ink">
                </div>

                <div class="mt-5 grid gap-2">
                    <button type="submit" class="rounded-full bg-red-600 py-3 font-semibold text-white transition-colors hover:bg-red-700">
                        Ya, Batalkan
                    </button>
                    <button type="button" onclick="document.getElementById('dlg-batal').close()"
                            class="rounded-full border border-slate-300 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                        Kembali
                    </button>
                </div>
            </form>
        </dialog>
    @endif
@endcomponent
