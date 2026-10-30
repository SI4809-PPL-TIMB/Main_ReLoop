<?php

namespace App\Http\Controllers;

use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Models\WilayahProvinsi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class LokasiController extends Controller
{
    private const LEVEL = [
        'provinsi'  => [WilayahProvinsi::class, null],
        'kabupaten' => [WilayahKabupaten::class, 'provinsi_id'],
        'kecamatan' => [WilayahKecamatan::class, 'kabupaten_id'],
        'kelurahan' => [WilayahKelurahan::class, 'kecamatan_id'],
    ];

    public function edit(Request $request): View
    {
        $kel = $request->user()->profile?->kelurahan?->load('kecamatan.kabupaten.provinsi');

        return view('lokasi', [
            'profile' => $request->user()->profile,
            'pilihan' => $kel ? [
                'provinsi'  => $this->opsi($kel->kecamatan->kabupaten->provinsi),
                'kabupaten' => $this->opsi($kel->kecamatan->kabupaten),
                'kecamatan' => $this->opsi($kel->kecamatan),
                'kelurahan' => $this->opsi($kel),
            ] : null,
            'dariProfil' => $request->query('from') === 'profil',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kelurahan_id' => ['required', 'exists:wilayah_kelurahan,id'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'patokan'      => ['nullable', 'string', 'max:255'],
        ], [
            'kelurahan_id.required' => 'Pilih desa/kelurahan dulu.',
            'kelurahan_id.exists'   => 'Desa/kelurahan tidak ditemukan.',
        ]);

        $request->user()->profile()->updateOrCreate([], $data);

        return redirect()->route('account')->with('success', 'Lokasi tersimpan.');
    }

    /** Daftar anak satu level, untuk kaskade provinsi > kab/kota > kecamatan > desa. */
    public function anak(Request $request, string $level): JsonResponse
    {
        abort_unless(isset(self::LEVEL[$level]), 404);
        [$model, $fk] = self::LEVEL[$level];

        $query = $model::orderBy('nama');
        if ($fk) {
            $query->where($fk, $request->integer('parent'));
        }

        return response()->json($query->get(['id', 'nama']));
    }

    public function cari(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.addcslashes($q, '%_\\').'%';

        return response()->json(
            WilayahKelurahan::with('kecamatan.kabupaten.provinsi')
                ->where(fn ($w) => $w->where('nama', 'like', $like)
                    ->orWhereHas('kecamatan', fn ($k) => $k->where('nama', 'like', $like)
                        ->orWhereHas('kabupaten', fn ($b) => $b->where('nama', 'like', $like))))
                ->orderBy('nama')->limit(20)->get()
                ->map(fn ($k) => $this->hasil($k))
        );
    }

    /** "Gunakan lokasi saya": koordinat GPS browser -> desa terdekat via reverse geocoding. */
    public function gps(Request $request): JsonResponse
    {
        $c = $request->validate(['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']]);

        $alamat = Http::timeout(5)->withHeaders(['User-Agent' => config('app.name').' (reloop)'])
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2', 'zoom' => 16, 'accept-language' => 'id',
                'lat' => $c['latitude'], 'lon' => $c['longitude'],
            ])->json('address', []);

        $kel = $this->cocokkan($alamat);

        return $kel
            ? response()->json($this->hasil($kel))
            : response()->json(['message' => 'Daerah belum terdaftar. Pilih manual.'], 404);
    }

    /**
     * OSM di Indonesia tidak konsisten: nama desa bisa ada di village/suburb/city_district/hamlet,
     * dan kadang berejaan beda ("Catur Tunggal"). Jadi semua nilai alamat jadi kandidat, dibandingkan
     * tanpa spasi/huruf besar, lalu dipilih desa yang kecamatan atau kab/kotanya juga ikut cocok.
     * shortcut: tanpa jarak ke titik pusat desa; upgrade bila tabel wilayah punya koordinat.
     */
    private function cocokkan(array $alamat): ?WilayahKelurahan
    {
        $norm = fn ($s) => preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) $s));
        $kand = array_values(array_unique(array_filter(array_map($norm, array_diff_key($alamat, array_flip(['road', 'postcode', 'country', 'country_code', 'state', 'ISO3166-2-lvl4']))))));
        if (! $kand) {
            return null;
        }
        $county = $norm($alamat['county'] ?? $alamat['city'] ?? $alamat['regency'] ?? '');

        $skor = fn (WilayahKelurahan $k) => (int) in_array($norm($k->kecamatan->nama), $kand, true)
            + (int) ($county !== '' && str_contains($norm($k->kecamatan->kabupaten->nama), $county));

        return WilayahKelurahan::with('kecamatan.kabupaten.provinsi')
            ->whereRaw("REPLACE(LOWER(nama), ' ', '') in (".implode(',', array_fill(0, count($kand), '?')).')', $kand)
            ->get()->filter(fn ($k) => $skor($k) > 0)->sortByDesc($skor)->first();
    }

    private function opsi($m): array
    {
        return ['id' => $m->id, 'nama' => $m->nama];
    }

    private function hasil(WilayahKelurahan $k): array
    {
        $kec = $k->kecamatan;
        $kab = $kec->kabupaten;

        return [
            'label'     => $k->breadcrumb(),
            'provinsi'  => $this->opsi($kab->provinsi),
            'kabupaten' => $this->opsi($kab),
            'kecamatan' => $this->opsi($kec),
            'kelurahan' => $this->opsi($k),
        ];
    }
}
