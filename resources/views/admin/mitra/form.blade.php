@php
    $ubah = $mitra->exists;
    $err = fn ($k) => $errors->has($k) ? '<p class="mt-2 text-sm text-red-600">'.e($errors->first($k)).'</p>' : '';
    $nilai = fn ($k) => old($k, $mitra->$k);
    $akun = fn ($k) => old($k, $mitra->user?->$k);
    $inputKelas = 'mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-ink';
@endphp

@component('layouts.app', ['title' => 'ReLoop · '.($ubah ? 'Ubah Mitra' : 'Tambah Mitra')])
    <x-slot name="header">
        <x-top-bar :title="$ubah ? 'Ubah Mitra' : 'Tambah Mitra'" :back="$ubah ? route('admin.mitra.show', $mitra) : route('admin.mitra.index')" />
    </x-slot>

    <form method="POST" action="{{ $ubah ? route('admin.mitra.update', $mitra) : route('admin.mitra.store') }}" class="space-y-6 px-4 pb-32 pt-4">
        @csrf
        @if ($ubah) @method('PUT') @endif

        <section class="space-y-4">
            <div><h2 class="text-lg font-bold">Data usaha</h2><p class="mt-0.5 text-sm text-slate-500">Tampil ke pengguna saat mencari pengepul.</p></div>
            <label class="block">
                <span class="text-sm font-semibold">Nama usaha *</span>
                <input name="nama_usaha" value="{{ $nilai('nama_usaha') }}" maxlength="150" required placeholder="mis. Bank Sampah Hijau Lestari" class="{{ $inputKelas }}">
                {!! $err('nama_usaha') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">Nomor HP usaha *</span>
                <input name="telepon" type="tel" inputmode="tel" value="{{ $nilai('telepon') }}" maxlength="20" required placeholder="081234567890" class="{{ $inputKelas }}">
                {!! $err('telepon') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">Kelurahan lapak *</span>
                <select name="kelurahan_id" required class="{{ $inputKelas }} bg-white">
                    <option value="">Pilih kelurahan</option>
                    @foreach ($kelurahan as $grup => $daftar)
                        <optgroup label="{{ $grup }}">
                            @foreach ($daftar as $k)
                                <option value="{{ $k->id }}" @selected((int) $nilai('kelurahan_id') === $k->id)>{{ $k->nama }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                {!! $err('kelurahan_id') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">Alamat lapak *</span>
                <textarea name="alamat" rows="2" maxlength="255" required placeholder="Nama jalan, nomor, patokan" class="{{ $inputKelas }}">{{ $nilai('alamat') }}</textarea>
                <span class="mt-1 block text-xs text-slate-500">Pengguna yang memilih Antar Sendiri datang ke alamat ini.</span>
                {!! $err('alamat') !!}
            </label>
        </section>

        <section class="space-y-4 border-t border-slate-100 pt-6">
            <div><h2 class="text-lg font-bold">Akun pengepul</h2><p class="mt-0.5 text-sm text-slate-500">Dipakai pengepul untuk masuk dan mengatur harga, area, serta jam operasional.</p></div>
            <label class="block">
                <span class="text-sm font-semibold">Nama pemilik *</span>
                <input name="name" value="{{ $akun('name') }}" maxlength="150" required class="{{ $inputKelas }}">
                {!! $err('name') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">Email *</span>
                <input name="email" type="email" value="{{ $akun('email') }}" maxlength="255" required autocomplete="off" class="{{ $inputKelas }}">
                {!! $err('email') !!}
            </label>
            <label class="block">
                <span class="text-sm font-semibold">{{ $ubah ? 'Kata sandi baru' : 'Kata sandi awal *' }}</span>
                <input name="password" type="password" minlength="8" @unless ($ubah) required @endunless autocomplete="new-password" class="{{ $inputKelas }}">
                <span class="mt-1 block text-xs text-slate-500">{{ $ubah ? 'Kosongkan jika tidak diubah.' : 'Minimal 8 karakter. Sampaikan ke pengepul dan minta diganti setelah masuk.' }}</span>
                {!! $err('password') !!}
            </label>
            @unless ($ubah)
                <p class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Mitra baru berstatus <strong>Menunggu verifikasi</strong> dan belum muncul di pencarian sampai kamu memverifikasinya.</p>
            @endunless
        </section>

        <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
            <button type="submit" data-loading-text="Menyimpan…" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">{{ $ubah ? 'Simpan perubahan' : 'Tambah mitra' }}</button>
        </div>
    </form>
@endcomponent
