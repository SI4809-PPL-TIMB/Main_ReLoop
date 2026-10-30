@component('layouts.app', ['title' => 'ReLoop · Akun', 'nav' => 'user'])
    <x-top-bar title="Akun" :border="false" />

    <section class="space-y-4 px-4 pb-8 pt-2">
        {{-- Flash Notification --}}
        @if (session('sukses'))
            <div role="alert" class="flex items-center gap-2.5 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800 shadow-sm animate-fade-in">
                <svg class="size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="font-medium">{{ session('sukses') }}</span>
            </div>
        @endif

        {{-- Kartu Utama Profil Pengguna --}}
        <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex items-start gap-4">
                {{-- Foto Avatar / Inisial --}}
                <div class="relative shrink-0">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="size-20 rounded-full border-2 border-brand-500 object-cover shadow-sm">
                    @else
                        <div class="flex size-20 items-center justify-center rounded-full border-2 border-brand-500 bg-brand-100 text-xl font-bold uppercase tracking-wider text-brand-800 shadow-sm" aria-label="Avatar inisial: {{ $user->initials() }}">
                            {{ $user->initials() }}
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 flex size-5 items-center justify-center rounded-full border-2 border-white bg-brand-500 text-[10px] text-white" title="Aktif">
                        ✓
                    </span>
                </div>

                {{-- Detail Info Pengguna --}}
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-xl font-bold text-ink">{{ $user->name }}</h2>
                    <p class="truncate text-xs text-slate-500 mt-0.5">{{ $user->email ?? $user->phone }}</p>
                    @if ($user->phone && $user->email)
                        <p class="truncate text-xs text-slate-400">{{ $user->phone }}</p>
                    @endif

                    @php($kel = $profil?->kelurahan)
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                        <svg class="size-3 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span class="truncate max-w-[140px]">{{ $kel ? $kel->nama : 'Belum atur lokasi' }}</span>
                    </div>
                </div>
            </div>

            @if ($profil?->bio)
                <div class="mt-3.5 rounded-xl border border-slate-100 bg-slate-50/80 p-3 text-xs leading-relaxed text-slate-600">
                    <p class="italic">“{{ $profil->bio }}”</p>
                </div>
            @endif

            {{-- Tombol Navigasi ke Form Ubah Profil --}}
            <a href="{{ route('account.edit') }}" class="mt-4 flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white py-2.5 text-xs font-semibold text-ink shadow-sm transition hover:bg-slate-50 active:scale-[0.99]">
                <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                </svg>
                <span>Ubah Profil Lengkap</span>
            </a>
        </div>

        {{-- Kartu Gamifikasi / Statistik ReLoop --}}
        <div>
            <div class="mb-2 flex items-center justify-between px-1">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Pencapaian ReLoop</h3>
                <span class="text-[11px] font-medium text-brand-700">Sprint 1</span>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                {{-- Level --}}
                <div class="flex items-center gap-3 rounded-2xl border border-loop/40 bg-loop-soft p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg shadow-sm">
                        🎖️
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-bold text-ink">Level {{ $profil?->level ?? 1 }}</span>
                        <span class="block text-[11px] text-slate-600 truncate">{{ ($profil?->level ?? 1) >= 2 ? 'Penjual Aktif' : 'Warga Baru' }}</span>
                    </div>
                </div>

                {{-- Poin --}}
                <div class="flex items-center gap-3 rounded-2xl border border-amber-200/60 bg-amber-50/70 p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg shadow-sm">
                        🪙
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-bold text-amber-950">{{ number_format($profil?->total_points ?? 0) }} Poin</span>
                        <span class="block text-[11px] text-amber-700 truncate">Dapat ditukar</span>
                    </div>
                </div>

                {{-- Streak --}}
                <div class="flex items-center gap-3 rounded-2xl border border-orange-200/60 bg-orange-50/70 p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg shadow-sm">
                        🔥
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-bold text-orange-950">{{ $profil?->streak_count ?? 0 }} Hari</span>
                        <span class="block text-[11px] text-orange-700 truncate">Streak aktif</span>
                    </div>
                </div>

                {{-- Rating & Terjual --}}
                <div class="flex items-center gap-3 rounded-2xl border border-brand-200/60 bg-brand-50/70 p-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg shadow-sm">
                        ⭐
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-bold text-brand-950">{{ number_format((float)($profil?->avg_rating ?? 0), 1) }}</span>
                        <span class="block text-[11px] text-brand-700 truncate">{{ $profil?->total_sold ?? 0 }} barang terjual</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Menu Pengaturan & Navigasi --}}
        <div>
            <h3 class="mb-2 px-1 text-xs font-bold uppercase tracking-wider text-slate-500">Menu & Pengaturan</h3>

            <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                {{-- Ubah Profil Menu --}}
                <a href="{{ route('account.edit') }}" class="flex items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="grid size-9 place-items-center rounded-full bg-slate-100 text-slate-600">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-sm font-semibold text-ink">Ubah Data Diri</span>
                            <span class="block text-xs text-slate-500">Nama, foto profil, bio, dan nomor HP</span>
                        </div>
                    </div>
                    <svg class="size-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>

                {{-- Lokasi Saya Menu --}}
                <a href="{{ route('lokasi', ['from' => 'profil']) }}" id="breadcrumb-lokasi" class="flex items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="grid size-9 place-items-center rounded-full bg-brand-50 text-brand-700">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-sm font-semibold text-ink">Lokasi Saya</span>
                            <span class="block text-xs text-slate-500 truncate">{{ $kel ? $kel->breadcrumb() : 'Atur lokasi tempat tinggal' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-xs font-semibold text-brand-700">{{ $kel ? 'Ubah' : 'Atur' }}</span>
                        <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </div>
                </a>

                {{-- Ketentuan & Bantuan --}}
                <a href="{{ route('home') }}" class="flex items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="grid size-9 place-items-center rounded-full bg-slate-100 text-slate-600">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                <path d="M12 17h.01"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-sm font-semibold text-ink">Bantuan & FAQ</span>
                            <span class="block text-xs text-slate-500">Pertanyaan umum tentang ReLoop</span>
                        </div>
                    </div>
                    <svg class="size-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Tombol Logout --}}
        <div class="pt-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-full border border-red-200 bg-red-50/50 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 hover:border-red-300 active:scale-[.98]">
                    <svg class="size-4 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Keluar dari Akun</span>
                </button>
            </form>
            <p class="mt-3 text-center text-[11px] text-slate-400">ReLoop App · Versi Sprint 1</p>
        </div>
    </section>
@endcomponent
