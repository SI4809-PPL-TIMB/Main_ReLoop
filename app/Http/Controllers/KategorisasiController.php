<?php

namespace App\Http\Controllers;

use App\Http\Requests\PilihTipeKategoriRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategorisasiController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            return view('kategori.index');
        }
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
    public function pasangBarang(Request $request): JsonResponse|View
    {
        return $this->tujuan($request, Category::TYPE_BEKAS, 'menunggu_form_4_2');
    }

    // Pencarian mitra lengkap dikerjakan di 8.3.
    public function pengepul(Request $request): JsonResponse|View
    {
        return $this->tujuan($request, Category::TYPE_DAUR_ULANG, 'pencarian_belum_tersedia');
    }

    private function tujuan(Request $request, string $type, string $status): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            return view('kategori.tujuan', ['tipe' => $type, 'label' => Category::types()[$type]]);
        }
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
