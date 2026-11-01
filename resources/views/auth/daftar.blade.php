<x-app-layout>
    <x-slot name="title">Daftar Akun — ReLoop</x-slot>

    {{-- Header: back button kiri + langkah kanan (persis mockup) --}}
    <header class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-white px-4 py-4">
        <a href="{{ url('/') }}" aria-label="Kembali"
           class="grid size-10 place-items-center rounded-full border border-slate-200">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </a>
        <p class="text-xs font-semibold text-brand-700">Langkah 1 dari 2</p>
    </header>

    <main>
        <form id="form-daftar" method="POST" action="{{ route('daftar.store') }}" class="space-y-5 px-4 pt-2 pb-36">
            @csrf

            {{-- Progress bar + Heading --}}
            <div>
                {{-- Progress bar 2 segmen --}}
                <div class="flex gap-1.5" aria-hidden="true">
                    <span class="h-1.5 flex-1 rounded-full bg-ink"></span>
                    <span class="h-1.5 flex-1 rounded-full bg-slate-200"></span>
                </div>

                <h1 class="mt-4 text-[28px] font-bold leading-tight">Buat akun</h1>
                <p class="mt-1 text-sm text-slate-500">Satu akun untuk jual-beli dan setor sampah.</p>
            </div>

            {{-- Alert: sudah terdaftar --}}
            @if ($errors->has('identifier') && str_contains($errors->first('identifier'), 'terdaftar'))
                <div role="alert" class="flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm">
                    <svg class="size-5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16h.01"/>
                    </svg>
                    <p class="text-red-700">
                        <b>Email atau nomor HP ini sudah terdaftar.</b>
                        <a href="{{ route('login') }}" class="font-semibold underline">Masuk saja</a>
                        atau pakai yang lain.
                    </p>
                </div>
            @endif

            {{-- Nama Lengkap --}}
            <label class="block">
                <span class="text-sm font-semibold">Nama lengkap</span>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                    placeholder="Sesuai KTP"
                    class="mt-2 w-full rounded-xl border px-4 py-3 outline-none transition focus:border-ink
                           {{ $errors->has('name') ? 'border-red-500 bg-red-50' : 'border-slate-200' }}"
                >
                @error('name')
                    <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span>
                @enderror
            </label>

            {{-- Email --}}
            <label class="block">
                <span class="text-sm font-semibold">Email</span>
                <input
                    id="identifier"
                    type="email"
                    name="identifier"
                    value="{{ old('identifier') }}"
                    required
                    autocomplete="username"
                    pattern="[^@\s]+@[^@\s]+\.[^@\s]+|(\+62|62|0)8[0-9]{7,12}"
                    title="Isi email yang valid"
                    placeholder="nama@email.com"
                    class="mt-2 w-full rounded-xl border px-4 py-3 outline-none transition focus:border-ink
                           {{ $errors->has('identifier') ? 'border-red-500 aria-[invalid=true]:border-red-500' : 'border-slate-200' }}"
                    @if($errors->has('identifier')) aria-invalid="true" @endif
                >
                @error('identifier')
                    @if (!str_contains($message, 'terdaftar'))
                        <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span>
                    @else
                        <span class="mt-1.5 block text-xs font-medium text-red-600">Sudah terdaftar. Pakai email atau nomor lain.</span>
                    @endif
                @enderror
            </label>

            {{-- Kata Sandi --}}
            <label class="block">
                <span class="text-sm font-semibold">Kata sandi</span>
                <span class="mt-2 flex items-center rounded-xl border pl-4 pr-1 transition focus-within:border-ink
                             {{ $errors->has('password') ? 'border-red-500' : 'border-slate-200' }}">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Buat kata sandi"
                        class="w-full bg-transparent py-3 outline-none"
                    >
                    <button
                        id="btn-toggle-password"
                        type="button"
                        aria-label="Tampilkan kata sandi"
                        aria-pressed="false"
                        onclick="togglePw(this)"
                        class="group grid size-9 place-items-center text-slate-400"
                    >
                        {{-- Icon eye open --}}
                        <svg class="size-5 group-aria-pressed:hidden" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        {{-- Icon eye off --}}
                        <svg class="hidden size-5 group-aria-pressed:block" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m3 3 18 18"/>
                            <path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6C3.9 8.3 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/>
                            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                        </svg>
                    </button>
                </span>
                @error('password')
                    <span class="mt-1.5 block text-xs font-medium text-red-600">{{ $message }}</span>
                @else
                    <span class="mt-2 block text-xs text-slate-500">Minimal 8 karakter.</span>
                @enderror
            </label>

            {{-- Checkbox Syarat & Ketentuan --}}
            <label class="flex cursor-pointer items-start gap-3 text-sm">
                <input
                    id="terms"
                    type="checkbox"
                    name="terms"
                    value="1"
                    required
                    {{ old('terms') ? 'checked' : '' }}
                    class="mt-0.5 size-5 shrink-0 accent-ink"
                >
                <span class="text-slate-600">
                    Saya setuju dengan
                    <a href="#" class="font-semibold text-ink underline">Syarat &amp; Ketentuan</a>
                    dan
                    <a href="#" class="font-semibold text-ink underline">Kebijakan Privasi</a>
                    Reloop.
                </span>
            </label>
            @error('terms')
                <p class="-mt-3 text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror

        </form>
    </main>

    {{-- Tombol fixed di bawah (persis mockup) --}}
    <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white
                px-4 pt-3 pb-[max(14px,env(safe-area-inset-bottom))]">
        <button
            id="btn-daftar"
            form="form-daftar"
            type="submit"
            class="w-full rounded-full bg-ink py-3.5 font-semibold text-white transition
                   hover:bg-ink/90 active:scale-[.98]"
        >
            Lanjut
        </button>
        <p class="mt-3 text-center text-sm text-slate-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" id="link-masuk" class="font-semibold text-ink underline">Masuk</a>
        </p>
    </div>

    @push('scripts')
    <script>
        function togglePw(btn) {
            const input = btn.previousElementSibling;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isHidden);
            btn.setAttribute('aria-label', (isHidden ? 'Sembunyikan' : 'Tampilkan') + ' kata sandi');
        }
    </script>
    @endpush

</x-app-layout>
