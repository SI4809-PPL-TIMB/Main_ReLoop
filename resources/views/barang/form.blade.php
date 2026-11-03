@php
    $ubah = $listing->exists;
    $err = fn ($k) => $errors->has($k) ? '<p data-err class="mt-2 text-sm text-red-600">'.e($errors->first($k)).'</p>' : '';
    $nilai = fn ($k) => old($k, $listing->$k);
    $inputKelas = 'mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-ink';
@endphp
<x-app-layout>
    <x-slot name="title">Reloop · {{ $ubah ? 'Ubah Barang' : 'Pasang Barang' }}</x-slot>

    <div class="sticky top-0 z-20 bg-white">
        <header class="flex items-center gap-3 px-4 pb-3 pt-2">
            <a href="{{ route('barang.index') }}" id="back" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 class="min-w-0 flex-1 truncate text-center text-xl font-bold leading-tight">{{ $ubah ? 'Ubah Barang' : 'Pasang Barang' }}</h1>
            <span class="size-10 shrink-0" aria-hidden="true"></span>
        </header>
        <div id="bar" class="flex items-center gap-3 border-b border-slate-100 px-4 pb-3" role="progressbar" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1" aria-label="Langkah pengisian">
            <span class="h-1 flex-1 overflow-hidden rounded-full bg-slate-200"><i id="bar-isi" class="block h-full w-1/4 rounded-full bg-ink transition-[width] duration-300"></i></span>
            <span id="bar-teks" class="text-xs font-semibold text-brand-700">1/4</span>
        </div>
    </div>

    <form id="form-barang" method="POST" enctype="multipart/form-data" novalidate
          action="{{ $ubah ? route('barang.update', $listing) : route('barang.store') }}" class="space-y-5 px-4 pb-32 pt-4">
        @csrf
        @if ($ubah) @method('PUT') @endif

        {{-- 1. Foto & kategori --}}
        <section data-step="1" class="space-y-5">
            <div><h2 class="text-xl font-bold">Foto &amp; kategori</h2><p class="mt-0.5 text-sm text-slate-500">Apa yang mau kamu jual?</p></div>

            <fieldset>
                <legend class="text-sm font-semibold">Foto * <span class="font-normal text-slate-400">(min. 1, maks. {{ \App\Models\Listing::MAKS_FOTO }})</span></legend>
                <div id="fotos" class="mt-2 grid grid-cols-4 gap-2" data-maks="{{ \App\Models\Listing::MAKS_FOTO }}">
                    @foreach ($listing->photos ?? [] as $foto)
                        <label data-lama class="relative aspect-square cursor-pointer overflow-hidden rounded-xl bg-slate-100 has-[:checked]:opacity-30">
                            <img src="{{ $foto->url }}" alt="" class="size-full object-cover">
                            <input type="checkbox" name="remove_photos[]" value="{{ $foto->id }}" class="peer sr-only">
                            <span class="absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-ink/80 text-white" aria-label="Hapus foto"><svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></span>
                        </label>
                    @endforeach
                    <button type="button" id="foto-add" class="grid aspect-square place-items-center rounded-xl border border-dashed border-slate-300 text-slate-500">
                        <span class="text-center text-[11px] font-semibold"><svg class="mx-auto size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Tambah</span>
                    </button>
                </div>
                <input id="foto-input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" tabindex="-1">
                <p class="mt-2 text-xs text-slate-500">Foto otomatis dikompres saat diunggah.</p>
                {!! $err('photos') !!}@foreach ($errors->get('photos.*') as $m){!! '<p data-err class="mt-2 text-sm text-red-600">'.e($m[0]).'</p>' !!}@break @endforeach
            </fieldset>

            <fieldset>
                <legend class="text-sm font-semibold">Kategori *</legend>
                <div class="mt-2 grid grid-cols-4 gap-2">
                    @foreach ($kategori as $k)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="category_id" value="{{ $k->id }}" class="peer sr-only" @checked((int) $nilai('category_id') === $k->id)>
                            <span class="flex h-full flex-col items-center gap-1 rounded-2xl border border-slate-200 px-1 pb-2 pt-3 text-center text-xs font-medium leading-tight peer-checked:border-ink peer-checked:ring-1 peer-checked:ring-ink">
                                @if (file_exists(public_path('assets/illust/categories/'.$k->slug.'.png')))
                                    <img src="{{ asset('assets/illust/categories/'.$k->slug.'.png') }}" class="size-10 object-contain" alt="">
                                @else
                                    <span class="grid size-10 grid-cols-2 gap-1 p-1" aria-hidden="true"><i class="rounded-md bg-loop"></i><i class="rounded-md bg-teal-600"></i><i class="rounded-md bg-teal-600"></i><i class="rotate-45 scale-75 rounded-sm bg-loop"></i></span>
                                @endif{{ $k->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
                {!! $err('category_id') !!}
                <p class="mt-4 text-xs text-slate-500">Mau buang botol, kardus, atau besi? <a href="#" class="font-semibold text-ink underline">Setor ke pengepul</a></p>
            </fieldset>
        </section>

        {{-- 2. Info barang --}}
        <section data-step="2" class="hidden space-y-5">
            <div><h2 class="text-xl font-bold">Info barang</h2><p class="mt-0.5 text-sm text-slate-500">Judul dan harga yang jelas bikin cepat laku.</p></div>
            <label class="block">
                <span class="text-sm font-semibold">Judul *</span>
                <input name="title" value="{{ $nilai('title') }}" maxlength="200" placeholder="mis. Kursi Kayu Jati Ukir" class="{{ $inputKelas }}">
                {!! $err('title') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">Deskripsi</span>
                <textarea name="description" rows="3" maxlength="2000" placeholder="Ceritakan barangnya: asal, alasan dijual, cara ambil…" class="{{ $inputKelas }}">{{ $nilai('description') }}</textarea>
                {!! $err('description') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">Harga *</span>
                <span class="mt-2 flex items-center rounded-xl border border-slate-200 px-4 focus-within:border-ink">
                    <span class="font-semibold text-slate-400">Rp</span>
                    <input name="price" inputmode="numeric" value="{{ $nilai('price') ? number_format((int) $nilai('price'), 0, ',', '.') : '' }}" placeholder="350.000" class="w-full bg-transparent px-2 py-3 font-semibold outline-none">
                </span>
                {!! $err('price') !!}
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                <span><span class="block text-sm font-semibold">Bisa nego</span><span class="block text-xs text-slate-500">Pembeli boleh menawar harga</span></span>
                <input type="checkbox" name="is_negotiable" value="1" @checked(old('is_negotiable', $listing->is_negotiable ?? true)) class="peer sr-only">
                <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 transition peer-checked:bg-ink after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
            </label>
        </section>

        {{-- 3. Kondisi --}}
        <section data-step="3" class="hidden space-y-5">
            <div><h2 class="text-xl font-bold">Kondisi</h2><p class="mt-0.5 text-sm text-slate-500">Jujur soal kondisi, pembeli lebih percaya.</p></div>
            <fieldset>
                <legend class="text-sm font-semibold">Kondisi *</legend>
                <div class="mt-2 grid grid-cols-3 gap-2 px-px">
                    @foreach (\App\Models\Listing::KONDISI as $kode => [$label, $ket])
                        <label class="relative cursor-pointer">
                            <input type="radio" name="condition" value="{{ $kode }}" class="peer sr-only" @checked($nilai('condition') === $kode)>
                            <span class="flex h-full flex-col items-center gap-0.5 rounded-2xl border border-slate-200 px-2 py-3.5 text-center peer-checked:border-ink peer-checked:ring-1 peer-checked:ring-ink"><span class="text-sm font-semibold">{{ $label }}</span><span class="text-[11px] leading-tight text-slate-500">{{ $ket }}</span></span>
                        </label>
                    @endforeach
                </div>
                {!! $err('condition') !!}
            </fieldset>
            <label class="block">
                <span class="text-sm font-semibold">Catatan kekurangan <span class="font-normal text-slate-400">(opsional)</span></span>
                <input name="condition_note" value="{{ $nilai('condition_note') }}" maxlength="120" placeholder="mis. baret halus di sandaran" class="{{ $inputKelas }}">
                {!! $err('condition_note') !!}
            </label>
            <fieldset>
                <legend class="text-sm font-semibold">Detail barang <span class="font-normal text-slate-400">(opsional)</span></legend>
                <div class="mt-2 grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-medium text-slate-500">Bahan</span><input name="material" value="{{ $nilai('material') }}" maxlength="100" placeholder="mis. Kayu jati" class="{{ $inputKelas }}"></label>
                    <label class="block"><span class="text-xs font-medium text-slate-500">Ukuran</span><input name="size" value="{{ $nilai('size') }}" maxlength="100" placeholder="mis. 50 × 55 × 95 cm" class="{{ $inputKelas }}"></label>
                    <label class="block"><span class="text-xs font-medium text-slate-500">Lama pakai</span><input name="usage_duration" value="{{ $nilai('usage_duration') }}" maxlength="100" placeholder="mis. 3 tahun" class="{{ $inputKelas }}"></label>
                    <label class="block"><span class="text-xs font-medium text-slate-500">Jumlah</span><input type="number" name="quantity" min="1" max="99" value="{{ $nilai('quantity') ?? 1 }}" class="{{ $inputKelas }}"></label>
                </div>
            </fieldset>
        </section>

        {{-- 4. Lokasi --}}
        <section data-step="4" class="hidden space-y-5">
            <div><h2 class="text-xl font-bold">Lokasi</h2><p class="mt-0.5 text-sm text-slate-500">Tinggal satu langkah lagi.</p></div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-100"><svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg></span>
                <span class="min-w-0 flex-1"><span class="block text-sm font-semibold">Daerah</span><span class="block truncate text-xs text-slate-500">{{ $lokasi ?? 'Belum diatur' }}</span></span>
                @unless ($ubah)<a href="{{ route('lokasi', ['from' => 'profil']) }}" class="text-sm font-semibold text-brand-700">{{ $lokasi ? 'Ubah' : 'Atur' }}</a>@endunless
            </div>
            <p class="text-xs text-slate-500">Pembeli hanya melihat nama daerah dan jarak perkiraan.</p>
            {!! $err('kelurahan_id') !!}
        </section>

        <div class="fixed bottom-0 left-1/2 z-30 flex w-full max-w-[420px] -translate-x-1/2 gap-2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            <button type="button" id="prev" class="hidden shrink-0 rounded-full border border-slate-300 px-6 py-3.5 font-semibold">Kembali</button>
            <button type="button" id="next" class="flex-1 rounded-full bg-ink py-3.5 font-semibold text-white">Lanjut</button>
            <button type="submit" id="kirim" name="action" value="publish" class="hidden flex-1 rounded-full bg-ink py-3.5 font-semibold text-white">{{ $ubah ? 'Simpan perubahan' : 'Pasang Sekarang' }}</button>
        </div>
    </form>

    @push('scripts')
        @vite('resources/js/barang.js')
    @endpush
</x-app-layout>
