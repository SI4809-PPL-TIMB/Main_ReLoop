<x-app-layout>
    <x-slot name="title">Reloop · Selamat Datang</x-slot>

    <div class="relative mx-auto flex min-h-screen max-w-[420px] flex-col overflow-x-hidden bg-white px-4 pb-[max(20px,env(safe-area-inset-bottom))] pt-6">

        {{-- Main Slider Section --}}
        <main class="flex flex-1 flex-col justify-center py-8">
            {{-- Slide 1 --}}
            <div data-slide-content="1" class="slide-item">
                <img src="{{ asset('assets/illust/onboarding/jual-beli.png') }}" alt="Jual beli barang bekas dengan aman di sekitar rumah" width="388" height="256" class="h-64 w-full object-contain">
                <div class="mt-8 text-center">
                    <h1 class="text-[28px] font-bold leading-tight">Jual barang bekas di sekitarmu</h1>
                    <p class="mt-2 text-slate-500">Pasang dalam semenit, ketemu pembeli dari desa sebelah. Pembayaran aman lewat Reloop.</p>
                </div>
            </div>

            {{-- Slide 2 --}}
            <div data-slide-content="2" class="slide-item hidden">
                <img src="{{ asset('assets/illust/onboarding/setor-sampah.png') }}" alt="Setor barang daur ulang, timbang, dan terima pembayaran" width="388" height="256" class="h-64 w-full object-contain">
                <div class="mt-8 text-center">
                    <h1 class="text-[28px] font-bold leading-tight">Setor sampah, dibayar sesuai berat</h1>
                    <p class="mt-2 text-slate-500">Pilih pengepul terdekat, minta dijemput atau antar sendiri. Ditimbang, lalu langsung dibayar.</p>
                </div>
            </div>

            {{-- Slide 3 --}}
            <div data-slide-content="3" class="slide-item hidden">
                <img src="{{ asset('assets/illust/onboarding/streak-level.png') }}" alt="Jaga streak dan kumpulkan pencapaian untuk naik level" width="388" height="256" class="h-64 w-full object-contain">
                <div class="mt-8 text-center">
                    <h1 class="text-[28px] font-bold leading-tight">Jaga streak, naik level</h1>
                    <p class="mt-2 text-slate-500">Rutin setor dan jual-beli bikin streak-mu tetap menyala. Kumpulkan badge dan naik level.</p>
                </div>
            </div>

            {{-- Dots Indicator --}}
            <div id="slide-dots" class="mt-6 flex items-center justify-center gap-1.5">
                <button type="button" data-dot="1" aria-label="Slide 1" class="h-2 w-6 rounded-full bg-ink transition-all"></button>
                <button type="button" data-dot="2" aria-label="Slide 2" class="h-2 w-2 rounded-full bg-slate-200 transition-all"></button>
                <button type="button" data-dot="3" aria-label="Slide 3" class="h-2 w-2 rounded-full bg-slate-200 transition-all"></button>
            </div>
        </main>

        {{-- Tombol Navigasi Slide 1 & 2 --}}
        <div id="nav-step12" class="grid gap-2">
            <button type="button" id="btn-lanjut" class="rounded-full bg-ink py-3.5 text-center font-semibold text-white transition hover:bg-ink/90 active:scale-[.98]">
                Lanjut
            </button>
            <button type="button" id="btn-lewati" class="rounded-full py-3 text-center font-semibold text-slate-500 transition hover:text-ink">
                Lewati
            </button>
        </div>

        {{-- Tombol Navigasi Slide 3 --}}
        <div id="nav-step3" class="hidden grid gap-2">
            <a href="{{ route('daftar') }}" id="btn-daftar-onboarding" class="rounded-full bg-ink py-3.5 text-center font-semibold text-white transition hover:bg-ink/90 active:scale-[.98]">
                Daftar
            </a>
            <a href="{{ route('login') }}" class="rounded-full border border-slate-200 py-3.5 text-center font-semibold transition hover:bg-slate-50 active:scale-[.98]">
                Saya sudah punya akun
            </a>
            <p class="mt-1 text-center text-xs text-slate-500">
                Dengan lanjut, kamu setuju dengan <a href="#" class="font-semibold text-ink underline">Syarat &amp; Ketentuan</a> Reloop.
            </p>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentSlide = 1;
            const totalSlides = 3;

            const slides = document.querySelectorAll('[data-slide-content]');
            const dots = document.querySelectorAll('[data-dot]');
            const nav12 = document.getElementById('nav-step12');
            const nav3 = document.getElementById('nav-step3');
            const btnLanjut = document.getElementById('btn-lanjut');
            const btnLewati = document.getElementById('btn-lewati');

            function showSlide(index) {
                currentSlide = index;

                // Tampilkan slide yang aktif
                slides.forEach(slide => {
                    const slideIdx = parseInt(slide.getAttribute('data-slide-content'), 10);
                    slide.classList.toggle('hidden', slideIdx !== currentSlide);
                });

                // Update dots
                dots.forEach(dot => {
                    const dotIdx = parseInt(dot.getAttribute('data-dot'), 10);
                    if (dotIdx === currentSlide) {
                        dot.className = 'h-2 w-6 rounded-full bg-ink transition-all';
                    } else {
                        dot.className = 'h-2 w-2 rounded-full bg-slate-200 transition-all';
                    }
                });

                // Update tombol bawah
                if (currentSlide === 3) {
                    nav12.classList.add('hidden');
                    nav3.classList.remove('hidden');
                } else {
                    nav12.classList.remove('hidden');
                    nav3.classList.add('hidden');
                }
            }

            btnLanjut.addEventListener('click', function () {
                if (currentSlide < totalSlides) {
                    showSlide(currentSlide + 1);
                }
            });

            btnLewati.addEventListener('click', function () {
                showSlide(3);
            });

            dots.forEach(dot => {
                dot.addEventListener('click', function () {
                    const idx = parseInt(this.getAttribute('data-dot'), 10);
                    showSlide(idx);
                });
            });

            // Cek query parameter ?slide=
            const urlParams = new URLSearchParams(window.location.search);
            const slideParam = parseInt(urlParams.get('slide'), 10);
            if (slideParam >= 1 && slideParam <= 3) {
                showSlide(slideParam);
            }
        });
    </script>
    @endpush
</x-app-layout>
