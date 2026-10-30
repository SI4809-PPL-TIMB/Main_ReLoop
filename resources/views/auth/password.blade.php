@component('layouts.app', [
    'title' => 'Reloop · Atur Ulang Kata Sandi',
    'language' => 'id',
    'shellClass' => 'relative mx-auto min-h-screen max-w-[420px] overflow-x-hidden bg-white pb-36',
])
    @slot('bodyClass', 'reloop-login bg-[#e9ebe8]')
    <header class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-white px-4 py-4">
        <a href="{{ route('login') }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        @if ($step < 4)<p class="text-xs font-semibold text-brand-700">Langkah {{ $step }} dari 3</p>@endif
    </header>
    @if ($step < 4)
        <div class="flex gap-1.5 px-4" aria-hidden="true">
            @for ($i = 1; $i <= 3; $i++)
                <span class="h-1.5 flex-1 rounded-full {{ $i <= $step ? 'bg-ink' : 'bg-slate-200' }}"></span>
            @endfor
        </div>
    @endif

    @if ($step === 1)
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5 px-4 pt-8">
            @csrf
            <div>
                <img src="{{ asset('assets/illust/auth/password.png') }}" alt="Pulihkan akses akun" width="128" height="96" class="mb-4 h-24 w-full object-contain">
                <h1 class="text-[28px] font-bold leading-tight">Lupa kata sandi?</h1>
                <p class="mt-1 text-sm text-slate-500">Tenang, masukkan email akunmu. Kami kirim kode untuk atur ulang.</p>
            </div>
            @include('auth.partials.reset-errors')
            <label class="block">
                <span class="text-sm font-semibold">Email</span>
                <input name="email" type="email" required maxlength="255" autocomplete="email" value="{{ old('email') }}" placeholder="nama@email.com" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-ink">
            </label>
            <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <button class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">Kirim kode</button>
                <p class="mt-3 text-center text-sm text-slate-500">Ingat kata sandimu? <a href="{{ route('login') }}" class="font-semibold text-ink underline">Masuk</a></p>
            </div>
        </form>
    @elseif ($step === 2)
        <form method="POST" action="{{ route('password.verify') }}" class="space-y-5 px-4 pt-8">
            @csrf
            <div>
                <img src="{{ asset('assets/illust/auth/verification.png') }}" alt="Kode verifikasi telah dikirim" width="128" height="96" class="mb-4 h-24 w-full object-contain">
                <h1 class="text-[28px] font-bold leading-tight">Cek email kamu</h1>
                <p class="mt-1 text-sm text-slate-500">Jika email terdaftar, kode 6 digit sudah kami kirim. Berlaku 10 menit. Belum masuk? Cek folder spam juga ya.</p>
            </div>
            @include('auth.partials.reset-errors')
            <label class="block">
                <span class="text-sm font-semibold">Kode verifikasi</span>
                <input name="code" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" title="Masukkan 6 digit angka" placeholder="••••••" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-center text-xl font-bold tracking-[0.5em] outline-none focus:border-ink">
            </label>
            <div class="flex items-center justify-between text-sm">
                <a href="{{ route('password.request') }}" class="font-semibold text-slate-500 underline">Ganti email</a>
                <button type="submit" form="resend-code" data-resend-countdown="{{ $retryAfter }}" @disabled($retryAfter > 0) class="font-semibold text-brand-600 disabled:text-slate-400">{{ $retryAfter > 0 ? 'Kirim ulang dalam '.$retryAfter.' dtk' : 'Kirim ulang' }}</button>
            </div>
            <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <button class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">Verifikasi</button>
                <a href="{{ route('login') }}" class="mt-2 block rounded-full border border-slate-200 py-3 text-center font-semibold">Kembali ke Masuk</a>
            </div>
        </form>
        <form id="resend-code" method="POST" action="{{ route('password.email') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
        </form>
    @elseif ($step === 3)
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5 px-4 pt-8">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">
            <div>
                <img src="{{ asset('assets/illust/auth/password.png') }}" alt="Amankan akun dengan kata sandi baru" width="128" height="96" class="mb-4 h-24 w-full object-contain">
                <h1 class="text-[28px] font-bold leading-tight">Atur kata sandi baru</h1>
                <p class="mt-1 text-sm text-slate-500">Pakai kombinasi yang mudah kamu ingat, tapi sulit ditebak orang.</p>
            </div>
            @include('auth.partials.reset-errors')
            <x-password-input name="password" label="Kata sandi baru" hint="Minimal 8 karakter." />
            <x-password-input name="password_confirmation" label="Ulangi kata sandi baru" placeholder="Ketik ulang" />
            @if ($errors->has('email') || $errors->has('token'))
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 underline">Minta kode baru</a>
            @endif
            <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <button class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">Simpan kata sandi</button>
            </div>
        </form>
    @else
        <section class="px-8 pt-16 text-center">
            <img src="{{ asset('assets/illust/auth/account-ready.png') }}" alt="Kata sandi berhasil diperbarui" width="128" height="96" class="mx-auto size-28 object-contain">
            <h1 class="mt-4 text-[28px] font-bold leading-tight">Kata sandi diperbarui</h1>
            <p class="mt-1 text-sm text-slate-500">Silakan masuk pakai kata sandi barumu.</p>
            <div class="fixed bottom-0 left-1/2 z-30 w-full max-w-[420px] -translate-x-1/2 border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <a href="{{ route('login') }}" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white">Masuk sekarang</a>
            </div>
        </section>
    @endif
@endcomponent
