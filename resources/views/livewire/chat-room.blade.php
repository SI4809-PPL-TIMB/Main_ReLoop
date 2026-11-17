<div wire:poll.2000ms class="flex flex-col min-h-screen bg-white">
    @php
        $photo = $listing?->primaryPhoto;
    @endphp

    {{-- Top Bar: Tombol kembali + Info barang + Avatar lawan bicara --}}
    <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3">
        {{-- Tombol Kembali --}}
        <a href="{{ route('pesan.index') }}" aria-label="Kembali"
           class="flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-200 text-ink transition hover:bg-slate-50">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </a>

        {{-- Info Barang (mini card di header) --}}
        @if ($listing)
            <a href="{{ route('barang.detail', $listing) }}" class="flex min-w-0 flex-1 items-center gap-2.5 overflow-hidden">
                <div class="size-10 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center">
                    @if ($photo)
                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $listing->title }}" class="size-full object-cover">
                    @else
                        <span class="text-xl">📦</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-ink">{{ $listing->title }}</p>
                    @if ($acceptedOffer)
                        <p class="text-xs text-brand-600 font-medium">Harga deal: Rp {{ number_format($acceptedOffer->amount, 0, ',', '.') }}</p>
                    @else
                        <p class="text-xs text-brand-600 font-medium">Rp {{ number_format($listing->price, 0, ',', '.') }}</p>
                    @endif
                </div>
            </a>
        @else
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink">Barang tidak tersedia</p>
            </div>
        @endif

        {{-- Avatar lawan bicara --}}
        @if ($other?->avatarUrl())
            <img src="{{ $other->avatarUrl() }}" alt="{{ $other->name }}" class="size-9 shrink-0 rounded-full object-cover border border-brand-300">
        @else
            <div class="shrink-0 flex size-9 items-center justify-center rounded-full bg-brand-100 border border-brand-300 text-xs font-bold text-brand-800" title="{{ $other?->name }}">
                {{ $other?->initials() ?? '?' }}
            </div>
        @endif
    </header>

    {{-- ===== Banner tawaran aktif / diterima ===== --}}
    @if ($acceptedOffer)
        <div role="status" class="flex items-center gap-3 border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            <svg class="size-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="m9 12 2 2 4-4"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="font-semibold">Tawaran disepakati!</p>
                <p class="text-xs text-emerald-700">Harga deal: <b>Rp {{ number_format($acceptedOffer->amount, 0, ',', '.') }}</b> · Lanjutkan ke pembayaran</p>
            </div>
        </div>
    @elseif ($activeOffer)
        <div role="status" class="flex items-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <svg class="size-5 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            </svg>
            <div class="flex-1 min-w-0">
                <p class="font-semibold">Tawaran menunggu respons</p>
                <p class="text-xs text-amber-700">
                    Rp {{ number_format($activeOffer->amount, 0, ',', '.') }} ·
                    Kedaluwarsa {{ $activeOffer->expires_at?->locale('id')->diffForHumans() ?? '24 jam' }}
                </p>
            </div>
        </div>
    @endif

    {{-- Area Chat (scroll) --}}
    <main id="chat-messages-container" class="flex flex-col gap-3 px-4 py-4"
          style="min-height: calc(100dvh - 64px - 72px); padding-bottom: 84px;">

        @if ($messages->isEmpty() && $offers->isEmpty())
            <div class="my-auto flex flex-col items-center justify-center gap-2 py-16 text-center text-slate-400">
                <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-2xl">👋</div>
                <p class="text-xs font-medium">Mulai percakapan dengan {{ $other?->name ?? 'penjual' }}</p>
            </div>
        @else
            {{-- Pesan teks --}}
            @foreach ($messages as $msg)
                @if ($msg->sender_id === auth()->id())
                    {{-- Pesan keluar (dari saya) --}}
                    <div wire:key="msg-{{ $msg->id }}" class="flex flex-col items-end gap-1 self-end max-w-[78%]">
                        <div class="rounded-2xl rounded-br-sm bg-ink px-4 py-2.5 text-sm text-white break-words">
                            {{ $msg->body }}
                        </div>
                        <div class="flex items-center gap-1 mr-1">
                            <span class="text-[10px] text-slate-400">{{ $msg->created_at->format('H:i') }}</span>
                            @if ($msg->read_at)
                                <svg class="size-3.5 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" title="Dibaca">
                                    <path d="M2 12l5 5L17 7"/><path d="M9 12l5 5L23 7"/>
                                </svg>
                            @else
                                <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" title="Terkirim">
                                    <path d="M2 12l5 5L17 7"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Pesan masuk (dari lawan bicara) --}}
                    <div wire:key="msg-{{ $msg->id }}" class="flex items-end gap-2 self-start max-w-[78%]">
                        @if ($other?->avatarUrl())
                            <img src="{{ $other->avatarUrl() }}" alt="{{ $other->name }}" class="size-7 shrink-0 rounded-full object-cover">
                        @else
                            <div class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[10px] font-bold text-brand-800">
                                {{ $other?->initials() ?? '?' }}
                            </div>
                        @endif
                        <div class="space-y-1">
                            <div class="rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-2.5 text-sm text-ink break-words">
                                {{ $msg->body }}
                            </div>
                            <span class="ml-1 block text-[10px] text-slate-400">{{ $msg->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- ===== 6.2.1 — Bubble Tawaran Harga ===== --}}
            @foreach ($offers as $offer)
                @php
                    $isOlehSaya = $offer->offerer_id === auth()->id();
                    $statusLabel = match ($offer->status) {
                        'menunggu'    => 'Menunggu respons',
                        'diterima'    => 'Diterima ✓',
                        'ditolak'     => 'Ditolak',
                        'kedaluwarsa' => 'Kedaluwarsa',
                        'dibatalkan'  => 'Dibatalkan',
                        default       => $offer->status,
                    };
                    $statusColor = match ($offer->status) {
                        'menunggu'    => 'text-amber-700 bg-amber-50 border-amber-200',
                        'diterima'    => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                        'ditolak'     => 'text-rose-700 bg-rose-50 border-rose-200',
                        'kedaluwarsa' => 'text-slate-500 bg-slate-50 border-slate-200',
                        'dibatalkan'  => 'text-slate-400 bg-slate-50 border-slate-200',
                        default       => 'text-slate-700 bg-white border-slate-200',
                    };
                    $isAktif = $offer->status === 'menunggu'
                        && ($offer->expires_at === null || $offer->expires_at->isFuture());
                @endphp

                <div wire:key="offer-{{ $offer->id }}"
                     class="flex flex-col {{ $isOlehSaya ? 'items-end self-end' : 'items-start self-start' }} max-w-[82%] gap-1">

                    {{-- Label "Tawaran" --}}
                    <span class="px-2 text-[10px] font-semibold text-slate-400 uppercase tracking-wide">
                        {{ $isOlehSaya ? 'Tawaranmu' : 'Tawaran ' . ($offer->offerer?->name ?? 'pembeli') }}
                    </span>

                    {{-- Kartu tawaran --}}
                    <div class="rounded-2xl {{ $isOlehSaya ? 'rounded-br-sm' : 'rounded-bl-sm' }} border {{ $statusColor }} px-4 py-3 text-sm min-w-[200px]">
                        <div class="flex items-center gap-2 justify-between">
                            <div>
                                <p class="text-[11px] font-medium opacity-70">Tawaran harga</p>
                                <p class="text-lg font-bold mt-0.5">Rp {{ number_format($offer->amount, 0, ',', '.') }}</p>
                            </div>
                            <svg class="size-7 opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                <path d="M12 6v6l4 2"/>
                            </svg>
                        </div>

                        {{-- Status badge --}}
                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <span class="text-[11px] font-semibold">{{ $statusLabel }}</span>
                            @if ($offer->expires_at && $offer->status === 'menunggu')
                                <span class="text-[10px] opacity-60">
                                    {{ $offer->expires_at->isFuture() ? 'Berakhir ' . $offer->expires_at->locale('id')->diffForHumans() : 'Sudah kedaluwarsa' }}
                                </span>
                            @endif
                        </div>

                        {{-- Tombol Terima / Tolak (hanya penjual, hanya jika tawaran masih aktif) --}}
                        @if (! $isPembeli && $isAktif)
                            <div class="mt-3 flex gap-2">
                                <button wire:click="tolakTawar({{ $offer->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex-1 rounded-full border border-current py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition active:scale-95 disabled:opacity-50">
                                    Tolak
                                </button>
                                <button wire:click="terimaTawar({{ $offer->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex-1 rounded-full bg-emerald-600 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition active:scale-95 disabled:opacity-50">
                                    Terima
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Waktu --}}
                    <span class="px-1 text-[10px] text-slate-400">{{ $offer->created_at->format('H:i') }}</span>
                </div>
            @endforeach
        @endif

    </main>

    {{-- ===== 6.2.1 — Form Tawaran Harga (hanya pembeli, barang aktif & negotiable) ===== --}}
    @if ($isPembeli && $listing && $listing->status === \App\Models\Listing::AKTIF && $listing->is_negotiable && ! $acceptedOffer)
        <div class="fixed inset-x-0 bottom-[72px] mx-auto max-w-[420px] z-10 px-4"
             x-data x-show="$wire.showTawarForm" x-transition>
            <div class="rounded-2xl border border-slate-200 bg-white shadow-lg p-4">
                <p class="text-sm font-semibold mb-3">Ajukan tawaran harga</p>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-500 shrink-0">Rp</span>
                    <input type="text" wire:model="tawarAmount"
                           placeholder="Contoh: 250000"
                           inputmode="numeric"
                           class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm outline-none transition focus:border-brand-400 focus:ring-1 focus:ring-brand-400"
                           wire:keydown.enter="ajukanTawar">
                </div>
                @error('tawarAmount')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-[11px] text-slate-400">Harga listing: Rp {{ number_format($listing->price, 0, ',', '.') }}</p>
                <div class="mt-3 flex gap-2">
                    <button wire:click="toggleTawarForm"
                            class="flex-1 rounded-full border border-slate-200 py-2 text-sm font-semibold hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button wire:click="ajukanTawar"
                            wire:loading.attr="disabled"
                            class="flex-1 rounded-full bg-ink py-2 text-sm font-semibold text-white hover:bg-slate-800 transition active:scale-95 disabled:opacity-50">
                        Kirim tawaran
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Input Pesan (sticky di bawah) --}}
    <div class="fixed inset-x-0 bottom-0 mx-auto max-w-[420px] border-t border-slate-200 bg-white px-4 py-3 z-20"
         style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
        <form wire:submit="sendMessage" class="flex items-center gap-2">
            {{-- Tombol Tawar (hanya untuk pembeli, barang aktif negotiable, belum ada deal) --}}
            @if ($isPembeli && $listing && $listing->status === \App\Models\Listing::AKTIF && $listing->is_negotiable && ! $acceptedOffer)
                <button type="button" wire:click="toggleTawarForm"
                        aria-label="Tawar harga"
                        class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:bg-slate-50 active:scale-95 {{ $showTawarForm ? 'bg-loop-soft border-loop text-loop-dark' : '' }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                        <path d="M15 9H9M15 12h-3M9 15h6"/>
                    </svg>
                </button>
            @endif

            <input type="text" wire:model="body" placeholder="Ketik pesan…" autocomplete="off"
                   class="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:bg-white focus:ring-1 focus:ring-brand-400">
            <button type="submit" aria-label="Kirim pesan"
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm transition hover:bg-brand-600 active:scale-95 disabled:opacity-50"
                    wire:loading.attr="disabled">
                <svg class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="M22 2 11 13"/>
                </svg>
            </button>
        </form>
    </div>

    <script>
        (() => {
            const scrollToBottom = () => {
                window.scrollTo({
                    top: document.body.scrollHeight,
                    behavior: 'smooth'
                });
            };

            document.addEventListener('DOMContentLoaded', scrollToBottom);

            window.addEventListener('message-sent', () => {
                setTimeout(scrollToBottom, 50);
            });

            const observer = new MutationObserver(() => {
                const distanceFromBottom = document.documentElement.scrollHeight - window.innerHeight - window.scrollY;
                if (distanceFromBottom < 250) {
                    scrollToBottom();
                }
            });

            const container = document.getElementById('chat-messages-container');
            if (container) {
                observer.observe(container, { childList: true, subtree: true });
            }
        })();
    </script>
</div>
