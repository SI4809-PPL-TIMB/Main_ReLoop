<x-app-layout title="ReLoop · Pesan" :nav="'chat'">
    <x-top-bar title="Pesan" :border="false" />

    <section class="pb-6 pt-1">
        @if ($conversations->isEmpty())
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center gap-4 px-8 py-24 text-center">
                <div class="flex size-20 items-center justify-center rounded-full bg-slate-100 text-4xl">💬</div>
                <div>
                    <p class="font-semibold text-ink">Belum ada percakapan</p>
                    <p class="mt-1 text-sm text-slate-500">Mulai chat dengan mengetuk tombol Chat di halaman detail barang.</p>
                </div>
                <a href="{{ route('cari') }}" class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ink px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-ink/90 active:scale-95">
                    Cari Barang
                </a>
            </div>
        @else
            {{-- Daftar Percakapan Dinamis --}}
            <ul class="divide-y divide-slate-100">
                @foreach ($conversations as $conv)
                    @php
                        $listing = $conv->listing;
                        $other = $conv->otherUser();
                        $latest = $conv->latestMessage;
                        $photo = $listing?->primaryPhoto;
                    @endphp
                    <li>
                        <a href="{{ route('pesan.show', $conv) }}" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-slate-50 active:bg-slate-100">
                            {{-- Foto Barang --}}
                            <div class="relative shrink-0">
                                <div class="size-14 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 flex items-center justify-center">
                                    @if ($photo)
                                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $listing?->title }}" class="size-full object-cover">
                                    @else
                                        <span class="text-2xl">📦</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Info Percakapan --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm font-semibold text-ink">{{ $listing?->title ?? 'Barang' }}</span>
                                    @if ($latest)
                                        <span class="shrink-0 text-[11px] {{ $conv->unread_count > 0 ? 'font-medium text-brand-700' : 'text-slate-400' }}">
                                            {{ $latest->created_at->format('H:i') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between gap-2 mt-0.5">
                                    <p class="truncate text-xs {{ $conv->unread_count > 0 ? 'font-medium text-ink' : 'text-slate-400' }}">
                                        @if ($latest)
                                            @if ($latest->sender_id === auth()->id())
                                                <span class="text-slate-500">Kamu:</span> {{ $latest->body }}
                                            @else
                                                {{ $latest->body }}
                                            @endif
                                        @else
                                            <span class="italic text-slate-400">Percakapan baru dibuat</span>
                                        @endif
                                    </p>
                                    @if ($conv->unread_count > 0)
                                        <span class="ml-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white">
                                            {{ $conv->unread_count }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1 flex items-center gap-1.5">
                                    @if ($other?->avatarUrl())
                                        <img src="{{ $other->avatarUrl() }}" alt="{{ $other->name }}" class="size-4 rounded-full object-cover">
                                    @else
                                        <div class="flex size-4 items-center justify-center rounded-full bg-brand-100 text-[8px] font-bold text-brand-800">
                                            {{ $other?->initials() ?? '?' }}
                                        </div>
                                    @endif
                                    <span class="text-[11px] text-slate-400">{{ $other?->name ?? 'Pengguna' }}</span>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-layout>
