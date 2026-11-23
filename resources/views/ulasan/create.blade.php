<x-app-layout>
    <x-slot name="title">Reloop · Beri Ulasan</x-slot>

    @php
        $listing = $order->listing;
        $penjual = $order->seller;
        $foto = $listing?->primaryPhoto;
        $fotoUrl = $foto ? asset('storage/' . $foto->file_path) : null;
        $tanggalSelesai = $order->buyer_confirmed_at ? $order->buyer_confirmed_at->locale('id')->translatedFormat('j M') : now()->locale('id')->translatedFormat('j M');
    @endphp

    <div class="relative mx-auto min-h-screen max-w-[420px] bg-white pb-28 shadow-sm">
        {{-- Header: Tombol kembali minimalis sesuai mockup --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 bg-white px-4 py-4">
            <a href="{{ route('order.show', $order->order_number) }}" aria-label="Kembali" class="grid size-10 place-items-center rounded-full border border-slate-200 hover:bg-slate-50 transition">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        </header>

        <main class="px-4 pt-1">
            {{-- Kartu Info Pesanan / Penjual --}}
            <section class="flex items-center gap-3 rounded-2xl border border-slate-200 p-3.5 bg-white">
                <div class="size-12 shrink-0 overflow-hidden rounded-xl border border-slate-100 bg-slate-50 flex items-center justify-center text-slate-400">
                    @if ($fotoUrl)
                        <img src="{{ $fotoUrl }}" alt="{{ $listing?->title }}" class="size-full object-cover">
                    @else
                        <svg class="size-6 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-sm text-ink">{{ $penjual?->name ?? 'Penjual' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Pesanan selesai · {{ $tanggalSelesai }}
                        @if ($listing)
                            <span class="sr-only">{{ $listing->title }}</span>
                        @endif
                    </p>
                </div>
            </section>

            {{-- Form Ulasan --}}
            <form action="{{ route('ulasan.store', $order->order_number) }}" method="POST" id="form-ulasan" class="mt-6 space-y-6">
                @csrf

                {{-- Bintang Rating 1-5 --}}
                <div class="text-center pt-2">
                    <label class="block text-sm font-bold text-ink">Bagaimana pengalamanmu?</label>

                    <div class="mt-3 flex justify-center gap-2 text-4xl text-slate-200" id="star-container">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" data-star="{{ $i }}" class="star-btn transition hover:scale-110 focus:outline-none" aria-label="{{ $i }} Bintang">★</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="rating-val" value="{{ old('rating', '') }}">
                    <p id="rating-label" class="mt-2 text-sm font-bold text-ink">Pilih bintang</p>
                    @error('rating')
                        <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Chips: Yang kamu suka --}}
                <div>
                    <label class="block text-sm font-bold text-ink mb-2.5">Yang kamu suka</label>
                    <div class="flex flex-wrap gap-2" id="chips-container">
                        @php
                            $tags = ['Tepat waktu', 'Timbangan jujur', 'Ramah', 'Harga sesuai'];
                        @endphp
                        @foreach ($tags as $tag)
                            <button type="button" data-tag="{{ $tag }}" class="tag-chip rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                {{ $tag }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Kolom Ulasan Teks --}}
                <div>
                    <label for="review-body" class="block text-sm font-bold text-ink mb-2">Ulasan</label>
                    <textarea id="review-body" name="body" rows="4" maxlength="500"
                        placeholder="Ceritakan pengalamanmu (opsional)"
                        class="w-full rounded-2xl border border-slate-200 p-3.5 text-sm outline-none transition focus:border-slate-400 focus:ring-0">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Foto (opsional) --}}
                <div>
                    <label class="block text-sm font-bold text-ink mb-2">Foto <span class="font-normal text-xs text-slate-400">(opsional)</span></label>
                    <div class="flex items-center">
                        <label for="foto-input" class="flex size-16 cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 text-slate-300 hover:border-slate-300 hover:text-slate-400 transition">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            <input type="file" id="foto-input" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                {{-- Catatan disclaimer --}}
                <p class="text-xs text-slate-400 pt-1">
                    Ulasan hanya bisa diberikan sekali dan tidak bisa diubah.
                </p>

                {{-- Tombol Kirim (Fixed di Bawah) --}}
                <div class="fixed inset-x-0 bottom-0 z-30 mx-auto w-full max-w-[420px] border-t border-slate-200 bg-white px-4 pb-[max(14px,env(safe-area-inset-bottom))] pt-3 shadow-[0_-2px_8px_rgba(0,0,0,0.03)]">
                    <button type="submit" id="btn-submit" class="block w-full rounded-full bg-ink py-3.5 text-center font-semibold text-white transition hover:bg-slate-800 active:scale-95">
                        Kirim ulasan
                    </button>
                </div>
            </form>
        </main>
    </div>

    @push('scripts')
        <script>
            (() => {
                const stars = document.querySelectorAll('.star-btn');
                const ratingVal = document.getElementById('rating-val');
                const ratingLabel = document.getElementById('rating-label');
                const textarea = document.getElementById('review-body');
                const tagChips = document.querySelectorAll('.tag-chip');

                const LABELS = {
                    1: 'Sangat Mengecewakan',
                    2: 'Kurang Puas',
                    3: 'Cukup Baik',
                    4: 'Memuaskan',
                    5: 'Sempurna'
                };

                function setRating(val) {
                    ratingVal.value = val;
                    ratingLabel.textContent = LABELS[val] || 'Pilih bintang';
                    stars.forEach((btn, idx) => {
                        if (idx < val) {
                            btn.classList.add('text-amber-400');
                            btn.classList.remove('text-slate-200');
                        } else {
                            btn.classList.remove('text-amber-400');
                            btn.classList.add('text-slate-200');
                        }
                    });
                }

                if (ratingVal.value) {
                    setRating(parseInt(ratingVal.value));
                }

                stars.forEach(btn => {
                    btn.addEventListener('click', () => {
                        setRating(parseInt(btn.dataset.star));
                    });
                });

                // Toggle chips "Yang kamu suka"
                tagChips.forEach(chip => {
                    chip.addEventListener('click', () => {
                        const isSelected = chip.classList.contains('bg-ink');
                        if (isSelected) {
                            chip.classList.remove('bg-ink', 'text-white', 'border-ink');
                            chip.classList.add('border-slate-200', 'text-slate-700');
                        } else {
                            chip.classList.remove('border-slate-200', 'text-slate-700');
                            chip.classList.add('bg-ink', 'text-white', 'border-ink');
                        }

                        // Secara otomatis tambahkan ke textarea jika diinginkan
                        const selectedTags = Array.from(document.querySelectorAll('.tag-chip.bg-ink'))
                            .map(c => c.dataset.tag);
                    });
                });
            })();
        </script>
    @endpush
</x-app-layout>
