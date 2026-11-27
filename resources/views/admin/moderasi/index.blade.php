<x-app-layout>
    <x-slot name="title">Reloop Admin · Moderasi & Sengketa</x-slot>

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white pb-10 shadow-sm">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.mitra.index') }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200 hover:bg-slate-50 transition">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </a>
                <h1 class="text-xl font-bold">Moderasi & Laporan</h1>
            </div>
            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700">Admin</span>
        </header>

        <main class="space-y-4 px-4 pt-4">
            @if (session('success'))
                <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('info'))
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-700">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Navigasi Filter Antrean --}}
            <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4">
                <a href="{{ route('admin.moderasi.index') }}" class="shrink-0 rounded-full border px-4 py-1.5 text-xs font-semibold {{ empty($status) ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600' }}">
                    Semua ({{ $counts['all'] }})
                </a>
                <a href="{{ route('admin.moderasi.index', ['status' => 'pending']) }}" class="shrink-0 rounded-full border px-4 py-1.5 text-xs font-semibold {{ $status === 'pending' ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600' }}">
                    Menunggu ({{ $counts['pending'] }})
                </a>
                <a href="{{ route('admin.moderasi.index', ['status' => 'sengketa']) }}" class="shrink-0 rounded-full border px-4 py-1.5 text-xs font-semibold {{ $status === 'sengketa' ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600' }}">
                    Sengketa Pesanan ({{ $counts['sengketa'] }})
                </a>
                <a href="{{ route('admin.moderasi.index', ['status' => 'resolved']) }}" class="shrink-0 rounded-full border px-4 py-1.5 text-xs font-semibold {{ $status === 'resolved' ? 'border-ink bg-ink text-white' : 'border-slate-200 text-slate-600' }}">
                    Selesai ({{ $counts['resolved'] }})
                </a>
            </nav>

            {{-- Daftar Antrean Laporan --}}
            @if ($reports->isEmpty())
                <div class="py-16 text-center text-slate-400">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-slate-100 text-2xl">✨</div>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Antrean Bersih</p>
                    <p class="mt-1 text-xs text-slate-500">Tidak ada laporan dengan filter status ini.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($reports as $r)
                        @php
                            $target = $r->target;
                            $isOrder = $r->reportable_type === 'order';
                            $cardBorder = $isOrder ? 'border-amber-200 bg-amber-50/20' : 'border-slate-200 bg-white';
                        @endphp
                        <div class="rounded-2xl border {{ $cardBorder }} p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $isOrder ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $isOrder ? 'Sengketa Pesanan' : 'Lapor ' . ucfirst($r->reportable_type) }}
                                </span>
                                <span class="text-[11px] text-slate-400">{{ $r->created_at->diffForHumans() }}</span>
                            </div>

                            <div>
                                @if ($r->reportable_type === 'listing' && $target)
                                    <p class="font-bold text-sm text-ink">Barang: {{ $target->title }}</p>
                                    <p class="text-xs text-slate-500">Pemilik: {{ $target->user?->name ?? 'Pengguna' }} · Status: {{ $target->status }}</p>
                                @elseif ($r->reportable_type === 'user' && $target)
                                    <p class="font-bold text-sm text-ink">Akun: {{ $target->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $target->email }} · Status: {{ $target->is_active ? 'Aktif' : 'Dibekukan' }}</p>
                                @elseif ($r->reportable_type === 'order' && $target)
                                    <p class="font-bold text-sm text-ink">#{{ $target->order_number }} · Rp {{ number_format($target->agreed_price, 0, ',', '.') }}</p>
                                    <p class="text-xs text-slate-500">Status pesanan: <b>{{ $target->status }}</b></p>
                                    <p class="text-xs text-slate-500">Pembeli: {{ $target->buyer?->name }} · Penjual: {{ $target->seller?->name }}</p>
                                @else
                                    <p class="font-bold text-sm text-ink text-slate-400">Entitas telah dihapus (#{{ $r->reportable_id }})</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-0.5">Pelapor: <span class="font-semibold text-slate-700">{{ $r->reporter?->name ?? 'Pengguna' }}</span></p>
                            </div>

                            <div class="rounded-xl bg-white p-3 border border-slate-200 text-xs space-y-1">
                                <p class="font-semibold text-red-600">Alasan: {{ ucwords(str_replace('_', ' ', $r->reason)) }}</p>
                                <p class="text-slate-600">"{{ $r->description }}"</p>
                            </div>

                            {{-- Aksi Sesuai Status dan Tipe --}}
                            @if ($r->status === 'pending')
                                @if ($r->reportable_type === 'order')
                                    {{-- Putusan Sengketa Pesanan (Cairkan vs Refund) --}}
                                    <div class="pt-1">
                                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Putusan Sengketa (Escrow):</p>
                                        <div class="flex gap-2">
                                            <form action="{{ route('admin.moderasi.resolve_dispute', $r) }}" method="POST" class="flex-1">
                                                @csrf
                                                <input type="hidden" name="decision" value="payout_seller">
                                                <button type="submit" onclick="return confirm('Cairkan dana pesanan ini ke penjual?')" class="w-full rounded-xl bg-ink py-2 text-xs font-semibold text-white hover:bg-slate-800 transition">
                                                    Cairkan ke Penjual
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.moderasi.resolve_dispute', $r) }}" method="POST" class="flex-1">
                                                @csrf
                                                <input type="hidden" name="decision" value="refund_buyer">
                                                <button type="submit" onclick="return confirm('Refund / kembalikan dana pesanan ini ke pembeli?')" class="w-full rounded-xl bg-red-600 py-2 text-xs font-semibold text-white hover:bg-red-700 transition">
                                                    Refund ke Pembeli
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @elseif ($r->reportable_type === 'listing')
                                    <div class="flex gap-2 pt-1">
                                        <form action="{{ route('admin.moderasi.dismiss', $r) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Abaikan
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.moderasi.deactive_listing', $r) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Nonaktifkan barang ini agar tidak tayang?')" class="w-full rounded-xl bg-red-600 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                                Nonaktifkan Barang
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($r->reportable_type === 'user')
                                    <div class="flex gap-2 pt-1">
                                        <form action="{{ route('admin.moderasi.dismiss', $r) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Abaikan
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.moderasi.deactive_user', $r) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Bekukan / nonaktifkan akun pengguna ini?')" class="w-full rounded-xl bg-red-600 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                                Bekukan Akun
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @else
                                <div class="rounded-xl bg-slate-100 p-2 text-xs text-slate-500">
                                    <p>Telah ditindak: <b class="text-ink">{{ ucwords(str_replace('_', ' ', $r->action_taken)) }}</b></p>
                                    @if ($r->admin_notes)
                                        <p class="mt-0.5 text-slate-600">{{ $r->admin_notes }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </main>
    </div>
</x-app-layout>
