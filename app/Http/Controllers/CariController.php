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
        'ada_minus'    => ['Ada minus', 'bg-amber-50 text-amber-800 ring-amber-200'],
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

        $urut = $request->query('urut', 'dekat');
        $kondisiFilter = $request->query('kondisi');

        $profile = $request->user()?->profile;

        $query = Listing::cari($keyword)
            ->kategori($kategori)
            ->harga($minHarga, $maxHarga)
            ->wilayah($provinsi, $kabupaten, $kecamatan, $kelurahan);

        if (! empty($kondisiFilter)) {
            $kondisiArr = is_array($kondisiFilter) ? $kondisiFilter : explode(',', (string) $kondisiFilter);
            $query->whereIn('listings.condition', $kondisiArr);
        }

        if ($urut === 'baru') {
            $query->orderByDesc('listings.published_at')->orderByDesc('listings.id');
        } elseif ($urut === 'murah') {
            $query->orderBy('listings.price')->orderByDesc('listings.id');
        } elseif ($urut === 'mahal') {
            $query->orderByDesc('listings.price')->orderByDesc('listings.id');
        } else {
            $query->terdekat($profile);
        }

        $barang = $query
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
                    'nama'       => $kelObj->nama,
                    'induk'      => $kelObj->kecamatan?->kabupaten?->nama,
                    'label'      => 'Desa/Kel: '.$kelObj->nama,
                    'breadcrumb' => $kelObj->breadcrumb(),
                    'pipe'       => ($kelObj->kecamatan?->kabupaten?->provinsi?->nama ?? '').'|'.($kelObj->kecamatan?->kabupaten?->nama ?? '').'|'.($kelObj->kecamatan?->nama ?? '').'|'.$kelObj->nama,
                ];
            }
        }

        // Nama daerah untuk teks "X barang di sekitar [nama daerah]"
        $namaDaerah = $wilayahTerpilih['nama'] ?? $profile?->kelurahan?->nama ?? 'lokasimu';

        // Breadcrumb HTML sesuai mockup (Jawa Barat › Kab. Bogor › Ciomas › Sukamaju)
        $breadcrumbHtml = $this->bangunBreadcrumbHtml($wilayahTerpilih, $profile);

        // Rekomendasi wilayah untuk dialog cari lokasi
        $daftarKelurahanRekomendasi = WilayahKelurahan::with('kecamatan.kabupaten.provinsi')
            ->limit(10)
            ->get()
            ->map(function ($k) {
                $k->breadcrumb_pipe = ($k->kecamatan?->kabupaten?->provinsi?->nama ?? '').'|'.($k->kecamatan?->kabupaten?->nama ?? '').'|'.($k->kecamatan?->nama ?? '').'|'.$k->nama;
                return $k;
            });

        // Hitung badge jumlah filter aktif untuk tombol filter di header
        $jumlahFilterAktif = 0;
        if ($minHarga !== null || $maxHarga !== null) {
            $jumlahFilterAktif++;
        }
        if (! empty($kondisiFilter) && count((array) $kondisiFilter) < 3) {
            $jumlahFilterAktif++;
        }
        if (! empty($kategori)) {
            $jumlahFilterAktif++;
        }
        if (! empty($provinsi) || ! empty($kabupaten) || ! empty($kecamatan) || ! empty($kelurahan)) {
            $jumlahFilterAktif++;
        }

        return view('cari', [
            'keyword'                    => $keyword,
            'kategori'                   => $kategori,
            'kategoriTerpilih'           => $kategoriTerpilih,
            'minHarga'                   => Listing::bersihkanNominal($minHarga),
            'maxHarga'                   => Listing::bersihkanNominal($maxHarga),
            'provinsi'                   => $provinsi,
            'kabupaten'                  => $kabupaten,
            'kecamatan'                  => $kecamatan,
            'kelurahan'                  => $kelurahan,
            'wilayahTerpilih'            => $wilayahTerpilih,
            'namaDaerah'                 => $namaDaerah,
            'breadcrumbHtml'             => $breadcrumbHtml,
            'urut'                       => $urut,
            'kondisiFilter'              => (array) ($kondisiFilter ?? ['seperti_baru', 'baik']),
            'jumlahFilterAktif'          => $jumlahFilterAktif,
            'daftarKelurahanRekomendasi' => $daftarKelurahanRekomendasi,
            'daftarKategori'             => $daftarKategori,
            'barang'                     => $barang,
            'kondisi'                    => self::KONDISI,
            'lokasi'                     => $profile?->kelurahan?->nama,
        ]);
    }

    private function bangunBreadcrumbHtml(?array $wilayahTerpilih, $profile): string
    {
        if ($wilayahTerpilih && ! empty($wilayahTerpilih['pipe'])) {
            $parts = explode('|', $wilayahTerpilih['pipe']);
            $lastIndex = count($parts) - 1;
            $html = '';
            foreach ($parts as $idx => $p) {
                if ($idx === $lastIndex) {
                    $html .= '<b class="font-semibold text-ink">'.e($p).'</b>';
                } else {
                    $html .= e($p).' <span>›</span> ';
                }
            }
            return $html;
        }

        $kel = $profile?->kelurahan;
        if ($kel) {
            $prov = $kel->kecamatan?->kabupaten?->provinsi?->nama ?? '';
            $kab  = $kel->kecamatan?->kabupaten?->nama ?? '';
            $kec  = $kel->kecamatan?->nama ?? '';
            $desa = $kel->nama;

            $parts = array_filter([$prov, $kab, $kec, $desa]);
            $lastIndex = count($parts) - 1;
            $html = '';
            $i = 0;
            foreach ($parts as $p) {
                if ($i === $lastIndex) {
                    $html .= '<b class="font-semibold text-ink">'.e($p).'</b>';
                } else {
                    $html .= e($p).' <span>›</span> ';
                }
                $i++;
            }
            return $html;
        }

        return 'Indonesia <span>›</span> <b class="font-semibold text-ink">Pilih Lokasi</b>';
    }
}
