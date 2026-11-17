{{-- Spinner kecil untuk tombol/indikator lokal. Dekoratif: status teksnya diumumkan oleh label di sebelahnya. --}}
<svg {{ $attributes->merge(['class' => 'reloop-spin size-4 shrink-0']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
</svg>
