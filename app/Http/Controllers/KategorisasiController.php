<?php

namespace App\Http\Controllers;

use App\Http\Requests\PilihTipeKategoriRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class KategorisasiController extends Controller
{
    // Kontrak data sementara; halaman Livewire disambungkan pada 4.1.3.
    public function index(): JsonResponse
    {
        $counts = Category::query()
            ->selectRaw('type, COUNT(*) AS total')
            ->whereIn('type', array_keys(Category::types()))
            ->groupBy('type')
            ->pluck('total', 'type');

        return response()->json([
            'tipe' => collect(Category::types())->map(
                fn (string $label, string $type) => [
                    'value' => $type,
                    'label' => $label,
                    'tersedia' => (int) ($counts[$type] ?? 0) > 0,
                ]
            )->values(),
        ]);
    }

    public function pilih(PilihTipeKategoriRequest $request): RedirectResponse
    {
        $type = $request->validated('tipe');
        $target = $type === Category::TYPE_BEKAS ? 'barang.create' : 'pengepul.sementara';

        return redirect()->route($target, ['tipe' => $type]);
    }

    // Titik integrasi form 4.2.5. Tidak membuat listing atau menyimpan draft.
    public function pasangBarang(): JsonResponse
    {
        return $this->tujuan(Category::TYPE_BEKAS, 'menunggu_form_4_2');
    }

    // Pencarian mitra lengkap dikerjakan di 8.3.
    public function pengepul(): JsonResponse
    {
        return $this->tujuan(Category::TYPE_DAUR_ULANG, 'pencarian_belum_tersedia');
    }

    private function tujuan(string $type, string $status): JsonResponse
    {
        // Tipe ditentukan oleh endpoint, bukan parameter URL dari pengguna.
        return response()->json([
            'tipe' => $type,
            'label' => Category::types()[$type],
            'status' => $status,
            'kategori' => Category::forSelect($type)
                ->map(fn (Category $category) => $category->only(['id', 'name', 'slug', 'type', 'icon'])),
        ]);
    }
}
