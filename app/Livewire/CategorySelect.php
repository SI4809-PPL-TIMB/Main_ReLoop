<?php

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Komponen Livewire untuk pemilihan kategori pada form pasang iklan / setor sampah.
 * Mengikuti token Tailwind Reloop (font Figtree, warna brand & loop).
 */
class CategorySelect extends Component
{
    /**
     * Tipe kategori yang sedang aktif ('bekas' | 'daur_ulang').
     */
    public string $selectedType = Category::TYPE_BEKAS;

    /**
     * ID kategori yang sedang dipilih.
     */
    public ?int $selectedCategoryId = null;

    /**
     * Nama input form HTML untuk integrasi dengan form POST tradisional.
     */
    public string $name = 'category_id';

    /**
     * Izinkan pengguna mengubah tipe kategori via tab switcher.
     */
    public bool $allowTypeSwitch = true;

    /**
     * Tampilkan sebagai dropdown compact atau visual grid card.
     * Mode: 'grid' (default) | 'compact'
     */
    public string $mode = 'grid';

    /**
     * Label untuk input/field.
     */
    public ?string $label = 'Kategori';

    /**
     * Apakah field ini wajib diisi.
     */
    public bool $required = true;

    public function mount(
        ?string $type = null,
        ?int $selected = null,
        string $name = 'category_id',
        bool $allowTypeSwitch = true,
        string $mode = 'grid',
        ?string $label = 'Kategori',
        bool $required = true
    ): void {
        if ($type && in_array($type, [Category::TYPE_BEKAS, Category::TYPE_DAUR_ULANG], true)) {
            $this->selectedType = $type;
        }

        $this->selectedCategoryId = $selected;
        $this->name              = $name;
        $this->allowTypeSwitch   = $allowTypeSwitch;
        $this->mode              = $mode;
        $this->label             = $label;
        $this->required          = $required;
    }

    /**
     * Ganti tipe kategori yang aktif.
     */
    public function setType(string $type): void
    {
        if (!in_array($type, [Category::TYPE_BEKAS, Category::TYPE_DAUR_ULANG], true)) {
            return;
        }

        $this->selectedType = $type;

        // Reset kategori terpilih jika tidak sesuai dengan tipe baru
        if ($this->selectedCategoryId) {
            $cat = Category::find($this->selectedCategoryId);
            if ($cat && $cat->type !== $type) {
                $this->selectedCategoryId = null;
                $this->dispatch('category-selected', [
                    'id'   => null,
                    'name' => null,
                    'type' => $this->selectedType,
                    'slug' => null,
                ]);
            }
        }
    }

    /**
     * Pilih kategori berdasarkan ID.
     */
    public function selectCategory(int $categoryId): void
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return;
        }

        // Toggle jika diklik ulang pada mode grid
        if ($this->selectedCategoryId === $categoryId && !$this->required) {
            $this->selectedCategoryId = null;
            $this->dispatch('category-selected', [
                'id'   => null,
                'name' => null,
                'type' => $this->selectedType,
                'slug' => null,
            ]);
            return;
        }

        $this->selectedCategoryId = $categoryId;
        $this->selectedType       = $category->type;

        $this->dispatch('category-selected', [
            'id'   => $category->id,
            'name' => $category->name,
            'type' => $category->type,
            'slug' => $category->slug,
        ]);
    }

    /**
     * Daftar kategori sesuai tipe aktif.
     */
    public function getCategoriesProperty(): Collection
    {
        return Category::forSelect($this->selectedType);
    }

    /**
     * Data kategori yang sedang dipilih saat ini.
     */
    public function getSelectedCategoryProperty(): ?Category
    {
        return $this->selectedCategoryId
            ? Category::find($this->selectedCategoryId)
            : null;
    }

    public function render(): View
    {
        return view('livewire.category-select', [
            'categories'         => $this->categories,
            'selectedCategory'   => $this->selectedCategory,
            'selectedType'       => $this->selectedType,
            'selectedCategoryId' => $this->selectedCategoryId,
            'name'               => $this->name,
            'allowTypeSwitch'    => $this->allowTypeSwitch,
            'mode'               => $this->mode,
            'label'              => $this->label,
            'required'           => $this->required,
        ]);
    }
}
