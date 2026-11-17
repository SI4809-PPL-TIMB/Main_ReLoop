<x-app-layout title="ReLoop · Pesan" :nav="'chat'">
    <header class="sticky top-0 z-20 bg-white px-4 pt-5">
        <h1 class="text-2xl font-bold">Pesan</h1>
        <label class="mt-4 flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2.5 focus-within:border-ink">
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="cari-pesan" type="search" placeholder="Cari nama atau barang" aria-label="Cari percakapan" class="w-full bg-transparent text-sm outline-none">
        </label>
        <div id="filter-pesan" class="no-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 pb-3 text-sm font-medium">
            @foreach (['semua' => 'Semua', 'jual' => 'Jual', 'beli' => 'Beli'] as $k => $nama)
                <button type="button" data-filter="{{ $k }}" class="shrink-0 rounded-full px-4 py-2 {{ $k === 'semua' ? 'bg-ink text-white' : 'bg-slate-100' }}">{{ $nama }}</button>
            @endforeach
        </div>
    </header>

    <main class="pb-6">
        @if ($conversations->isEmpty())
            <div class="flex flex-col items-center justify-center gap-4 px-8 py-24 text-center">
                <div class="flex size-20 items-center justify-center rounded-full bg-slate-100 text-4xl">💬</div>
                <div>
                    <p class="font-semibold text-ink">Belum ada pesan</p>
                    <p class="mt-1 text-sm text-slate-500">Chat dengan penjual atau pembeli muncul di sini. Mulai dari tombol Chat di detail barang.</p>
                </div>
                <a href="{{ route('cari') }}" class="mt-2 inline-flex rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">Mulai jelajah</a>
            </div>
        @else
            <ul id="daftar-pesan" class="divide-y divide-slate-100">
                @foreach ($conversations as $conv)
                    @php
                        $listing = $conv->listing;
                        $other = $conv->otherUser();
                        $latest = $conv->latestMessage;
                        $jenis = (int) $conv->buyer_id === (int) auth()->id() ? 'beli' : 'jual';
                        $tawar = $conv->offers->sortByDesc('id')->first();
                        $waktu = ($latest?->created_at ?? $conv->updated_at);
                    @endphp
                    <li data-jenis="{{ $jenis }}" data-teks="{{ strtolower(($other?->name ?? '').' '.($listing?->title ?? '')) }}">
                        <a href="{{ route('pesan.show', $conv) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50 active:bg-slate-100">
                            <div class="relative shrink-0">
                                @if ($other?->avatarUrl())
                                    <img src="{{ $other->avatarUrl() }}" alt="" class="size-12 rounded-full object-cover">
                                @else
                                    <div class="grid size-12 place-items-center rounded-full bg-loop-soft text-sm font-bold text-loop-dark">{{ $other?->initials() ?? '?' }}</div>
                                @endif
                                <img src="{{ asset('assets/illust/'.($jenis === 'beli' ? 'chat-beli' : 'promo-jual').'.png') }}" class="absolute -bottom-1 -right-1 size-5 rounded-full bg-white object-contain ring-2 ring-white" width="20" height="20" alt="{{ ucfirst($jenis) }}" title="{{ ucfirst($jenis) }}">
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="truncate font-bold">{{ $other?->name ?? 'Pengguna' }}</p>
                                    <span class="shrink-0 text-xs {{ $conv->unread_count > 0 ? 'font-semibold text-ink' : 'text-slate-400' }}">{{ $waktu->isToday() ? $waktu->format('H.i') : $waktu->locale('id')->translatedFormat('j M') }}</span>
                                </div>
                                <p class="truncate text-xs text-slate-500">{{ $listing?->title ?? 'Barang tidak tersedia' }}</p>
                                @if ($tawar)
                                    <p class="truncate text-xs font-semibold text-amber-600">Tawaran Rp {{ number_format($tawar->amount, 0, ',', '.') }} · menunggu</p>
                                @endif
                                <div class="mt-0.5 flex items-center justify-between gap-2">
                                    <p class="truncate text-sm {{ $conv->unread_count > 0 ? 'font-medium text-ink' : 'text-slate-500' }}">
                                        @if ($latest)
                                            @if ((int) $latest->sender_id === (int) auth()->id())<span class="text-slate-400">Kamu:</span> @endif{{ $latest->body }}
                                        @else
                                            <span class="italic text-slate-400">Percakapan baru dibuat</span>
                                        @endif
                                    </p>
                                    @if ($conv->unread_count > 0)
                                        <span class="grid min-w-5 shrink-0 place-items-center rounded-full bg-loop px-1.5 text-[11px] font-bold leading-5 text-ink">{{ $conv->unread_count }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p id="pesan-kosong" hidden class="px-6 py-16 text-center text-sm text-slate-500">Tidak ada percakapan yang cocok.</p>

            <script>
                (() => {
                    const items = [...document.querySelectorAll('#daftar-pesan > li')];
                    const input = document.getElementById('cari-pesan');
                    const chips = [...document.querySelectorAll('#filter-pesan [data-filter]')];
                    let f = 'semua';
                    const apply = () => {
                        const q = input.value.trim().toLowerCase();
                        let n = 0;
                        items.forEach((li) => {
                            const ok = (f === 'semua' || li.dataset.jenis === f) && li.dataset.teks.includes(q);
                            li.hidden = !ok;
                            n += ok;
                        });
                        document.getElementById('pesan-kosong').hidden = n > 0;
                    };
                    chips.forEach((c) => c.addEventListener('click', () => {
                        f = c.dataset.filter;
                        chips.forEach((x) => {
                            const on = x === c;
                            x.classList.toggle('bg-ink', on); x.classList.toggle('text-white', on); x.classList.toggle('bg-slate-100', !on);
                        });
                        apply();
                    }));
                    input.addEventListener('input', apply);
                })();
            </script>
        @endif
    </main>
</x-app-layout>
