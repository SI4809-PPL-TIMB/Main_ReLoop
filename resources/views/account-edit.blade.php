@php
    $kel = $profil?->kelurahan;
    $lokasi = $kel ? $kel->nama.', '.$kel->kecamatan->nama.', '.$kel->kecamatan->kabupaten->nama : 'Atur lokasi';
    $kolom = 'mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-3 font-medium outline-none focus:border-ink';
    $tipeFoto = 'image/jpeg,image/png,image/webp';
@endphp

@component('layouts.app', ['title' => 'ReLoop · Ubah Profil', 'nav' => null])
    <x-top-bar title="Ubah Profil" :back="route('account')" />

    <main class="space-y-4 px-4 pb-28 pt-5">
        @if (session('sukses'))
            <div role="alert" class="rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-medium text-brand-800">{{ session('sukses') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col items-center">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-24 rounded-full object-cover">
            @else
                <div class="flex size-24 items-center justify-center rounded-full bg-brand-100 text-2xl font-bold uppercase text-brand-800" aria-label="Avatar inisial: {{ $user->initials() }}">
                    {{ $user->initials() }}
                </div>
            @endif
            <button type="button" data-open="foto" class="mt-2 text-sm font-semibold text-brand-600">Ganti foto</button>
        </div>

        <form id="form-profil" method="POST" action="{{ route('account.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <label class="block">
                <span class="text-sm font-semibold">Nama</span>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required minlength="3" maxlength="150" autocomplete="name" class="{{ $kolom }}">
            </label>

            <label class="block">
                <span class="text-sm font-semibold">Email</span>
                <input type="email" value="{{ $user->email }}" disabled class="{{ $kolom }} cursor-not-allowed bg-slate-50 text-slate-500">
            </label>

            <label class="block">
                <span class="text-sm font-semibold">Nomor HP</span>
                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" minlength="11" maxlength="12" inputmode="numeric" pattern="[0-9]{11,12}"
                       title="Isi 11 sampai 12 angka, tanpa spasi" autocomplete="tel" placeholder="08xxxxxxxxxx"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="{{ $kolom }}">
            </label>

            <div>
                <p class="text-sm font-semibold">Lokasi</p>
                <a href="{{ route('lokasi', ['from' => 'profil']) }}" class="mt-1.5 flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-3">
                    <svg class="size-5 shrink-0 text-slate-500" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    <span class="flex-1 font-medium">{{ $lokasi }}</span>
                    <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m9 6 6 6-6 6"/></svg>
                </a>
            </div>

            <label class="block">
                <span class="text-sm font-semibold">Bio <span class="font-normal text-slate-400">(opsional)</span></span>
                <textarea name="bio" rows="3" maxlength="160" placeholder="Ceritakan sedikit tentang kamu"
                          class="mt-1.5 w-full resize-none rounded-xl border border-slate-200 px-3 py-3 outline-none focus:border-ink">{{ old('bio', $profil?->bio) }}</textarea>
            </label>

            <p class="flex gap-2 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                <svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                Alamat lengkap tidak pernah ditampilkan ke publik. Orang lain hanya melihat nama area dan perkiraan jarak.
            </p>

            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <button type="submit" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white">Simpan</button>
            </div>
        </form>
    </main>

    <dialog id="foto" class="m-0 mt-auto w-full max-w-[420px] rounded-t-3xl p-0 sm:mx-auto">
        <div class="p-5">
            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-slate-200"></div>
            <h2 class="text-lg font-bold">Foto profil</h2>
            <div class="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200 text-sm font-medium">
                <form method="POST" action="{{ route('account.avatar.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="flex w-full cursor-pointer items-center gap-3 px-4 py-3.5 text-left">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                        Ambil foto
                        <input type="file" name="avatar" accept="{{ $tipeFoto }}" capture="environment" class="sr-only" onchange="this.form.submit()">
                    </label>
                </form>
                <form method="POST" action="{{ route('account.avatar.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="flex w-full cursor-pointer items-center gap-3 px-4 py-3.5 text-left">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m4 18 5-5 4 4 3-3 4 4"/></svg>
                        Pilih dari galeri
                        <input type="file" name="avatar" accept="{{ $tipeFoto }}" class="sr-only" onchange="this.form.submit()">
                    </label>
                </form>
                @if ($profil?->avatar)
                    <form method="POST" action="{{ route('account.avatar.delete') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex w-full items-center gap-3 px-4 py-3.5 text-left text-red-500">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                            Hapus foto
                        </button>
                    </form>
                @endif
            </div>
            <button type="button" data-close class="mt-3 w-full rounded-full border border-slate-300 py-3 font-semibold">Batal</button>
        </div>
    </dialog>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-open]').forEach((tombol) =>
                tombol.addEventListener('click', () => document.getElementById(tombol.dataset.open)?.showModal()));
            document.querySelectorAll('dialog').forEach((dialog) =>
                dialog.addEventListener('click', (e) => {
                    if (e.target === dialog || e.target.closest('[data-close]')) dialog.close();
                }));
        </script>
    @endpush
@endcomponent
