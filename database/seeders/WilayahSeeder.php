<?php

namespace Database\Seeders;

use App\Models\WilayahKabupaten;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use App\Models\WilayahProvinsi;
use Illuminate\Database\Seeder;

/**
 * Seeder wilayah Jawa Barat — minimal Kab./Kota Bogor dan Depok
 * sesuai acceptance criteria PPLTIMB-31.
 *
 * Data kode BPS mengikuti BPS 2024.
 */
class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------
        // Provinsi Jawa Barat
        // -------------------------
        $jabar = WilayahProvinsi::create([
            'nama' => 'Jawa Barat',
            'kode' => '32',
        ]);

        // -------------------------
        // Kabupaten Bogor
        // -------------------------
        $kabBogor = WilayahKabupaten::create([
            'provinsi_id' => $jabar->id,
            'nama'        => 'Kabupaten Bogor',
            'kode'        => '3201',
        ]);

        $kecCibinong = WilayahKecamatan::create([
            'kabupaten_id' => $kabBogor->id,
            'nama'         => 'Cibinong',
            'kode'         => '3201010',
        ]);
        $kelCibinong = [
            ['nama' => 'Cibinong',      'kode' => '3201010001'],
            ['nama' => 'Nanggewer',     'kode' => '3201010002'],
            ['nama' => 'Nanggewer Mekar','kode'=> '3201010003'],
            ['nama' => 'Karadenan',     'kode' => '3201010004'],
            ['nama' => 'Harapan Jaya',  'kode' => '3201010005'],
        ];
        foreach ($kelCibinong as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecCibinong->id]));
        }

        $kecBogorTimur = WilayahKecamatan::create([
            'kabupaten_id' => $kabBogor->id,
            'nama'         => 'Bogor Timur',
            'kode'         => '3201020',
        ]);
        $kelBogorTimur = [
            ['nama' => 'Katulampa',     'kode' => '3201020001'],
            ['nama' => 'Baranangsiang', 'kode' => '3201020002'],
            ['nama' => 'Sindangsari',   'kode' => '3201020003'],
        ];
        foreach ($kelBogorTimur as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecBogorTimur->id]));
        }

        $kecLeuwiliang = WilayahKecamatan::create([
            'kabupaten_id' => $kabBogor->id,
            'nama'         => 'Leuwiliang',
            'kode'         => '3201030',
        ]);
        $kelLeuwiliang = [
            ['nama' => 'Leuwiliang',    'kode' => '3201030001'],
            ['nama' => 'Barengkok',     'kode' => '3201030002'],
            ['nama' => 'Karehkel',      'kode' => '3201030003'],
        ];
        foreach ($kelLeuwiliang as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecLeuwiliang->id]));
        }

        // -------------------------
        // Kota Bogor
        // -------------------------
        $kotaBogor = WilayahKabupaten::create([
            'provinsi_id' => $jabar->id,
            'nama'        => 'Kota Bogor',
            'kode'        => '3271',
        ]);

        $kecBogorTengah = WilayahKecamatan::create([
            'kabupaten_id' => $kotaBogor->id,
            'nama'         => 'Bogor Tengah',
            'kode'         => '3271010',
        ]);
        $kelBogorTengah = [
            ['nama' => 'Paledang',       'kode' => '3271010001'],
            ['nama' => 'Pabaton',        'kode' => '3271010002'],
            ['nama' => 'Sempur',         'kode' => '3271010003'],
            ['nama' => 'Cibogor',        'kode' => '3271010004'],
            ['nama' => 'Gudang',         'kode' => '3271010005'],
            ['nama' => 'Ciwaringin',     'kode' => '3271010006'],
        ];
        foreach ($kelBogorTengah as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecBogorTengah->id]));
        }

        $kecBogorSelatan = WilayahKecamatan::create([
            'kabupaten_id' => $kotaBogor->id,
            'nama'         => 'Bogor Selatan',
            'kode'         => '3271020',
        ]);
        $kelBogorSelatan = [
            ['nama' => 'Batutulis',      'kode' => '3271020001'],
            ['nama' => 'Empang',         'kode' => '3271020002'],
            ['nama' => 'Lawang Gintung', 'kode' => '3271020003'],
            ['nama' => 'Bondongan',      'kode' => '3271020004'],
            ['nama' => 'Muarasari',      'kode' => '3271020005'],
        ];
        foreach ($kelBogorSelatan as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecBogorSelatan->id]));
        }

        $kecBogorUtara = WilayahKecamatan::create([
            'kabupaten_id' => $kotaBogor->id,
            'nama'         => 'Bogor Utara',
            'kode'         => '3271030',
        ]);
        $kelBogorUtara = [
            ['nama' => 'Tanah Baru',     'kode' => '3271030001'],
            ['nama' => 'Cibuluh',        'kode' => '3271030002'],
            ['nama' => 'Kedung Halang',  'kode' => '3271030003'],
            ['nama' => 'Tegal Gundil',   'kode' => '3271030004'],
        ];
        foreach ($kelBogorUtara as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecBogorUtara->id]));
        }

        // -------------------------
        // Kota Depok
        // -------------------------
        $kotaDepok = WilayahKabupaten::create([
            'provinsi_id' => $jabar->id,
            'nama'        => 'Kota Depok',
            'kode'        => '3276',
        ]);

        $kecBeji = WilayahKecamatan::create([
            'kabupaten_id' => $kotaDepok->id,
            'nama'         => 'Beji',
            'kode'         => '3276010',
        ]);
        $kelBeji = [
            ['nama' => 'Beji',           'kode' => '3276010001'],
            ['nama' => 'Beji Timur',     'kode' => '3276010002'],
            ['nama' => 'Pondok Cina',    'kode' => '3276010003'],
            ['nama' => 'Kukusan',        'kode' => '3276010004'],
            ['nama' => 'Tanah Baru',     'kode' => '3276010005'],
            ['nama' => 'Kemiri Muka',    'kode' => '3276010006'],
        ];
        foreach ($kelBeji as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecBeji->id]));
        }

        $kecSukmajaya = WilayahKecamatan::create([
            'kabupaten_id' => $kotaDepok->id,
            'nama'         => 'Sukmajaya',
            'kode'         => '3276020',
        ]);
        $kelSukmajaya = [
            ['nama' => 'Sukmajaya',      'kode' => '3276020001'],
            ['nama' => 'Mekarjaya',      'kode' => '3276020002'],
            ['nama' => 'Abadijaya',      'kode' => '3276020003'],
            ['nama' => 'Tirtajaya',      'kode' => '3276020004'],
            ['nama' => 'Bakti Jaya',     'kode' => '3276020005'],
            ['nama' => 'Cisalak',        'kode' => '3276020006'],
        ];
        foreach ($kelSukmajaya as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecSukmajaya->id]));
        }

        $kecDepokJaya = WilayahKecamatan::create([
            'kabupaten_id' => $kotaDepok->id,
            'nama'         => 'Pancoran Mas',
            'kode'         => '3276030',
        ]);
        $kelDepokJaya = [
            ['nama' => 'Depok',          'kode' => '3276030001'],
            ['nama' => 'Depok Jaya',     'kode' => '3276030002'],
            ['nama' => 'Pancoran Mas',   'kode' => '3276030003'],
            ['nama' => 'Mampang',        'kode' => '3276030004'],
            ['nama' => 'Rangkapan Jaya', 'kode' => '3276030005'],
            ['nama' => 'Rangkapan Jaya Baru', 'kode' => '3276030006'],
        ];
        foreach ($kelDepokJaya as $kel) {
            WilayahKelurahan::create(array_merge($kel, ['kecamatan_id' => $kecDepokJaya->id]));
        }
    }
}
