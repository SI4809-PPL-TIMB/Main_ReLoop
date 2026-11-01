
<x-app-layout>
    <x-slot name="title">Reloop · Kode verifikasi</x-slot>

    <header class="sticky top-0 z-20 flex items-center bg-white px-4 py-4">
        <a href="{{ route('daftar') }}"
           aria-label="Kembali"
           class="grid size-10 place-items-center rounded-full border border-slate-200">
            <svg class="size-5" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
            </svg>
        </a>
    </header>

    <main>
        <form id="otp"
              method="POST"
              action="{{ route('verifikasi.store') }}"
              class="space-y-5 px-4 pt-2 pb-36"
              novalidate>
            @csrf

            <div>
                <img src="{{ asset('assets/illust/auth/verification.png') }}"
                     alt="Kode verifikasi untuk keamanan akun"
                     width="128" height="96"
                     class="mb-4 h-24 w-full object-contain">

                <h1 class="text-[28px] font-bold leading-tight">
                    Masukkan kode yang kami kirim
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kode 5 digit dikirim ke
                    <b id="tujuan" class="text-ink">
                        {{ Auth::user()->email
                            ?? \App\Models\User::find(session('pending_user_id'))?->email
                            ?? 'email Anda' }}
                    </b>.
                </p>
            </div>

            <div class="grid grid-cols-5 gap-3">
                @for ($i = 0; $i < 5; $i++)
                    <input
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        {{ $i === 0 ? 'autocomplete=one-time-code' : 'autocomplete=off' }}
                        maxlength="1"
                        aria-label="Digit {{ $i + 1 }}"
                        class="otp aspect-square w-full min-w-0 rounded-2xl border border-slate-200 bg-white text-center text-2xl font-bold outline-none focus:border-ink aria-invalid:border-red-400 aria-invalid:bg-red-50"
                        {{ $errors->has('otp') ? 'aria-invalid=true' : '' }}
                    >
                @endfor
            </div>

            {{-- Nama field disamakan dengan yang dibaca controller --}}
            <input type="hidden" name="otp" id="otp-hidden" value="">

            <div id="salah"
                 role="alert"
                 @if (!$errors->has('otp')) hidden @endif
                 class="flex items-center gap-2 rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                <svg class="size-5 shrink-0" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5M12 16h.01"/>
                </svg>
                {{ $errors->first('otp') ?: 'Kode verifikasi salah. Coba lagi.' }}
            </div>

            @if (session('success'))
                <p role="status" class="text-sm text-green-700">
                    {{ session('success') }}
                </p>
            @endif

            <p class="text-sm text-slate-500">
                <span id="tunggu">
                    Belum terima kode? Kirim ulang dalam
                    <b id="detik" class="text-ink">01:00</b>
                </span>

                <button type="button"
                        id="ulang"
                        hidden
                        class="font-semibold text-brand-600 underline">
                    Kirim ulang kode
                </button>
            </p>

            <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3">
                <button id="lanjut"
                        type="submit"
                        class="w-full rounded-full bg-ink py-3.5 font-semibold text-white disabled:opacity-40"
                        disabled>
                    Verifikasi
                </button>
            </div>
        </form>

        <form id="form-resend"
              method="POST"
              action="{{ route('verifikasi.resend') }}"
              class="hidden">
            @csrf
        </form>
    </main>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const otp = Array.from(document.querySelectorAll('.otp'));
            const salah = document.getElementById('salah');
            const lanjut = document.getElementById('lanjut');
            const hiddenInput = document.getElementById('otp-hidden');
            const form = document.getElementById('otp');

            const nilai = () => otp.map(input => input.value).join('');

            const tandai = (bad) => {
                if (salah) salah.hidden = !bad;

                otp.forEach(input => {
                    if (bad) {
                        input.setAttribute('aria-invalid', 'true');
                    } else {
                        input.removeAttribute('aria-invalid');
                    }
                });
            };

            const cek = () => {
                const val = nilai();
                hiddenInput.value = val;
                lanjut.disabled = !/^[0-9]{5}$/.test(val);
            };

            otp.forEach((el, i) => {
                el.addEventListener('input', () => {
                    el.value = el.value.replace(/\D/g, '').slice(-1);
                    tandai(false);

                    if (el.value && otp[i + 1]) {
                        otp[i + 1].focus();
                    }

                    cek();
                });

                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !el.value && otp[i - 1]) {
                        otp[i - 1].focus();
                        otp[i - 1].value = '';
                        tandai(false);
                        cek();
                    }
                });

                el.addEventListener('paste', (e) => {
                    const t = (e.clipboardData.getData('text') || '')
                        .replace(/\D/g, '')
                        .slice(0, 5);

                    if (!t) return;

                    e.preventDefault();

                    otp.forEach((input, k) => {
                        input.value = t[k] || '';
                    });

                    otp[Math.min(t.length, 4)].focus();
                    tandai(false);
                    cek();
                });
            });

            form.addEventListener('submit', (e) => {
                hiddenInput.value = nilai();

                if (!/^[0-9]{5}$/.test(hiddenInput.value)) {
                    e.preventDefault();
                    tandai(true);
                }
            });

            if (otp[0]) {
                otp[0].focus();
            }

            // Hitung mundur pengiriman ulang
            let s = 60;

            const p = x => String(x).padStart(2, '0');
            const detikEl = document.getElementById('detik');
            const tungguEl = document.getElementById('tunggu');
            const ulangEl = document.getElementById('ulang');

            const tick = () => {
                if (detikEl) {
                    detikEl.textContent =
                        `${p(Math.floor(s / 60))}:${p(s % 60)}`;
                }

                if (s > 0) {
                    s--;
                    setTimeout(tick, 1000);
                } else {
                    if (tungguEl) tungguEl.hidden = true;
                    if (ulangEl) ulangEl.hidden = false;
                }
            };

            tick();

            if (ulangEl) {
                ulangEl.addEventListener('click', () => {
                    document.getElementById('form-resend').submit();
                });
            }
        });
    </script>
    @endpush
</x-app-layout>