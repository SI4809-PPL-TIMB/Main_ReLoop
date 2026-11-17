@props(['jumlah' => 6])
{{-- Kerangka grid barang (sama dengan kartu di cari/beranda) selama hasil berikutnya dimuat. --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-2 gap-x-3 gap-y-4']) }} aria-hidden="true">
    @for ($i = 0; $i < $jumlah; $i++)
        <div>
            <div class="reloop-skeleton h-36 rounded-xl"></div>
            <div class="reloop-skeleton mt-2 h-4 w-10 rounded-md"></div>
            <div class="reloop-skeleton mt-2 h-4 w-4/5 rounded-md"></div>
            <div class="reloop-skeleton mt-1.5 h-3 w-1/2 rounded-md"></div>
            <div class="reloop-skeleton mt-2 h-4 w-2/5 rounded-md"></div>
        </div>
    @endfor
</div>
