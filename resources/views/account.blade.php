@component('layouts.app', ['title' => 'ReLoop · Akun', 'nav' => 'user'])
    <x-top-bar title="Akun" />
    <section class="space-y-5 p-4">
        <div>
            <h2 class="text-xl font-bold">{{ auth()->user()->name }}</h2>
            <p class="text-sm text-slate-500">{{ auth()->user()->email ?? auth()->user()->phone }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-full border border-red-200 py-3 font-semibold text-red-700">Keluar</button>
        </form>
    </section>
@endcomponent
