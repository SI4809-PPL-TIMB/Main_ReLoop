<?php

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Komponen Livewire untuk filter kategori pada halaman pencarian / katalog barang.
 * Mengikuti token Tailwind Reloop (font Figtree, warna ink & brand).
 */
class CategoryFilter extends Component
{
    /**
     * Tipe yang sedang difilter ('semua' | 'bekas' | 'daur_ulang').
     */
    public string $activeType = 'semua';

    /**
     * ID kategori terpilih (null = semua kategori dalam tipe tersebut).
     */
    public ?int $selectedCategoryId = null;

    /**
     * Apakah menampilkan tab switcher tipe di atas kategori pills.
     */
    public bool $showTypeFilter = true;

    public function mount(
        string $type = 'semua',
        ?int $selected = null,
        bool $showTypeFilter = true
    ): void {
        if (in_array($type, ['semua', Category::TYPE_BEKAS, Category::TYPE_DAUR_ULANG], true)) {
            $this->activeType = $type;
        }

        $this->selectedCategoryId = $selected;
        $this->showTypeFilter     = $showTypeFilter;
    }

    /**
     * Ubah filter tipe ('semua', 'bekas', 'daur_ulang').
     */
    public function setType(string $type): void
    {
        if (!in_array($type, ['semua', Category::TYPE_BEKAS, Category::TYPE_DAUR_ULANG], true)) {
            return;
        }

        $this->activeType = $type;

        // Reset kategori jika kategori yang dipilih tidak cocok dengan tipe yang baru
        if ($this->selectedCategoryId && $type !== 'semua') {
            $cat = Category::find($this->selectedCategoryId);
            if ($cat && $cat->type !== $type) {
                $this->selectedCategoryId = null;
            }
        }

        $this->emitFilter();
    }

    /**
     * Pilih atau batalkan kategori tertentu.
     */
    public function toggleCategory(?int $categoryId = null): void
    {
        if ($this->selectedCategoryId === $categoryId) {
            $this->selectedCategoryId = null;
        } else {
            $this->selectedCategoryId = $categoryId;
        }

        $this->emitFilter();
    }

    /**
     * Pancarkan event perubahan filter ke komponen lain.
     */
    protected function emitFilter(): void
    {
        $category = $this->selectedCategoryId ? Category::find($this->selectedCategoryId) : null;

        $this->dispatch('category-filtered', [
            'type'          => $this->activeType,
            'category_id'   => $this->selectedCategoryId,
            'category_slug' => $category?->slug,
            'category_name' => $category?->name,
        ]);
    }

    /**
     * Daftar kategori yang ditampilkan sesuai filter tipe saat ini.
     */
    public function getCategoriesProperty(): Collection
    {
        $query = Category::query()->orderBy('sort_order')->orderBy('name');

        if ($this->activeType === Category::TYPE_BEKAS) {
            $query->bekas();
        } elseif ($this->activeType === Category::TYPE_DAUR_ULANG) {
            $query->daurUlang();
        }

        return $query->get();
    }

    public function render(): View
    {
        return view('livewire.category-filter', [
            'categories'         => $this->categories,
            'activeType'         => $this->activeType,
            'selectedCategoryId' => $this->selectedCategoryId,
            'showTypeFilter'     => $this->showTypeFilter,
        ]);
    }
}
