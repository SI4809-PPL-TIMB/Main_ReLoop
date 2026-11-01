@component('layouts.app', [
    'title' => 'Reloop · Masuk',
    'language' => 'id',
    'shellClass' => 'relative mx-auto min-h-screen max-w-[420px] overflow-x-hidden bg-white pb-36',
])
    @slot('bodyClass', 'reloop-login bg-[#e9ebe8]')
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-white px-4 py-4">
            <a href="{{ url('/') }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        </header>
        <form method="POST" action="{{ route('login.store') }}" data-login-form class="space-y-5 px-4 pt-2">
            @csrf
            <div>
                <h1 class="text-[28px] font-bold leading-tight">Masuk</h1>
                <p class="mt-1 text-sm text-slate-500">Senang ketemu lagi. Lanjutkan streak-mu.</p>
            </div>
            @if ($errors->any())
                <div id="login-error" role="alert" class="flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm">
                    <svg class="size-5 shrink-0 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                    <p class="text-red-700"><b>{{ $errors->first() }}</b>@if ($errors->first() === 'Email atau kata sandi salah.') Cek lagi, atau atur ulang lewat Lupa kata sandi.@endif</p>
                </div>
            @endif
            <label for="identifier" class="block">
                <span class="text-sm font-semibold">Email</span>
                <input id="identifier" name="identifier" type="email" required maxlength="255" autocomplete="username"
                       value="{{ old('identifier') }}" placeholder="nama@email.com"
                       @error('identifier') aria-invalid="true" aria-describedby="login-error" @enderror
                       class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-ink aria-[invalid=true]:border-red-500">
            </label>
            <div>
                <label for="password" class="text-sm font-semibold">Kata sandi</label>
                <div class="mt-2 flex items-center rounded-xl border border-slate-200 pl-4 pr-1 focus-within:border-ink has-[[aria-invalid=true]]:border-red-500">
                    <input id="password" name="password" type="password" required maxlength="1024" autocomplete="current-password"
                           placeholder="Kata sandimu" class="min-w-0 flex-1 bg-transparent py-3 outline-none"
                           @if ($errors->has('password') || $errors->has('identifier')) aria-invalid="true" aria-describedby="login-error" @endif>
                    <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false"
                            class="group grid size-9 shrink-0 place-items-center text-slate-400 focus-visible:outline-2 focus-visible:outline-ink">
                        <svg data-eye-show class="size-5 group-aria-pressed:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-hide class="hidden size-5 group-aria-pressed:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6C3.9 8.3 2 12 2 12s3.5 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                @if ($errors->any())
                    <span class="mt-2 block text-xs font-medium text-red-600">{{ $errors->first() }}</span>
                @endif
                <a href="{{ route('password.request') }}" class="mt-2 inline-block text-sm font-semibold text-brand-600">Lupa kata sandi?</a>
            </div>
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <button type="submit" data-login-submit class="w-full rounded-full bg-ink py-3.5 font-semibold text-white disabled:cursor-wait disabled:opacity-60">Masuk</button>
                @if (Route::has('register'))
                    <p class="mt-3 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-ink underline">Daftar</a></p>
                @else
                    <p class="mt-3 text-center text-sm text-slate-500">Belum punya akun? <a role="link" aria-disabled="true" class="cursor-not-allowed font-semibold text-ink underline">Daftar</a></p>
                @endif
            </div>
        </form>
@endcomponent
