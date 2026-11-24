<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use App\Models\MitraPengepul;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    private const KONDISI = [
        'seperti_baru' => ['Seperti baru', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'baik'         => ['Baik', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'ada_minus'    => ['Ada minus', 'bg-amber-50 text-amber-700 ring-amber-200'],
    ];

    public function index(Request $request): View
    {
        $profile = $request->user()->profile;

        $barang = Listing::with('user.profile', 'kelurahan', 'primaryPhoto')->aktif()->terdekat($profile)->limit(8)->get()
            ->map(fn ($l) => [
                'id'      => $l->id,
                'judul'   => $l->title,
                'harga'   => (int) $l->price,
                'kondisi' => self::KONDISI[$l->condition],
                'rating'  => $l->user->profile?->avg_rating,
                'lokasi'  => $l->jarak_km !== null ? number_format($l->jarak_km, 1, ',', '.').' km' : $l->kelurahan?->nama,
                'foto'    => $l->primaryPhoto?->url,
            ]);

        return view('beranda', [
            'lokasi'     => $profile?->kelurahan?->nama,
            'poin'       => $profile?->total_points ?? 0,
            'level'      => $profile?->level ?? 1,
            'streak'     => $profile?->streak_count ?? 0,
            'kategori'   => Category::forSelect(Category::TYPE_BEKAS)->take(7),
            'barang'     => $barang->isNotEmpty() ? $barang : $this->contohBarang(),
            'pengepul'   => $this->pengepulTerdekat($profile),
            'kecamatan'  => $profile?->kelurahan?->kecamatan?->nama,
            'kondisi'    => self::KONDISI,
        ]);
    }

    // shortcut: data contoh dari mockup sampai fitur pasang barang selesai; ganti dengan query Listing yang dibatasi radius.
    private function contohBarang(): \Illuminate\Support\Collection
    {
        return collect([
            ['judul' => 'Kursi Kayu Jati', 'harga' => 350000, 'kondisi' => self::KONDISI['baik'], 'rating' => 4.8, 'lokasi' => '1,4 km'],
            ['judul' => 'Kaos Band Original', 'harga' => 85000, 'kondisi' => self::KONDISI['seperti_baru'], 'rating' => 4.6, 'lokasi' => '2,1 km'],
            ['judul' => 'Sepeda Lipat 20"', 'harga' => 900000, 'kondisi' => self::KONDISI['baik'], 'rating' => 4.9, 'lokasi' => '3,5 km'],
        ]);
    }

    /**
     * Mitra terverifikasi yang melayani kecamatan pengguna (tanpa lokasi: semua mitra terverifikasi).
     * Lapak di kecamatan yang sama tampil lebih dulu. Mitra belum punya koordinat, jadi jarak diganti
     * nama daerah lapak. Pencarian lengkap + "Lihat semua" dikerjakan di 8.3.
     */
    private function pengepulTerdekat(?\App\Models\Profile $profile): \Illuminate\Support\Collection
    {
        $kecamatan = $profile?->kelurahan?->kecamatan_id;

        return MitraPengepul::terverifikasi()
            ->with(['kelurahan.kecamatan', 'harga' => fn ($q) => $q->join('categories', 'categories.id', '=', 'mitra_harga.category_id')
                ->orderBy('categories.sort_order')->select('mitra_harga.*', 'categories.name as jenis')])
            ->melayani($kecamatan)
            ->get()
            ->sortBy(fn ($m) => [$m->kelurahan?->kecamatan_id === $kecamatan ? 0 : 1, $m->nama_usaha])
            ->take(5)
            ->map(fn (MitraPengepul $m) => [
                'id'     => $m->id,
                'nama'   => $m->nama_usaha,
                'harga'  => $m->harga->first()?->harga_per_kg,
                'jenis'  => $m->harga->first()?->jenis,
                'gambar' => $m->gambar(),
                'lokasi' => $m->kelurahan ? $m->kelurahan->nama.', '.$m->kelurahan->kecamatan->nama : null,
                'layanan' => $m->layananLabel(),
            ])->values();
    }
}
