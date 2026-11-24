<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MitraPengepul;
use App\Models\WilayahKecamatan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** 8.3 · Cari pengepul per daerah dan jenis sampah (PPLTIMB-150). Hanya mitra terverifikasi yang tampil. */
class PengepulController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'kecamatan_id' => ['nullable', 'integer', 'exists:wilayah_kecamatan,id'],
            'jenis'        => ['nullable', 'string', 'max:100'],
        ]);

        $lokasi = $request->user()->profile?->kelurahan?->load('kecamatan.kabupaten');
        $jenisDaftar = Category::daurUlang()->orderBy('sort_order')->orderBy('name')->get();
        // Jenis yang tidak dikenal diabaikan (sama dengan "Semua"), bukan error.
        $jenis = $jenisDaftar->firstWhere('slug', $request->query('jenis'));

        // Daerah: kecamatan pilihan, atau kecamatan di profil pengguna.
        $kecamatan = $request->filled('kecamatan_id')
            ? WilayahKecamatan::with('kabupaten')->find($request->integer('kecamatan_id'))
            : $lokasi?->kecamatan;

        $mitra = MitraPengepul::terverifikasi()
            ->melayani($kecamatan?->id)
            ->menerima($jenis?->id)
            ->with(['kelurahan.kecamatan', 'user.profile', 'harga.category'])
            ->get()
            ->each(function (MitraPengepul $m) use ($lokasi, $jenis) {
                $m->setAttribute('jarak', $m->kedekatan($lokasi));
                $m->setAttribute('harga_jenis', $jenis ? $m->harga->firstWhere('category_id', $jenis->id)?->harga_per_kg : null);
            })
            // Terdekat dulu; dengan filter jenis, harga beli tertinggi dulu; lalu nama.
            ->sortBy([['jarak', 'asc'], ['harga_jenis', 'desc'], ['nama_usaha', 'asc']])
            ->values();

        return view('pengepul.index', [
            'mitra'       => $mitra,
            'jenisDaftar' => $jenisDaftar,
            'jenis'       => $jenis,
            'kecamatan'   => $kecamatan,
            'dariProfil'  => ! $request->filled('kecamatan_id'),
        ]);
    }

    public function show(MitraPengepul $mitra): View
    {
        // Mitra menunggu / ditolak tidak boleh bisa dibuka lewat URL.
        abort_unless($mitra->status === MitraPengepul::TERVERIFIKASI, 404);

        $mitra->load([
            'kelurahan.kecamatan.kabupaten',
            'area' => fn ($q) => $q->orderBy('nama'),
            'harga' => fn ($q) => $q->join('categories', 'categories.id', '=', 'mitra_harga.category_id')
                ->orderBy('categories.sort_order')->select('mitra_harga.*', 'categories.name as jenis'),
            'jam',
            'user.profile',
        ]);

        return view('pengepul.show', [
            'mitra'       => $mitra,
            'jumlahUlasan' => $mitra->user?->receivedReviews()->count() ?? 0,
        ]);
    }
}
