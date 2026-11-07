@php
    $rupiah = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $tabs = ['berjalan' => 'Berjalan', 'riwayat' => 'Riwayat', 'draft' => 'Draft'];
    $filter = ['belanja' => 'Belanja', 'jual' => 'Jualan', 'setor' => 'Setor'];
    $url = fn ($t, $ff) => route('aktivitas', array_filter(['tab' => $t, 'f' => $ff]));
    $label = fn ($tgl) => $tgl->isToday() ? 'Hari ini' : ($tgl->isYesterday() ? 'Kemarin' : $tgl->locale('id')->translatedFormat('j M'));
    $ilustrasi = function ($listing) {
        $slug = $listing?->category?->slug;
        return asset('assets/illust/categories/'.(file_exists(public_path("assets/illust/categories/{$slug}.png")) ? $slug : 'perabot').'.png');
    };
    $ikonOrang = '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>';
    $ikonCek = '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>';
    $ikonJam = '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>';
    $ikonX = '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>';
    // [teks status, ikon, kelas warna, label tombol, tautan] per status pesanan
    $status = function ($o, $jenis) use ($ikonCek, $ikonJam, $ikonX) {
        return match ($o->status) {
            \App\Models\Order::STATUS_MENUNGGU_BAYAR => ['Menunggu pembayaran', $ikonJam, 'text-amber-600', $jenis === 'belanja' ? 'Bayar' : 'Detail', $jenis === 'belanja' ? route('order.status', $o->order_number) : route('order.show', $o->order_number)],
            \App\Models\Order::STATUS_DIBAYAR => [$jenis === 'belanja' ? 'Dibayar, menunggu serah terima' : 'Dibayar, siap diserahkan', $ikonJam, 'text-amber-600', 'Detail', route('order.show', $o->order_number)],
            \App\Models\Order::STATUS_DISERAHKAN => [$jenis === 'belanja' ? 'Diserahkan penjual' : 'Diserahkan, menunggu konfirmasi pembeli', $ikonCek, 'text-slate-600', $jenis === 'belanja' ? 'Konfirmasi' : 'Detail', route('order.show', $o->order_number)],
            \App\Models\Order::STATUS_SELESAI => ['Pesanan selesai', $ikonCek, 'text-slate-600', 'Lihat', route('order.show', $o->order_number)],
            default => ['Pesanan dibatalkan', $ikonX, 'text-slate-600', 'Lihat', route('order.show', $o->order_number)],
        };
    };
@endphp
<x-app-layout title="Reloop · Aktivitas" nav="list" bodyClass="bg-[#e9ebe8]">
    <header class="px-4 pt-5">
        <h1 class="text-2xl font-bold">Aktivitas</h1>

        <div role="tablist" class="mt-4 grid grid-cols-3 border-b border-slate-200 text-center text-sm font-medium">
            @foreach ($tabs as $k => $nama)
                <a role="tab" href="{{ $url($k, $f) }}" aria-selected="{{ $tab === $k ? 'true' : 'false' }}"
                   class="py-3 {{ $tab === $k ? 'border-b-2 border-ink font-semibold text-ink' : 'text-slate-500' }}">{{ $nama }}</a>
            @endforeach
        </div>

        <div class="no-scrollbar -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 text-sm font-medium">
            @foreach ($filter as $k => $nama)
                <a href="{{ $url($tab, $f === $k ? null : $k) }}" class="shrink-0 rounded-full px-4 py-2 {{ $f === $k ? 'bg-ink text-white' : 'bg-slate-100' }}">{{ $nama }}</a>
            @endforeach
        </div>
    </header>

    <main class="pb-28">
        @forelse ($grup as $tgl => $baris)
            <h2 class="mt-3 px-4 py-2 text-xs font-medium text-slate-500">{{ $label($baris->first()['waktu']) }}, {{ $baris->first()['waktu']->format('H.i') }}</h2>
            <ul class="space-y-3 px-4">
                @foreach ($baris as $b)
                    @if ($b['jenis'] === 'draft')
                        @php $l = $b['listing']; @endphp
                        <li class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex gap-3">
                                <img src="{{ $ilustrasi($l) }}" class="size-12 shrink-0 rounded-lg object-contain" width="48" height="48" alt="">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <p class="truncate font-semibold">{{ $l->title ?: 'Tanpa judul' }}</p>
                                    <p class="text-xs text-slate-500">Draft listing{{ $l->price ? '' : ' · harga belum diisi' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                                <form method="POST" action="{{ route('barang.destroy', $l) }}" onsubmit="return confirm('Hapus draft ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-semibold text-red-500">Hapus</button>
                                </form>
                                <a href="{{ route('barang.edit', $l) }}" class="rounded-full bg-ink px-5 py-2 text-sm font-semibold text-white">Lanjutkan</a>
                            </div>
                        </li>
                    @else
                        @php
                            $o = $b['order'];
                            [$teks, $ikon, $warna, $tombol, $href] = $status($o, $b['jenis']);
                            $lawan = $b['jenis'] === 'belanja' ? $o->seller : $o->buyer;
                        @endphp
                        <li class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex gap-3">
                                <img src="{{ $ilustrasi($o->listing) }}" class="size-12 shrink-0 rounded-lg object-contain" width="48" height="48" alt="">
                                <div class="min-w-0 flex-1 space-y-1">
                                    <p class="truncate font-semibold">{{ $o->listing?->title }}</p>
                                    <p class="flex items-center gap-1.5 text-xs {{ $warna }}"><svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>{{ $teks }}</p>
                                    <p class="flex items-center gap-1.5 text-xs text-slate-500"><svg class="size-3 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">{!! $ikonOrang !!}</svg>{{ $b['jenis'] === 'belanja' ? '' : 'Pembeli: ' }}{{ $lawan?->name }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                                <p class="font-semibold">{{ $rupiah($o->agreed_price) }}</p>
                                <a href="{{ $href }}" class="rounded-full bg-ink px-5 py-2 text-sm font-semibold text-white">{{ $tombol }}</a>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        @empty
            <div class="flex min-h-[calc(100dvh-270px)] flex-col items-center justify-center px-6 text-center">
                <p class="text-base font-semibold">{{ $f === 'setor' ? 'Setor belum tersedia' : 'Belum ada aktivitas di sini' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $f === 'setor' ? 'Fitur setor sampah masih dalam pengembangan.' : 'Belanja barang bekas atau jual barangmu, aktivitasmu muncul di sini.' }}</p>
                <a href="{{ route('cari') }}" class="mt-5 inline-block rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white">Mulai jelajah</a>
            </div>
        @endforelse
    </main>
</x-app-layout>
