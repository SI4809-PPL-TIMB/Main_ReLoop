<x-app-layout>
    <x-slot name="title">Reloop · Akun siap</x-slot>

    <div class="relative mx-auto flex min-h-screen max-w-[420px] flex-col overflow-x-hidden bg-white px-4 pb-[max(20px,env(safe-area-inset-bottom))] pt-6">
        <main class="flex flex-1 flex-col items-center justify-center text-center">
            <img src="{{ asset('assets/illust/auth/account-ready.png') }}" alt="Akun terverifikasi dan siap digunakan" width="224" height="224" class="size-56 object-contain">
            <h1 class="mt-8 text-[28px] font-bold leading-tight">Makasih! Akunmu sudah siap</h1>
            <p class="mt-2 max-w-[300px] text-slate-500">Mulai jual barang bekas atau setor sampah dari desamu. Streak pertamamu menunggu.</p>
        </main>

        <a href="{{ route('lokasi') }}" id="btn-lanjut-lokasi" class="rounded-full bg-ink py-3.5 text-center font-semibold text-white transition hover:bg-ink/90 active:scale-[.98]">
            Lanjut
        </a>
    </div>
</x-app-layout>

