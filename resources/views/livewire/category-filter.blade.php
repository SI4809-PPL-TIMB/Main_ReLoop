<div class="space-y-2.5 font-sans">
    {{-- Filter Tipe: Semua | Barang Bekas | Daur Ulang --}}
    @if ($showTypeFilter)
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1">
            <button
                type="button"
                wire:click="setType('semua')"
                class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all {{ $activeType === 'semua' ? 'bg-ink text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}"
            >
                Semua
            </button>
            <button
                type="button"
                wire:click="setType('{{ \App\Models\Category::TYPE_BEKAS }}')"
                class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all {{ $activeType === \App\Models\Category::TYPE_BEKAS ? 'bg-ink text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}"
            >
                Barang Bekas
            </button>
            <button
                type="button"
                wire:click="setType('{{ \App\Models\Category::TYPE_DAUR_ULANG }}')"
                class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition-all {{ $activeType === \App\Models\Category::TYPE_DAUR_ULANG ? 'bg-ink text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}"
            >
                Sampah Daur Ulang
            </button>
        </div>
    @endif

    {{-- Horizontal Scrollable Category Pills --}}
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-0.5">
        {{-- Pill "Semua Kategori" --}}
        <button
            type="button"
            wire:click="toggleCategory(null)"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-all {{ $selectedCategoryId === null ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-ink' }}"
        >
            <span>Semua</span>
        </button>

        {{-- Pills per Kategori --}}
        @foreach ($categories as $cat)
            @php($isActive = $selectedCategoryId === $cat->id)
            <button
                type="button"
                wire:click="toggleCategory({{ $cat->id }})"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-all {{ $isActive ? 'bg-brand-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-ink' }}"
            >
                <x-category-icon :name="$cat->icon" class="size-3.5 {{ $isActive ? 'text-white' : 'text-slate-500' }}" />
                <span>{{ $cat->name }}</span>
            </button>
        @endforeach
    </div>
</div>

