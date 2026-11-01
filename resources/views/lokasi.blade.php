@component('layouts.app', ['title' => 'Reloop · Atur Lokasi'])
    @php($levels = ['provinsi' => 'Provinsi', 'kabupaten' => 'Kota/Kabupaten', 'kecamatan' => 'Kecamatan', 'kelurahan' => 'Desa/Kelurahan'])

    <x-slot name="header">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-white px-4 py-4">
            <a href="{{ $dariProfil ? route('account') : ($from === 'beranda' ? route('beranda') : route('selesai')) }}" aria-label="Kembali"
               class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </a>
            @unless ($dariProfil)
                <p class="text-xs font-semibold text-brand-700">Langkah 2 dari 2</p>
            @endunless
        </header>
    </x-slot>

    <form method="POST" action="{{ route('lokasi.update') }}" id="form-lokasi" class="space-y-5 px-4 pb-28 pt-2"
          data-url-anak="{{ url('/wilayah') }}" data-url-cari="{{ route('wilayah.cari') }}" data-url-gps="{{ route('lokasi.gps') }}"
          data-pilihan='@json($pilihan)'>
        @csrf
        <input type="hidden" name="from" value="{{ $from }}">

        <div>
            <img src="{{ asset('assets/illust/auth/location.png') }}" alt="Temukan barang dan pengepul di sekitar lokasi" width="128" height="96" class="mb-4 h-24 w-full object-contain">
            <h1 class="text-[28px] font-bold leading-tight">{{ $dariProfil ? 'Ubah lokasi' : 'Kamu tinggal di mana?' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Biar kami tampilkan barang dan pengepul terdekat.</p>
        </div>

        {{-- BR-07: hanya nama daerah + jarak perkiraan yang publik --}}
        <div class="flex gap-3 rounded-2xl border border-loop bg-loop-soft p-4 text-sm">
            <p class="text-slate-700">Orang lain hanya melihat <b class="text-ink">nama desa</b> dan <b class="text-ink">jarak perkiraan</b>, misal "Sukamaju · ±1,2 km". Alamat lengkapmu tidak pernah ditampilkan.</p>
        </div>

        <fieldset class="min-w-0">
            <legend class="text-sm font-semibold">Daerah *</legend>
            <button type="button" id="daerah-btn" class="mt-2 flex w-full items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3.5 text-left hover:bg-slate-50">
                <svg class="size-5 shrink-0 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z" /><circle cx="12" cy="10" r="2.5" /></svg>
                <span id="daerah-nilai" class="min-w-0 flex-1 truncate font-semibold text-slate-400">Pilih daerah</span>
                <svg class="size-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </button>
            <input type="hidden" name="kelurahan_id" id="kelurahan_id" value="{{ old('kelurahan_id', $pilihan['kelurahan']['id'] ?? '') }}">
            <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $profile?->latitude) }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $profile?->longitude) }}">
            @error('kelurahan_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <label class="block">
            <span class="text-sm font-semibold">Patokan alamat (untuk jemput) <span class="font-normal text-slate-400">· opsional</span></span>
            <textarea name="patokan" rows="2" maxlength="255" placeholder="Contoh: Gg. Melati, rumah pagar hijau"
                      class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-ink">{{ old('patokan', $profile?->patokan) }}</textarea>
            <span class="mt-2 block text-xs text-slate-500">Privat. Hanya dibagikan ke pengepul setelah order jemput diterima.</span>
        </label>

        <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            <button class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">{{ $dariProfil ? 'Simpan lokasi' : 'Mulai pakai Reloop' }}</button>
        </div>
    </form>


    {{-- Bottom sheet pilih daerah --}}
    <dialog id="sheet" aria-labelledby="sheet-judul" class="m-0 mt-auto w-full max-w-[420px] rounded-t-3xl bg-white p-0 backdrop:bg-black/40 sm:mx-auto">
        <div class="flex max-h-[85vh] flex-col">
            <div class="flex items-center gap-2 px-4 pb-2 pt-4">
                <button type="button" id="sheet-back" hidden aria-label="Kembali" class="grid size-9 place-items-center rounded-full border border-slate-200">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>
                <h2 id="sheet-judul" class="flex-1 text-base font-bold">Pilih provinsi</h2>
                <button type="button" id="sheet-tutup" aria-label="Tutup" class="grid size-9 place-items-center rounded-full border border-slate-200">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                </button>
            </div>
            <p id="sheet-jejak" class="truncate px-4 text-xs text-slate-500"></p>
            <div class="px-4 pb-2 pt-3">
                <input id="cari" type="search" autocomplete="off" placeholder="Cari desa, kecamatan, atau kota" aria-label="Cari wilayah"
                       class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-ink">
            </div>
            <div class="px-4 pb-3">
                <div id="gps-error" role="alert" hidden class="mb-3 rounded-2xl border border-red-200 bg-red-50 p-3 text-xs text-red-700"></div>
                <button type="button" id="gps" class="flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 py-2.5 text-sm font-semibold disabled:opacity-70">Gunakan lokasi saat ini</button>
            </div>
            <ul id="sheet-daftar" class="flex-1 overflow-y-auto px-2 pb-[max(16px,env(safe-area-inset-bottom))]"></ul>
        </div>
    </dialog>

    @push('scripts')
        @vite('resources/js/lokasi.js')
    @endpush
@endcomponent
