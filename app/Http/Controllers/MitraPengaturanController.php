<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanMitraRequest;
use App\Models\Category;
use App\Models\MitraPengepul;
use App\Models\WilayahKecamatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** 8.1 · Pengepul mengatur area layanan, harga per kg, jam operasional, dan layanan Jemput / Antar Sendiri. */
class MitraPengaturanController extends Controller
{
    public function edit(Request $request): View
    {
        $mitra = $this->mitra($request)->load(['area', 'harga', 'jam']);

        return view('mitra.pengaturan', [
            'mitra'     => $mitra,
            'kecamatan' => WilayahKecamatan::with('kabupaten')->orderBy('kabupaten_id')->orderBy('nama')->get()->groupBy('kabupaten.nama'),
            'sampah'    => Category::forSelect(Category::TYPE_DAUR_ULANG),
            'harga'     => $mitra->harga->pluck('harga_per_kg', 'category_id'),
            'jam'       => $mitra->jam->keyBy('hari'),
        ]);
    }

    public function update(PengaturanMitraRequest $request): RedirectResponse
    {
        $mitra = $this->mitra($request);
        $data = $request->validated();

        DB::transaction(function () use ($mitra, $data) {
            $mitra->update(['layanan_jemput' => $data['layanan_jemput'], 'layanan_antar' => $data['layanan_antar']]);
            $mitra->area()->sync($data['area']);

            $mitra->harga()->whereNotIn('category_id', array_keys($data['harga']))->delete();
            foreach ($data['harga'] as $kategori => $harga) {
                $mitra->harga()->updateOrCreate(['category_id' => $kategori], ['harga_per_kg' => $harga]);
            }

            $mitra->jam()->delete();
            $mitra->jam()->createMany(collect($data['jam'])
                ->map(fn ($j, $hari) => ['hari' => $hari, 'buka' => $j['buka'], 'tutup' => $j['tutup']])->values()->all());
        });

        return redirect()->route('mitra.pengaturan')->with('sukses', 'Pengaturan mitra tersimpan.');
    }

    private function mitra(Request $request): MitraPengepul
    {
        // Akun pengepul selalu dibuat admin bersama data mitranya.
        return MitraPengepul::where('user_id', $request->user()->id)->first()
            ?? abort(404, 'Data mitra untuk akun ini belum dibuat admin.');
    }
}
