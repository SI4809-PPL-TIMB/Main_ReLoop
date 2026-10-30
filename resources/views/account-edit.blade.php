@component('layouts.app', ['title' => 'ReLoop · Ubah Profil', 'nav' => 'user'])
    <x-top-bar title="Ubah Profil" :back="route('account')" />

    <section class="space-y-5 px-4 pb-12 pt-2">
        {{-- Flash Notification --}}
        @if (session('sukses'))
            <div role="alert" class="flex items-center gap-2.5 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800 shadow-sm">
                <svg class="size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="font-medium">{{ session('sukses') }}</span>
            </div>
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

        {{-- Kartu Foto Profil --}}
        <div class="flex flex-col items-center gap-3 rounded-3xl border border-slate-200 bg-white p-5 text-center shadow-card">
            <div class="relative">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-24 rounded-full border-2 border-brand-500 object-cover shadow-sm">
                @else
                    <div class="flex size-24 items-center justify-center rounded-full border-2 border-brand-500 bg-brand-100 text-2xl font-bold uppercase tracking-wider text-brand-800 shadow-sm" aria-label="Avatar inisial: {{ $user->initials() }}">
                        {{ $user->initials() }}
                    </div>
                @endif
            </div>

            <div>
                <h2 class="text-base font-bold text-ink">{{ $user->name }}</h2>
                <p class="text-xs text-slate-400">Pilih foto yang jelas agar mudah dikenali pembeli & penjual</p>
            </div>

            {{-- Tombol Aksi Avatar --}}
            <div class="flex flex-wrap items-center justify-center gap-2">
                <form method="POST" action="{{ route('account.avatar.upload') }}" enctype="multipart/form-data" id="form-avatar">
                    @csrf
                    <label for="avatar-input" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 active:scale-95">
                        <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                        <span>{{ $profil?->avatar ? 'Ganti Foto' : 'Unggah Foto' }}</span>
                        <input id="avatar-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden" onchange="document.getElementById('form-avatar').submit()">
                    </label>
                </form>

                @if ($profil?->avatar)
                    <form method="POST" action="{{ route('account.avatar.delete') }}" id="form-hapus-avatar">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-4 py-2 text-xs font-semibold text-red-600 shadow-sm transition hover:bg-red-50 active:scale-95" onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil?')">
                            <svg class="size-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                            <span>Hapus Foto</span>
                        </button>
                    </form>
                @endif
            </div>
            <p class="text-[11px] text-slate-400">Format yang didukung: JPG, PNG, WEBP (maks. 2 MB)</p>
        </div>

        {{-- Form Ubah Data Diri --}}
        <form method="POST" action="{{ route('account.update') }}" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            @csrf
            @method('PUT')

            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-ink">Informasi Akun</h3>
                <p class="text-xs text-slate-500">Perbarui data diri dan kontak yang dapat dihubungi</p>
            </div>

            {{-- Nama Tampilan --}}
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700">Nama Tampilan <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="150"
                       placeholder="Masukkan nama tampilan kamu"
                       class="mt-1.5 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Bio / Tentang Saya --}}
            <div>
                <label for="bio" class="block text-xs font-semibold text-slate-700">Bio Singkat</label>
                <textarea id="bio" name="bio" rows="3" maxlength="500"
                          placeholder="Contoh: Sering jual perabot kayu jati bekas terawat & botol kaca siap setor."
                          class="mt-1.5 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500 resize-none">{{ old('bio', $profil?->bio) }}</textarea>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Akan tampil di profil publikmu</span>
                    <span>Maks. 500 karakter</span>
                </div>
                @error('bio')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nomor WhatsApp / Telepon --}}
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700">Nomor WhatsApp / HP</label>
                <div class="relative mt-1.5">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-xs font-semibold text-slate-400">ID</span>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="12" minlength="11"
                           inputmode="numeric" pattern="[0-9]{11,12}"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           placeholder="08xxxxxxxxxx"
                           class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <p class="mt-1 text-[11px] text-slate-400">Hanya Boleh Diisi angka</p>
                @error('phone')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Alamat Email (Read-only) --}}
            <div>
                <div class="flex items-center justify-between">
                    <label for="email" class="block text-xs font-semibold text-slate-700">Alamat Email</label>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-700">
                        <svg class="size-3 text-brand-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Akun Utama
                    </span>
                </div>
                <input type="email" id="email" value="{{ $user->email }}" disabled
                       class="mt-1.5 w-full cursor-not-allowed rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                <p class="mt-1 text-[11px] text-slate-400">Alamat email digunakan untuk login dan pengiriman kode verifikasi.</p>
            </div>

            {{-- Tombol Submit Form --}}
            <div class="pt-2 space-y-2">
                <button type="submit" class="w-full rounded-full bg-ink py-3.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-ink/90 active:scale-[.98]">
                    Simpan Perubahan
                </button>
                <a href="{{ route('account') }}" class="block text-center text-xs font-semibold text-slate-500 hover:text-ink py-2 transition">
                    Batal & Kembali ke Akun
                </a>
            </div>
        </form>
    </section>
@endcomponent

