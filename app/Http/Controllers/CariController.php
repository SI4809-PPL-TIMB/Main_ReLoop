<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Models\WilayahProvinsi;
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

        $provinsi = $request->query('provinsi_id') ?? $request->query('provinsi');
        $kabupaten = $request->query('kabupaten_id') ?? $request->query('kabupaten');
        $kecamatan = $request->query('kecamatan_id') ?? $request->query('kecamatan');
        $kelurahan = $request->query('kelurahan_id') ?? $request->query('kelurahan') ?? $request->query('desa');

        $profile = $request->user()?->profile;

        $barang = Listing::cari($keyword)
            ->kategori($kategori)
            ->harga($minHarga, $maxHarga)
            ->wilayah($provinsi, $kabupaten, $kecamatan, $kelurahan)
            ->terdekat($profile)
            ->with('user.profile', 'kelurahan.kecamatan.kabupaten.provinsi', 'category', 'primaryPhoto')
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

        // Resolusi info label wilayah terpilih
        $wilayahTerpilih = null;
        if (! empty($kelurahan)) {
            $kelObj = is_numeric($kelurahan) ? WilayahKelurahan::with('kecamatan.kabupaten.provinsi')->find($kelurahan) : WilayahKelurahan::with('kecamatan.kabupaten.provinsi')->where('nama', 'like', '%'.$kelurahan.'%')->first();
            if ($kelObj) {
                $wilayahTerpilih = [
                    'level'      => 'kelurahan',
                    'label'      => 'Desa/Kel: '.$kelObj->nama,
                    'breadcrumb' => $kelObj->breadcrumb(),
                ];
            }
        } elseif (! empty($kecamatan)) {
            $kecObj = is_numeric($kecamatan) ? WilayahKecamatan::with('kabupaten.provinsi')->find($kecamatan) : WilayahKecamatan::with('kabupaten.provinsi')->where('nama', 'like', '%'.$kecamatan.'%')->first();
            if ($kecObj) {
                $wilayahTerpilih = [
                    'level'      => 'kecamatan',
                    'label'      => 'Kec: '.$kecObj->nama,
                    'breadcrumb' => $kecObj->nama.', '.$kecObj->kabupaten?->nama,
                ];
            }
        } elseif (! empty($kabupaten)) {
            $kabObj = is_numeric($kabupaten) ? WilayahKabupaten::with('provinsi')->find($kabupaten) : WilayahKabupaten::with('provinsi')->where('nama', 'like', '%'.$kabupaten.'%')->first();
            if ($kabObj) {
                $wilayahTerpilih = [
                    'level'      => 'kabupaten',
                    'label'      => $kabObj->nama,
                    'breadcrumb' => $kabObj->nama.', '.$kabObj->provinsi?->nama,
                ];
            }
        } elseif (! empty($provinsi)) {
            $provObj = is_numeric($provinsi) ? WilayahProvinsi::find($provinsi) : WilayahProvinsi::where('nama', 'like', '%'.$provinsi.'%')->first();
            if ($provObj) {
                $wilayahTerpilih = [
                    'level'      => 'provinsi',
                    'label'      => 'Prov: '.$provObj->nama,
                    'breadcrumb' => $provObj->nama,
                ];
            }
        }

        $daftarProvinsi = WilayahProvinsi::orderBy('nama')->get();

        return view('cari', [
            'keyword'          => $keyword,
            'kategori'         => $kategori,
            'kategoriTerpilih' => $kategoriTerpilih,
            'minHarga'         => Listing::bersihkanNominal($minHarga),
            'maxHarga'         => Listing::bersihkanNominal($maxHarga),
            'provinsi'         => $provinsi,
            'kabupaten'        => $kabupaten,
            'kecamatan'        => $kecamatan,
            'kelurahan'        => $kelurahan,
            'wilayahTerpilih'  => $wilayahTerpilih,
            'daftarProvinsi'   => $daftarProvinsi,
            'daftarKategori'   => $daftarKategori,
            'barang'           => $barang,
            'kondisi'          => self::KONDISI,
            'lokasi'           => $profile?->kelurahan?->nama,
        ]);
    }
}
