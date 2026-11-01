<?php

namespace Tests\Feature;

use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ImportWilayahTest extends TestCase
{
    use RefreshDatabase;

    private function csv(): string
    {
        $f = tempnam(sys_get_temp_dir(), 'wil');
        file_put_contents($f, implode("\n", [
            '32,Jawa Barat', '32.01,Kabupaten Bogor', '32.01.01,Cibinong', '32.01.01.1001,Cibinong', '32.01.01.1099,Desa Baru',
            '34,DI Yogyakarta', '34.04,Kabupaten Sleman', '34.04.01,Depok', '34.04.01.1001,Caturtunggal',
        ]));

        return $f;
    }

    public function test_impor_menambah_wilayah_baru_dan_aman_diulang(): void
    {
        $f = $this->csv();
        $this->artisan('wilayah:impor', ['file' => $f])->assertSuccessful();
        $jumlah = fn () => [DB::table('wilayah_provinsi')->count(), DB::table('wilayah_kelurahan')->count()];
        $setelahPertama = $jumlah();

        $this->assertDatabaseHas('wilayah_kelurahan', ['kode' => '3404011001', 'nama' => 'Caturtunggal']);
        $this->artisan('wilayah:impor', ['file' => $f]);
        $this->assertSame($setelahPertama, $jumlah());
    }

    public function test_seed_lama_dicocokkan_lewat_nama_tanpa_duplikat_dan_id_tetap(): void
    {
        $this->seed(WilayahSeeder::class);
        $lama = DB::table('wilayah_kelurahan')->where('nama', 'Cibinong')->first();
        $kecLama = DB::table('wilayah_kecamatan')->where('nama', 'Cibinong')->count();

        $this->artisan('wilayah:impor', ['file' => $this->csv()])->assertSuccessful();

        $baru = DB::table('wilayah_kelurahan')->where('nama', 'Cibinong')->get();
        $this->assertCount(1, $baru);
        $this->assertSame($lama->id, $baru[0]->id);
        $this->assertSame('3201011001', $baru[0]->kode);
        $this->assertSame($kecLama, DB::table('wilayah_kecamatan')->where('nama', 'Cibinong')->count());
    }

    public function test_file_tidak_ada_gagal(): void
    {
        $this->artisan('wilayah:impor', ['file' => '/tidak/ada.csv'])->assertFailed();
    }
}
