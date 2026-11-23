@props(['status'])

@php
    $warna = [
        \App\Models\MitraPengepul::MENUNGGU      => 'bg-amber-50 text-amber-800 ring-amber-200',
        \App\Models\MitraPengepul::TERVERIFIKASI => 'bg-brand-50 text-brand-800 ring-brand-200',
        \App\Models\MitraPengepul::DITOLAK       => 'bg-red-50 text-red-700 ring-red-200',
    ][$status] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset', $warna]) }}>
    {{ \App\Models\MitraPengepul::STATUS[$status] ?? $status }}
</span>
