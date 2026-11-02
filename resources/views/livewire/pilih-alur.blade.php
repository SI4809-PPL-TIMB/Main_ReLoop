<div class="px-4 pb-44 pt-4">
    <p class="text-sm text-slate-500">Mau jual barang atau setor daur ulang?</p>
    <form method="POST" action="{{ route('kategori.pilih') }}" class="mt-5">
        @csrf
        <fieldset aria-describedby="keterangan-tipe">
            <legend class="text-sm font-semibold">Pilih jenis barang</legend>
            <div class="mt-3 grid grid-cols-2 gap-3">
                @foreach ($types as $value => $label)
                    @php($enabled = in_array($value, $available, true))
                    <label wire:key="tipe-{{ $value }}" class="relative {{ $enabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50' }}">
                        <input type="radio" name="tipe" value="{{ $value }}" wire:model.live="tipe" required @disabled(!$enabled)
                               class="peer sr-only" aria-describedby="deskripsi-{{ $value }}">
                        <span class="flex h-full flex-col items-center gap-2 rounded-2xl border border-slate-200 px-3 pb-4 pt-5 text-center peer-checked:border-ink peer-checked:bg-slate-50 peer-checked:ring-1 peer-checked:ring-ink peer-focus-visible:ring-2 peer-focus-visible:ring-ink">
                            <img src="{{ asset('assets/illust/categories/'.($value === 'bekas' ? 'perabot' : 'plastik').'.png') }}" class="mb-1 size-16 object-contain" width="64" height="64" alt="">
                            <span class="text-sm font-semibold">{{ $label }}</span>
                            <span id="deskripsi-{{ $value }}" class="text-xs leading-relaxed text-slate-500">{{ $value === 'bekas' ? 'Jual barang yang masih layak pakai.' : 'Setor material ke mitra pengepul.' }}</span>
                            @unless ($enabled)<span class="text-xs text-slate-500">Belum tersedia</span>@endunless
                        </span>
                    </label>
                @endforeach
            </div>
            <p id="keterangan-tipe" class="mt-4 text-xs leading-relaxed text-slate-500">Kategori detail dipilih di langkah berikutnya.</p>
        </fieldset>
        @error('tipe')<p role="alert" class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            <p aria-live="polite" class="mb-3 text-xs text-slate-500">{{ $canContinue ? $types[$tipe] : 'Pilih jenis barang untuk melanjutkan.' }}</p>
            <button type="submit" @disabled(!$canContinue) wire:loading.attr="disabled" class="w-full rounded-full bg-ink px-3 py-3.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                {{ !$canContinue ? 'Lanjutkan' : ($tipe === 'bekas' ? 'Lanjut ke Pasang Barang' : 'Lanjut ke Mitra Pengepul') }}
            </button>
        </div>
    </form>
</div>
