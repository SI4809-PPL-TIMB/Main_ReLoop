@use('App\Models\MitraPengepul')
@php
    // Setelah validasi gagal, isian dipulihkan dari old() — berisi input mentah seperti yang diketik.
    // Harga "3.500" bukan desimal: titik dibuang dulu sebelum diformat ulang.
    $adaOld = session()->hasOldInput();
    $areaDipilih = $adaOld ? array_map('intval', (array) old('area', [])) : $mitra->area->pluck('id')->all();
    $layanan = fn ($k) => $adaOld ? (bool) old($k) : (bool) $mitra->$k;
    $hargaNilai = function ($id) use ($adaOld, $harga) {
        $v = (string) ($adaOld ? old("harga.$id") : ($harga[$id] ?? ''));
        $angka = preg_replace('/[.\s]/', '', $v);
        return ctype_digit($angka) ? number_format((int) $angka, 0, ',', '.') : $v;
    };
    $jamAktif = fn ($n) => $adaOld ? ! empty(old("jam.$n.aktif")) : isset($jam[$n]);
    $jamNilai = fn ($n, $k, $bawaan) => $adaOld ? (old("jam.$n.$k") ?? $bawaan) : (isset($jam[$n]) ? substr($jam[$n]->$k, 0, 5) : $bawaan);
    $err = fn ($k) => $errors->has($k) ? '<p class="mt-2 text-sm text-red-600">'.e($errors->first($k)).'</p>' : '';
@endphp

