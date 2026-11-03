<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CariController extends Controller
{
    private const KONDISI = [
        'seperti_baru' => ['Seperti baru', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'baik'         => ['Baik', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'ada_minus'    => ['Ada minus', 'bg-amber-50 text-amber-700 ring-amber-200'],
    ];

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $kategori = $request->query('kategori') ?? $request->query('category_id');
        $minHarga = $request->query('min_harga') ?? $request->query('min_price');
        $maxHarga = $request->query('max_harga') ?? $request->query('max_price');
        $profile = $request->user()?->profile;

        $barang = Listing::cari($keyword)
            ->kategori($kategori)
            ->harga($minHarga, $maxHarga)
            ->terdekat($profile)
            ->with('user.profile', 'kelurahan', 'category', 'primaryPhoto')
            ->paginate(12)
            ->withQueryString();

        $daftarKategori = Category::bekas()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $kategoriTerpilih = null;
        if (! empty($kategori)) {
            $kategoriTerpilih = $daftarKategori->first(function ($c) use ($kategori) {
                return (string) $c->id === (string) $kategori || $c->slug === (string) $kategori;
            });
        }

        return view('cari', [
            'keyword'          => $keyword,
            'kategori'         => $kategori,
            'kategoriTerpilih' => $kategoriTerpilih,
            'minHarga'         => Listing::bersihkanNominal($minHarga),
            'maxHarga'         => Listing::bersihkanNominal($maxHarga),
            'daftarKategori'   => $daftarKategori,
            'barang'           => $barang,
            'kondisi'          => self::KONDISI,
            'lokasi'           => $profile?->kelurahan?->nama,
        ]);
    }
}
