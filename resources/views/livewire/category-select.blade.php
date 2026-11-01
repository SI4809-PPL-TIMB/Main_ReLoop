<div class="space-y-3 font-sans">
    @if ($label)
        <div class="flex items-center justify-between">
            <label class="text-sm font-semibold text-ink">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500">*</span>
                @else
                    <span class="text-xs font-normal text-slate-400">· opsional</span>
                @endif
            </label>

            @if ($selectedCategory)
                <span class="text-xs font-medium text-brand-700">
                    Dipilih: <b class="font-semibold">{{ $selectedCategory->name }}</b>
                </span>
            @endif
        </div>
    @endif

    {{-- Switcher Tipe Kategori --}}
    @if ($allowTypeSwitch)
        <div class="inline-flex w-full rounded-xl bg-slate-100 p-1 text-xs font-medium">
            <button
                type="button"
                wire:click="setType('{{ \App\Models\Category::TYPE_BEKAS }}')"
                class="flex-1 rounded-lg py-2 text-center transition-all {{ $selectedType === \App\Models\Category::TYPE_BEKAS ? 'bg-white font-semibold text-ink shadow-sm' : 'text-slate-500 hover:text-ink' }}"
            >
                Barang Bekas
            </button>
            <button
                type="button"
                wire:click="setType('{{ \App\Models\Category::TYPE_DAUR_ULANG }}')"
                class="flex-1 rounded-lg py-2 text-center transition-all {{ $selectedType === \App\Models\Category::TYPE_DAUR_ULANG ? 'bg-white font-semibold text-ink shadow-sm' : 'text-slate-500 hover:text-ink' }}"
            >
                Sampah Daur Ulang
            </button>
        </div>
    @endif

    {{-- Input hidden untuk form submission standar --}}
    <input type="hidden" name="{{ $name }}" value="{{ $selectedCategoryId }}" @if($required) required @endif>

    @if ($mode === 'compact')
        {{-- Mode Dropdown Compact --}}
        <div class="relative">
            <select
                wire:change="selectCategory($event.target.value)"
                class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-ink outline-none transition focus:border-ink"
            >
                <option value="">-- Pilih Kategori --</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected($selectedCategoryId === $cat->id)>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    @else
        {{-- Mode Grid Card Visual --}}
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
            @forelse ($categories as $cat)
                @php($isSelected = $selectedCategoryId === $cat->id)
                <button
                    type="button"
                    wire:click="selectCategory({{ $cat->id }})"
                    class="group relative flex flex-col items-center justify-center gap-2 rounded-2xl border p-3 text-center transition-all duration-150 {{ $isSelected ? 'border-brand-500 bg-brand-50 text-brand-900 shadow-sm ring-2 ring-brand-400/40' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50/80 active:scale-[0.98]' }}"
                >
                    {{-- Ikon Kategori --}}
                    <div class="flex size-10 items-center justify-center rounded-xl transition-colors {{ $isSelected ? 'bg-brand-500 text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200' }}">
                        <x-category-icon :name="$cat->icon" class="size-5" />
                    </div>

                    {{-- Nama Kategori --}}
                    <span class="text-xs font-semibold leading-tight line-clamp-1">
                        {{ $cat->name }}
                    </span>

                    {{-- Indikator Checkmark saat terpilih --}}
                    @if ($isSelected)
                        <span class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm ring-2 ring-white">
                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </button>
            @empty
                <div class="col-span-3 py-6 text-center text-xs text-slate-400">
                    Tidak ada kategori tersedia untuk tipe ini.
                </div>
            @endforelse
        </div>
    @endif
</div>