@component('layouts.app', ['title' => 'ReLoop · Pengaturan Mitra'])
    <x-slot name="header">
        <x-top-bar title="Pengaturan Mitra" :back="route('account')" />
    </x-slot>

    <form method="POST" action="{{ route('mitra.pengaturan.update') }}" class="space-y-6 px-4 pb-32 pt-4" novalidate>
        @csrf
        @method('PUT')

        @if (session('sukses'))
            <div role="alert" class="rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-medium text-brand-800">{{ session('sukses') }}</div>
        @endif

        {{-- Status verifikasi dari admin --}}
        <section class="rounded-2xl border border-slate-200 p-4">
            <div class="flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="font-bold leading-tight">{{ $mitra->nama_usaha }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $mitra->alamat }}</p>
                </div>
                <x-status-mitra :status="$mitra->status" />
            </div>
            <p @class([
                'mt-3 rounded-xl p-3 text-sm',
                'bg-amber-50 text-amber-800' => $mitra->status === MitraPengepul::MENUNGGU,
                'bg-brand-50 text-brand-800' => $mitra->status === MitraPengepul::TERVERIFIKASI,
                'bg-red-50 text-red-700' => $mitra->status === MitraPengepul::DITOLAK,
            ])>
                @switch($mitra->status)
                    @case(MitraPengepul::TERVERIFIKASI)
                        Akunmu terverifikasi. Pengguna bisa menemukanmu di pencarian pengepul.
                        @break
                    @case(MitraPengepul::DITOLAK)
                        <strong class="block">Verifikasi ditolak admin.</strong>{{ $mitra->catatan_verifikasi }}
                        @break
                    @default
                        Menunggu verifikasi admin. Kamu belum muncul di pencarian, tapi sudah bisa melengkapi pengaturan di bawah.
                @endswitch
            </p>
            <p class="mt-3 text-xs text-slate-500">Nama usaha, alamat, dan nomor HP diubah lewat admin.</p>
        </section>

        {{-- Layanan --}}
        <fieldset class="space-y-3">
            <legend class="text-lg font-bold">Layanan</legend>
            <p class="-mt-1 text-sm text-slate-500">Cara pengguna menyetor sampah ke kamu.</p>
            @foreach ([
                'layanan_jemput' => ['Jemput', 'Kamu datang ke alamat pengguna di area layananmu.'],
                'layanan_antar'  => ['Antar Sendiri', 'Pengguna mengantar sampah ke lapakmu.'],
            ] as $nama => [$judul, $ket])
                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-slate-200 px-4 py-3 has-[:checked]:border-ink">
                    <span><span class="block text-sm font-semibold">{{ $judul }}</span><span class="block text-xs text-slate-500">{{ $ket }}</span></span>
                    <input type="checkbox" name="{{ $nama }}" value="1" @checked($layanan($nama)) class="peer sr-only">
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 transition peer-checked:bg-ink after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                </label>
            @endforeach
            {!! $err('layanan') !!}
        </fieldset>

        {{-- Area layanan --}}
        <fieldset class="space-y-3 border-t border-slate-100 pt-6">
            <legend class="text-lg font-bold">Area layanan</legend>
            <p class="-mt-1 text-sm text-slate-500">Kecamatan yang kamu layani. Pengguna di sini akan melihatmu.</p>
            @foreach ($kecamatan as $kabupaten => $daftar)
                <div>
                    <p class="text-xs font-medium text-slate-500">{{ $kabupaten }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($daftar as $kec)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="area[]" value="{{ $kec->id }}" @checked(in_array($kec->id, $areaDipilih, true)) class="peer sr-only">
                                <span class="inline-block rounded-full border border-slate-200 px-4 py-2 text-sm font-medium peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-loop">{{ $kec->nama }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            {!! $err('area') !!}{!! $err('area.*') !!}
        </fieldset>

        {{-- Harga per kg --}}
        <fieldset class="space-y-3 border-t border-slate-100 pt-6">
            <legend class="text-lg font-bold">Harga per kg</legend>
            <p class="-mt-1 text-sm text-slate-500">Kosongkan jenis sampah yang tidak kamu terima.</p>
            <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                @foreach ($sampah as $k)
                    <label class="flex items-center gap-3 px-4 py-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-loop-soft text-loop-dark"><x-category-icon :name="$k->icon" class="size-4" /></span>
                        <span class="min-w-0 flex-1 text-sm font-semibold">{{ $k->name }}</span>
                        <span class="flex w-36 items-center rounded-xl border border-slate-200 px-3 focus-within:border-ink @error('harga.'.$k->id) border-red-500 @enderror">
                            <span class="text-xs font-semibold text-slate-400">Rp</span>
                            <input name="harga[{{ $k->id }}]" inputmode="numeric" value="{{ $hargaNilai($k->id) }}" placeholder="—" aria-label="Harga {{ $k->name }} per kg" class="w-full min-w-0 bg-transparent px-1.5 py-2.5 text-right text-sm font-semibold outline-none">
                            <span class="text-xs text-slate-400">/kg</span>
                        </span>
                    </label>
                    @error('harga.'.$k->id)<p class="px-4 pb-3 text-sm text-red-600">{{ $message }}</p>@enderror
                @endforeach
            </div>
            {!! $err('harga') !!}
        </fieldset>

        {{-- Jam operasional --}}
        <fieldset class="space-y-3 border-t border-slate-100 pt-6">
            <legend class="text-lg font-bold">Jam operasional</legend>
            <p class="-mt-1 text-sm text-slate-500">Centang hari buka. Hari yang tidak dicentang dianggap libur.</p>
            <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                @foreach (MitraPengepul::HARI as $no => $hari)
                    @php($aktif = $jamAktif($no))
                    <div data-hari class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                <input type="checkbox" name="jam[{{ $no }}][aktif]" value="1" @checked($aktif) data-hari-aktif class="size-5 shrink-0 accent-ink">
                                <span class="text-sm font-semibold">{{ $hari }}</span>
                            </label>
                            <span class="flex items-center gap-1.5 text-sm">
                                <input type="time" name="jam[{{ $no }}][buka]" value="{{ $jamNilai($no, 'buka', '08:00') }}" aria-label="Jam buka {{ $hari }}" @disabled(! $aktif) class="rounded-lg border border-slate-200 px-2 py-1.5 disabled:opacity-40">
                                <span class="text-slate-400">–</span>
                                <input type="time" name="jam[{{ $no }}][tutup]" value="{{ $jamNilai($no, 'tutup', '16:00') }}" aria-label="Jam tutup {{ $hari }}" @disabled(! $aktif) class="rounded-lg border border-slate-200 px-2 py-1.5 disabled:opacity-40">
                            </span>
                        </div>
                        @foreach (['buka', 'tutup'] as $k)
                            @error("jam.$no.$k")<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@break @enderror
                        @endforeach
                    </div>
                @endforeach
            </div>
            {!! $err('jam') !!}
        </fieldset>

        <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            <button type="submit" data-loading-text="Menyimpan…" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">Simpan pengaturan</button>
        </div>
    </form>

    @push('scripts')
        <script>
            // Jam hanya bisa diisi untuk hari yang dicentang.
            document.querySelectorAll('[data-hari]').forEach((baris) => {
                const cek = baris.querySelector('[data-hari-aktif]');
                cek.addEventListener('change', () =>
                    baris.querySelectorAll('input[type="time"]').forEach((jam) => { jam.disabled = !cek.checked; }));
            });
        </script>
    @endpush
@endcomponent
