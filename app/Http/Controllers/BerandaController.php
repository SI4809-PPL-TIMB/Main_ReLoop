<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
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

        $barang = Listing::with('user.profile', 'kelurahan', 'primaryPhoto')->where('status', 'aktif')->latest('published_at')->limit(8)->get()
            ->map(fn ($l) => [
                'judul'   => $l->title,
                'harga'   => (int) $l->price,
                'kondisi' => self::KONDISI[$l->condition],
                'rating'  => $l->user->profile?->avg_rating,
                'lokasi'  => $l->kelurahan?->nama,
                'foto'    => $l->primaryPhoto?->url,
            ]);

        return view('beranda', [
            'lokasi'     => $profile?->kelurahan?->nama,
            'poin'       => $profile?->total_points ?? 0,
            'level'      => $profile?->level ?? 1,
            'streak'     => $profile?->streak_count ?? 0,
            'kategori'   => Category::where('type', Category::TYPE_BEKAS)->orderBy('id')->limit(7)->get(),
            'barang'     => $barang->isNotEmpty() ? $barang : $this->contohBarang(),
            'pengepul'   => $this->contohPengepul(),
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

    // shortcut: belum ada model pengepul; data contoh dari mockup.
    private function contohPengepul(): array
    {
        return [
            ['nama' => 'Bank Sampah Hijau Lestari', 'harga' => 3500, 'gambar' => 'hijau-lestari', 'jarak' => '0,8 km'],
            ['nama' => 'CV Daur Jaya', 'harga' => 3800, 'gambar' => 'daur-jaya', 'jarak' => '1,6 km'],
        ];
    }
}
