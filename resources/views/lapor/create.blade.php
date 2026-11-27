<x-app-layout>
    <x-slot name="title">Reloop · Laporkan Masalah</x-slot>

    @php
        $targetTitle = '';
        $targetSub = '';
        $typeLabel = match($type) {
            'listing' => 'Barang',
            'user'    => 'Pengguna',
            'order'   => 'Pesanan',
            default   => 'Laporan'
        };

        if ($type === 'listing') {
            $targetTitle = $target->title;
            $targetSub = 'Penjual: ' . ($target->user?->name ?? 'Pengguna');
        } elseif ($type === 'user') {
            $targetTitle = $target->name;
            $targetSub = 'Akun Pengguna (' . $target->email . ')';
        } elseif ($type === 'order') {
            $targetTitle = '#' . $target->order_number . ' · Rp ' . number_format($target->agreed_price, 0, ',', '.');
            $targetSub = 'Barang: ' . ($target->listing?->title ?? 'Pesanan');
        }
    @endphp

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white p-4 pb-28 shadow-sm">
        <header class="flex items-center gap-3 border-b border-slate-200 pb-4">
            <a href="javascript:history.back()" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200 hover:bg-slate-50 transition">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="text-xl font-bold">Laporkan Masalah</h1>
        </header>

        <main class="mt-4 space-y-4">
            {{-- Info Entitas yang Dilaporkan --}}
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-red-100 text-red-600 font-bold text-lg">
                    ⚠
                </div>
                <div class="min-w-0 flex-1">
                    <span class="inline-block rounded-md bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">{{ $typeLabel }}</span>
                    <p class="truncate font-semibold text-sm mt-0.5 text-ink">{{ $targetTitle }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $targetSub }}</p>
                </div>
            </div>

            @if (session('error'))
                <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Formulir Pelaporan --}}
            <form action="{{ route('lapor.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="reportable_type" value="{{ $type }}">
                <input type="hidden" name="reportable_id" value="{{ $target->id }}">

                <div>
                    <label class="block text-sm font-bold text-ink">Alasan Pelaporan *</label>
                    <p class="text-xs text-slate-500 mt-0.5">Pilih alasan utama yang paling sesuai dengan kendala</p>

                    <div class="mt-2.5 space-y-2">
                        @php
                            $alasanOptions = [
                                'penipuan'            => 'Penipuan / Barang tidak sesuai',
                                'barang_terlarang'    => 'Barang terlarang / Ilegal / Berbahaya',
                                'perilaku_buruk'      => 'Pelecehan / Perilaku tidak sopan',
                                'sengketa_transaksi'  => 'Sengketa transaksi / Masalah dana',
                                'lainnya'             => 'Lainnya',
                            ];
                        @endphp

                        @foreach ($alasanOptions as $val => $label)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="reason" value="{{ $val }}" class="size-4 text-ink focus:ring-ink" {{ $loop->first ? 'checked' : '' }}>
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-bold text-ink">
                        Keterangan Tambahan *
                    </label>
                    <textarea id="description" name="description" rows="4" required maxlength="1000"
                        placeholder="Jelaskan detail kendala atau kronologi kejadian untuk membantu proses peninjauan oleh admin..."
                        class="mt-1.5 w-full rounded-2xl border border-slate-200 p-3.5 text-sm outline-none focus:border-ink focus:ring-1 focus:ring-ink">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-start gap-2.5 rounded-2xl bg-amber-50 border border-amber-200 p-3.5 text-xs text-amber-900">
                    <svg class="size-4 shrink-0 text-amber-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                    <p>Laporanmu bersifat rahasia dan akan ditinjau langsung oleh tim moderasi Reloop. Pihak terlapor tidak akan mengetahui identitasmu.</p>
                </div>

                <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-2px_8px_rgba(0,0,0,0.03)]">
                    <button type="submit" class="w-full rounded-full bg-red-600 py-3.5 text-center font-semibold text-white shadow-sm hover:bg-red-700 active:scale-95 transition">
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </main>
    </div>
</x-app-layout>
