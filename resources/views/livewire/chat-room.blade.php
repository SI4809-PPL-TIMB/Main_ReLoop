<div wire:poll.2000ms class="flex flex-col min-h-screen bg-white">
    @php
        $photo = $listing?->primaryPhoto;
        $uid = auth()->id();
        $isBuyer = (int) $conversation->buyer_id === (int) $uid;
        $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $canOffer = $isBuyer && $listing && ! $pending && ! $accepted;
        $chips = $listing ? collect([.85, .9, .95])->map(fn ($f) => (int) (round($listing->price * $f / 5000) * 5000))
            ->filter(fn ($v) => $v > 0 && $v < $listing->price)->unique()->values() : collect();
    @endphp

    {{-- Header: lawan bicara + kartu barang (ui/chat.html) --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white px-4 py-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('pesan.index') }}" aria-label="Kembali" class="grid size-10 shrink-0 place-items-center rounded-full border border-slate-200 transition hover:bg-slate-50">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            @if ($other?->avatarUrl())
                <img src="{{ $other->avatarUrl() }}" alt="{{ $other->name }}" class="size-10 shrink-0 rounded-full object-cover">
            @else
                <div class="grid size-10 shrink-0 place-items-center rounded-full bg-loop font-bold" title="{{ $other?->name }}">{{ $other?->initials() ?? '?' }}</div>
            @endif
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold leading-tight">{{ $other?->name ?? 'Pengguna' }}</p>
                <p class="truncate text-xs text-slate-500">
                    @if ($other?->profile?->avg_rating > 0)★ {{ number_format($other->profile->avg_rating, 1, ',', '.') }}@endif
                    @if ($other?->profile?->avg_rating > 0 && $other?->profile?->kelurahan) · @endif
                    {{ $other?->profile?->kelurahan?->nama }}
                </p>
            </div>
        </div>

    </header>

    {{-- Kartu barang di bawah header --}}
    <div class="px-4 pt-3">
        @if ($listing)
            <a href="{{ route('barang.detail', $listing) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 p-2">
                <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-100">
                    @if ($photo)
                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $listing->title }}" class="size-full object-cover">
                    @else
                        <img src="{{ asset('assets/illust/categories/'.(file_exists(public_path('assets/illust/categories/'.$listing->category?->slug.'.png')) ? $listing->category->slug : 'perabot').'.png') }}" alt="" class="size-10 object-contain">
                    @endif
                </div>
                <div class="min-w-0 flex-1 text-sm">
                    <p class="truncate font-semibold">{{ $listing->title }}</p>
                    <p class="font-bold text-orange-500">Rp {{ number_format($listing->price, 0, ',', '.') }}</p>
                </div>
            </a>
        @else
            <p class="rounded-xl border border-slate-200 p-3 text-sm text-slate-500">Barang tidak tersedia</p>
        @endif
    </div>

    {{-- Area Chat (scroll) --}}
    <main id="chat-messages-container" class="flex flex-col gap-3 px-4 py-4 text-sm"
          style="min-height: calc(100dvh - 160px); padding-bottom: 84px;">

        @if ($timeline->isEmpty())
            <div class="my-auto flex flex-col items-center justify-center gap-2 py-16 text-center text-slate-400">
                <img src="{{ asset('assets/illust/onboarding/jual-beli.png') }}" alt="" width="160" height="160" class="size-40 object-contain">
                <p class="text-sm font-medium text-slate-500">Mulai percakapan dengan {{ $other?->name ?? 'penjual' }}</p>
            </div>
        @else
            @php $prevDay = null; @endphp
            @foreach ($timeline as $msg)
                @php $day = $msg->created_at->toDateString(); @endphp
                @if ($day !== $prevDay)
                    <p wire:key="day-{{ $day }}-{{ $loop->index }}" class="self-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-500">
                        {{ $msg->created_at->isToday() ? 'Hari ini' : ($msg->created_at->isYesterday() ? 'Kemarin' : $msg->created_at->locale('id')->translatedFormat('d M Y')) }}
                    </p>
                    @php $prevDay = $day; @endphp
                @endif

                @if ($msg instanceof \App\Models\Offer)
                    @php
                        $mine = (int) $msg->offerer_id === (int) $uid;
                        $live = $msg->status === 'menunggu' && $msg->expires_at?->isFuture();
                        $expired = $msg->status === 'dibatalkan' && $msg->expires_at?->isPast();
                        $fromSeller = (int) $msg->offerer_id === (int) $conversation->seller_id;
                        $title = $mine ? ($fromSeller ? 'Tawaran balikmu' : 'Tawaranmu')
                                       : ($fromSeller ? 'Tawaran balik ' . $other?->name : 'Tawaran dari ' . $other?->name);
                    @endphp
                    <div wire:key="offer-{{ $msg->id }}-{{ $msg->status }}"
                         class="w-[84%] rounded-2xl border p-3.5 {{ $mine ? 'self-end' : 'self-start' }} {{ $msg->status === 'diterima' ? 'border-brand-300 bg-brand-50' : 'border-slate-200 bg-white shadow-sm' }}">
                        <p class="text-xs font-medium text-slate-500">{{ $title }}</p>
                        <p class="mt-0.5 text-2xl font-extrabold text-ink">{{ $rp($msg->amount) }}</p>
                        @if ($listing)
                            <p class="text-[11px] text-slate-400">Harga barang <span class="line-through">{{ $rp($listing->price) }}</span></p>
                        @endif

                        @if ($live)
                            <p class="mt-2 text-xs text-amber-700">{{ $mine ? 'Menunggu jawaban' : 'Jawab dalam' }} · kedaluwarsa {{ $msg->expires_at->locale('id')->diffForHumans(['parts' => 1]) }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($mine)
                                    <button type="button" wire:click="cancelOffer({{ $msg->id }})" class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700">Batal</button>
                                @else
                                    <button type="button" wire:click="rejectOffer({{ $msg->id }})" class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700">Tolak</button>
                                    <button type="button" wire:click="openOffer" class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700">Tawar balik</button>
                                    <button type="button" wire:click="acceptOffer({{ $msg->id }})" class="rounded-full bg-ink px-4 py-2 text-xs font-semibold text-white">Terima {{ $rp($msg->amount) }}</button>
                                @endif
                            </div>
                        @elseif ($msg->status === 'diterima')
                            @if ($isBuyer && $listing)
                                <p class="mt-2 text-xs font-semibold text-brand-700">{{ $fromSeller ? 'Kamu' : 'Penjual' }} menerima {{ $rp($msg->amount) }}. Harga ini jadi harga checkout.</p>
                                <a href="{{ route('order.checkout', $listing) }}" class="mt-3 block rounded-full bg-ink py-2.5 text-center text-xs font-semibold text-white">Checkout {{ $rp($msg->amount) }}</a>
                            @else
                                <p class="mt-2 text-xs font-semibold text-brand-700">Kamu menerima tawaran ini. Menunggu {{ $other?->name }} checkout dan bayar.</p>
                            @endif
                        @elseif ($msg->status === 'ditolak')
                            <p class="mt-2 text-xs text-slate-500">{{ $mine ? 'Tawaran ini ditolak.' : 'Kamu menolak tawaran ini.' }}</p>
                        @elseif ($expired)
                            <p class="mt-2 text-xs text-slate-500">Kedaluwarsa karena tidak dijawab dalam 24 jam.</p>
                        @else
                            <p class="mt-2 text-xs text-slate-500">Tawaran dibatalkan.</p>
                        @endif
                    </div>
                @elseif ($msg->sender_id === auth()->id())
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
        @endif

    </main>

    {{-- Sheet Tawar harga --}}
    @if ($showOffer && $listing)
        <div wire:click="closeOffer" class="fixed inset-0 z-30 bg-black/40" aria-hidden="true"></div>
        <div role="dialog" aria-label="Tawar harga" class="fixed inset-x-0 bottom-0 z-40 mx-auto max-w-[420px] rounded-t-3xl bg-white p-4 shadow-2xl"
             style="padding-bottom: max(16px, env(safe-area-inset-bottom));">
            <div class="mx-auto mb-3 h-1 w-9 rounded-full bg-slate-200"></div>
            <div class="flex items-center justify-between">
                <p class="text-base font-bold">Tawar harga</p>
                <button type="button" wire:click="closeOffer" class="text-sm font-semibold text-slate-500">Batal</button>
            </div>
            <p class="mt-1 text-xs text-slate-500">Harga penjual {{ $rp($listing->price) }}</p>
            @if ($chips->isNotEmpty())
                <div class="mt-3 flex gap-2">
                    @foreach ($chips as $c)
                        <button type="button" wire:click="$set('offerAmount', '{{ $c }}')"
                                class="rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ (string) $offerAmount === (string) $c ? 'border-ink bg-ink text-white' : 'border-slate-300 text-slate-700' }}">{{ number_format($c, 0, ',', '.') }}</button>
                    @endforeach
                </div>
            @endif
            <form wire:submit="submitOffer" class="mt-3">
                <div class="flex items-center rounded-xl border border-slate-200 px-3 focus-within:border-brand-400">
                    <span class="text-sm font-semibold text-slate-500">Rp</span>
                    <input type="text" inputmode="numeric" wire:model="offerAmount" placeholder="0" autocomplete="off"
                           class="w-full border-0 bg-transparent px-2 py-3 text-lg font-bold outline-none focus:ring-0">
                </div>
                @error('offerAmount') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-2 text-[11px] text-slate-400">Lawan bicara punya 24 jam untuk menjawab. Tawaran yang diterima jadi harga checkout.</p>
                <button type="submit" class="mt-3 w-full rounded-full bg-ink py-3 text-sm font-semibold text-white">Kirim tawaran</button>
            </form>
        </div>
    @endif

    {{-- Input Pesan (sticky di bawah) --}}
    <div class="fixed inset-x-0 bottom-0 mx-auto max-w-[420px] border-t border-slate-200 bg-white px-4 py-3 z-20"
         style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
        <form wire:submit="sendMessage" class="flex items-center gap-2">
            @if ($canOffer)
                <button type="button" wire:click="openOffer"
                        class="shrink-0 rounded-full border border-slate-300 px-3.5 py-2.5 text-xs font-semibold text-ink hover:bg-slate-50">Tawar</button>
            @endif
            <input type="text" wire:model="body" placeholder="Ketik pesan…" required autocomplete="off"
                   class="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:bg-white focus:ring-1 focus:ring-brand-400">
            <button type="submit" aria-label="Kirim pesan"
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm transition hover:bg-brand-600 active:scale-95 disabled:opacity-50"
                    wire:loading.attr="disabled">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
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

