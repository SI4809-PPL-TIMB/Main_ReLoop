<x-app-layout title="ReLoop · Chat" bodyClass="bg-white">
    @php
        $listing = $conversation->listing;
        $other = $conversation->otherUser();
        $messages = $conversation->messages;
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
                    <p class="text-xs text-brand-600 font-medium">Rp {{ number_format($listing->price, 0, ',', '.') }}</p>
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

    {{-- Area Chat (scroll) --}}
    <main id="chat-messages" class="flex flex-col gap-3 px-4 py-4"
          style="min-height: calc(100dvh - 64px - 72px); padding-bottom: 84px;">

        @if ($messages->isEmpty())
            <div class="my-auto flex flex-col items-center justify-center gap-2 py-16 text-center text-slate-400">
                <div class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-2xl">👋</div>
                <p class="text-xs font-medium">Mulai percakapan dengan {{ $other?->name ?? 'penjual' }}</p>
            </div>
        @else
            @foreach ($messages as $msg)
                @if ($msg->sender_id === auth()->id())
                    {{-- Pesan keluar (dari saya) --}}
                    <div class="flex flex-col items-end gap-1 self-end max-w-[78%]">
                        <div class="rounded-2xl rounded-br-sm bg-ink px-4 py-2.5 text-sm text-white break-words">
                            {{ $msg->body }}
                        </div>
                        <div class="flex items-center gap-1 mr-1">
                            <span class="text-[10px] text-slate-400">{{ $msg->created_at->format('H:i') }}</span>
                            @if ($msg->read_at)
                                {{-- Centang dua = sudah dibaca --}}
                                <svg class="size-3.5 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" title="Dibaca">
                                    <path d="M2 12l5 5L17 7"/><path d="M9 12l5 5L23 7"/>
                                </svg>
                            @else
                                {{-- Centang satu = terkirim --}}
                                <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" title="Terkirim">
                                    <path d="M2 12l5 5L17 7"/>
                                </svg>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Pesan masuk (dari lawan bicara) --}}
                    <div class="flex items-end gap-2 self-start max-w-[78%]">
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

    {{-- Input Pesan (sticky di bawah) --}}
    <div class="fixed inset-x-0 bottom-0 mx-auto max-w-[420px] border-t border-slate-200 bg-white px-4 py-3 z-20"
         style="padding-bottom: max(12px, env(safe-area-inset-bottom));">
        <form method="POST" action="{{ route('pesan.kirim', $conversation) }}" class="flex items-center gap-2">
            @csrf
            <input type="text" name="body" placeholder="Ketik pesan…" required autocomplete="off"
                   class="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none transition focus:border-brand-400 focus:bg-white focus:ring-1 focus:ring-brand-400">
            <button type="submit" aria-label="Kirim pesan"
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white shadow-sm transition hover:bg-brand-600 active:scale-95">
                <svg class="size-4 translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="M22 2 11 13"/>
                </svg>
            </button>
        </form>
    </div>

    @push('scripts')
        <script>
            // Auto scroll ke pesan paling bawah saat halaman dimuat
            window.addEventListener('DOMContentLoaded', () => {
                window.scrollTo({
                    top: document.body.scrollHeight,
                    behavior: 'instant'
                });
            });
        </script>
    @endpush
</x-app-layout>
