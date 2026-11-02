<?php

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PilihAlur extends Component
{
    public string $tipe = '';

    public function mount(): void
    {
        $previous = old('tipe', request()->query('tipe', ''));
        $this->tipe = is_string($previous) && array_key_exists($previous, Category::types()) ? $previous : '';
    }

    public function render(): View
    {
        $available = Category::query()->whereIn('type', array_keys(Category::types()))
            ->distinct()->pluck('type')->all();

        return view('livewire.pilih-alur', [
            'types' => Category::types(),
            'available' => $available,
            'canContinue' => in_array($this->tipe, $available, true),
        ]);
    }
}
