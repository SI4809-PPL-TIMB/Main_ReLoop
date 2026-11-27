<x-app-layout>
    <x-slot name="title">Reloop · Riwayat Laporan</x-slot>

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white p-4 pb-20 shadow-sm">
        <header class="flex items-center gap-3 border-b border-slate-200 pb-4">
            <a href="javascript:history.back()" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200 hover:bg-slate-50 transition">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="text-xl font-bold">Laporan Saya</h1>
        </header>

        <main class="mt-4 space-y-3">
            @if (session('success'))
                <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($reports->isEmpty())
                <div class="py-16 text-center text-slate-400">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-slate-100 text-2xl">🛡️</div>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada laporan</p>
                    <p class="mt-1 text-xs text-slate-500">Platform aman dan tertib. Kamu belum pernah melaporkan pelanggaran.</p>
                </div>
            @else
                @foreach ($reports as $r)
                    @php
                        $badgeStatus = match($r->status) {
                            'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'rejected' => 'bg-slate-100 text-slate-600 border-slate-200',
                            default    => 'bg-slate-100 text-slate-600 border-slate-200',
                        };
                        $statusText = match($r->status) {
                            'pending'  => 'Sedang Ditinjau',
                            'resolved' => 'Selesai Ditindak',
                            'rejected' => 'Ditolak / Selesai',
                            default    => ucfirst($r->status),
                        };
                    @endphp
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                Lapor {{ ucfirst($r->reportable_type) }}
                            </span>
                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $badgeStatus }}">
                                {{ $statusText }}
                            </span>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-ink">Alasan: {{ ucwords(str_replace('_', ' ', $r->reason)) }}</p>
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">"{{ $r->description }}"</p>
                        </div>

                        @if ($r->admin_notes)
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-2.5 text-xs text-slate-600">
                                <span class="font-semibold text-ink">Catatan Admin:</span> {{ $r->admin_notes }}
                            </div>
                        @endif

                        <p class="text-[10px] text-slate-400 pt-1">
                            Diajukan pada {{ $r->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                @endforeach
            @endif
        </main>
    </div>
</x-app-layout>
