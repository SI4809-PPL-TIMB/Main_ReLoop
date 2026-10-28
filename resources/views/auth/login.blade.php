@component('layouts.app', ['title' => 'ReLoop · Masuk'])
    <div class="pb-32">
        <x-top-bar :back="url('/')" :border="false" />
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5 px-4 pt-2">
            @csrf
            <div>
                <h1 class="text-[28px] font-bold leading-tight">Masuk</h1>
                <p class="mt-1 text-sm text-slate-500">Senang ketemu lagi. Lanjutkan streak-mu.</p>
            </div>
            @if ($errors->any())
                <div id="login-error" role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif
            <div>
                <label for="identifier" class="text-sm font-semibold">Email atau nomor HP</label>
                <input id="identifier" name="identifier" type="text" required maxlength="255" autocomplete="username"
                       value="{{ old('identifier') }}" placeholder="nama@email.com / 08xx"
                       @if ($errors->any()) aria-invalid="true" aria-describedby="login-error" @endif
                       class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:border-ink aria-[invalid=true]:border-red-500">
            </div>
            <div>
                <label for="password" class="text-sm font-semibold">Kata sandi</label>
                <div class="mt-2 flex items-center rounded-xl border border-slate-200 pl-4 pr-1 focus-within:border-ink">
                    <input id="password" name="password" type="password" required maxlength="1024" autocomplete="current-password"
                           placeholder="Kata sandimu" class="min-w-0 flex-1 bg-transparent py-3 outline-none"
                           @if ($errors->any()) aria-invalid="true" aria-describedby="login-error" @endif>
                    <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false"
                            class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 focus-visible:outline-2 focus-visible:outline-ink">Tampilkan</button>
                </div>
            </div>
            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[420px] border-t border-slate-200 bg-white px-4 pt-3 pb-[max(14px,env(safe-area-inset-bottom))]">
                <button type="submit" class="w-full rounded-full bg-ink py-3.5 font-semibold text-white">Masuk</button>
            </div>
        </form>
    </div>
@endcomponent
