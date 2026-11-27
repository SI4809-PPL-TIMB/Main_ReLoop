<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MitraHarga;
use App\Models\MitraPengepul;
use App\Models\OrderSetor;
use App\Models\Profile;
use App\Models\User;
use App\Models\WilayahKecamatan;
use App\Models\WilayahKelurahan;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 9.1.4 · Testing Order Setor: Jemput atau Antar Sendiri (PPLTIMB-172).
 * Kriteria Penerimaan:
 * 1. Customer memilih mitra, jenis sampah, perkiraan berat, dan metode Jemput atau Antar Sendiri.
 * 2. Opsi Jemput hanya tersedia jika alamat customer (kecamatan_id) ada di area layanan mitra (mitra_area).
 * 3. Harga per kg dikunci saat order dibuat (tidak ikut berubah kalau mitra mengubah harga).
 * 4. Order diteruskan ke mitra dengan status awal 'menunggu'.
 */
class OrderSetorTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $pengepulUser;
    private MitraPengepul $mitra;
    private Category $kategoriPlastik;
    private Category $kategoriKertas;
    private WilayahKecamatan $kecamatanMitra;
    private WilayahKecamatan $kecamatanLuar;
    private WilayahKelurahan $kelurahanCustomerDalamArea;
    private WilayahKelurahan $kelurahanCustomerLuarArea;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->kategoriPlastik = Category::where('slug', 'plastik')->firstOrFail();
        $this->kategoriKertas = Category::where('slug', 'kertas')->first()
            ?? Category::create(['name' => 'Kertas', 'slug' => 'kertas', 'type' => Category::TYPE_DAUR_ULANG, 'sort_order' => 2]);

        $kecamatans = WilayahKecamatan::with('kelurahan')->take(2)->get();
        $this->kecamatanMitra = $kecamatans[0];
        $this->kecamatanLuar = $kecamatans[1];

        $this->kelurahanCustomerDalamArea = $this->kecamatanMitra->kelurahan->first();
        $this->kelurahanCustomerLuarArea = $this->kecamatanLuar->kelurahan->first();

        // Akun Mitra Pengepul Terverifikasi
        $this->pengepulUser = User::factory()->create(['role' => User::PENGEPUL]);
        $this->mitra = $this->pengepulUser->mitra()->create([
            'nama_usaha'     => 'Bank Sampah Hijau Lestari',
            'telepon'        => '081234567890',
            'alamat'         => 'Jl. Margonda Raya No. 45',
            'kelurahan_id'   => $this->kelurahanCustomerDalamArea->id,
            'layanan_jemput' => true,
            'layanan_antar'  => true,
        ]);
        $this->mitra->forceFill(['status' => MitraPengepul::TERVERIFIKASI])->save();

        // Layanan area: hanya kecamatanMitra
        $this->mitra->area()->attach($this->kecamatanMitra->id);

        // Atur harga sampah mitra
        MitraHarga::create([
            'mitra_id'     => $this->mitra->id,
            'category_id'  => $this->kategoriPlastik->id,
            'harga_per_kg' => 3500,
        ]);
        MitraHarga::create([
            'mitra_id'     => $this->mitra->id,
            'category_id'  => $this->kategoriKertas->id,
            'harga_per_kg' => 2000,
        ]);

        // Customer
        $this->customer = User::factory()->create();
        Profile::create([
            'user_id'      => $this->customer->id,
            'kelurahan_id' => $this->kelurahanCustomerDalamArea->id,
            'patokan'      => 'Pagar hitam samping pos satpam',
        ]);
    }

    public function test_guest_harus_login_untuk_mengakses_form_setor(): void
    {
        $response = $this->get(route('setor.create', $this->mitra));
        $response->assertRedirect(route('login'));
    }

    public function test_customer_dapat_melihat_halaman_form_setor_mitra_terverifikasi(): void
    {
        $response = $this->actingAs($this->customer)->get(route('setor.create', $this->mitra));

        $response->assertOk();
        $response->assertSee('Setor Sampah');
        $response->assertSee('Bank Sampah Hijau Lestari');
        $response->assertSee('Plastik');
        $response->assertSee('Rp 3.500');
        $response->assertSee('Jemput ke Rumah');
        $response->assertSee('Antar Sendiri ke Lapak');
        // Pastikan script interaktif (chips dan hitung otomatis) ter-render ke client
        $response->assertSee('hitungEstimasi');
        $response->assertSee('DOMContentLoaded');
    }

    public function test_form_setor_gagal_jika_mitra_belum_terverifikasi(): void
    {
        $this->mitra->forceFill(['status' => MitraPengepul::MENUNGGU])->save();

        $response = $this->actingAs($this->customer)->get(route('setor.create', $this->mitra));
        $response->assertNotFound();
    }

    public function test_opsi_jemput_berhasil_jika_alamat_customer_di_dalam_area_layanan_mitra(): void
    {
        $payload = [
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'metode'          => 'jemput',
            'patokan'         => 'Pagar hitam depan pos satpam',
            'catatan'         => 'Ada di rumah jam 2 siang',
        ];

        $response = $this->actingAs($this->customer)->post(route('setor.store', $this->mitra), $payload);

        $order = OrderSetor::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('setor.show', $order->order_number));

        $this->assertDatabaseHas('order_setor', [
            'id'              => $order->id,
            'user_id'         => $this->customer->id,
            'mitra_id'        => $this->mitra->id,
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'harga_per_kg'    => 3500,
            'total_estimasi'  => 17500,
            'metode'          => 'jemput',
            'status'          => OrderSetor::STATUS_MENUNGGU,
        ]);
        $this->assertTrue(str_starts_with($order->order_number, 'ST-'));
    }

    public function test_opsi_jemput_ditolak_jika_alamat_customer_di_luar_area_layanan_mitra(): void
    {
        // Pindahkan profil customer ke kecamatan di luar area layanan mitra
        $this->customer->profile->update([
            'kelurahan_id' => $this->kelurahanCustomerLuarArea->id,
        ]);

        $payload = [
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'metode'          => 'jemput',
        ];

        $response = $this->actingAs($this->customer)->post(route('setor.store', $this->mitra), $payload);

        $response->assertSessionHasErrors('metode');
        $this->assertDatabaseCount('order_setor', 0);
    }

    public function test_opsi_antar_sendiri_berhasil_meskipun_alamat_di_luar_area_jemput(): void
    {
        $this->customer->profile->update([
            'kelurahan_id' => $this->kelurahanCustomerLuarArea->id,
        ]);

        $payload = [
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 8,
            'metode'          => 'antar',
        ];

        $response = $this->actingAs($this->customer)->post(route('setor.store', $this->mitra), $payload);

        $order = OrderSetor::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('setor.show', $order->order_number));

        $this->assertDatabaseHas('order_setor', [
            'id'              => $order->id,
            'metode'          => 'antar',
            'perkiraan_berat' => 8,
            'total_estimasi'  => 28000,
            'status'          => OrderSetor::STATUS_MENUNGGU,
        ]);
    }

    public function test_harga_per_kg_dikunci_saat_order_dibuat_dan_tidak_berubah_saat_mitra_mengubah_harga(): void
    {
        // 1. Buat order saat harga plastik Rp 3.500
        $payload = [
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 10,
            'metode'          => 'antar',
        ];

        $this->actingAs($this->customer)->post(route('setor.store', $this->mitra), $payload);

        $order = OrderSetor::first();
        $this->assertEquals(3500, $order->harga_per_kg);
        $this->assertEquals(35000, $order->total_estimasi);

        // 2. Mitra kemudian menurunkan harga plastik menjadi Rp 2.000
        MitraHarga::where('mitra_id', $this->mitra->id)
            ->where('category_id', $this->kategoriPlastik->id)
            ->update(['harga_per_kg' => 2000]);

        // 3. Pastikan harga pada order lama tetap Rp 3.500 dan total estimasi tetap Rp 35.000
        $orderFresh = $order->fresh();
        $this->assertEquals(3500, $orderFresh->harga_per_kg);
        $this->assertEquals(35000, $orderFresh->total_estimasi);

        // 4. Pastikan halaman detail menampilkan harga terkunci Rp 3.500
        $response = $this->actingAs($this->customer)->get(route('setor.show', $order->order_number));
        $response->assertOk();
        $response->assertSee('Rp 3.500');
        $response->assertSee('Rp 35.000');
    }

    public function test_halaman_status_order_dapat_diakses_customer_dan_mitra_tetapi_403_untuk_user_lain(): void
    {
        $order = OrderSetor::create([
            'order_number'    => OrderSetor::generateOrderNumber(),
            'user_id'         => $this->customer->id,
            'mitra_id'        => $this->mitra->id,
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'harga_per_kg'    => 3500,
            'total_estimasi'  => 17500,
            'metode'          => 'jemput',
            'status'          => OrderSetor::STATUS_MENUNGGU,
        ]);

        // Customer pemilik dapat melihat
        $responseCustomer = $this->actingAs($this->customer)->get(route('setor.show', $order->order_number));
        $responseCustomer->assertOk();
        $responseCustomer->assertSee($order->order_number);
        $responseCustomer->assertSee('Menunggu Konfirmasi Pengepul');

        // Mitra yang bersangkutan dapat melihat
        $responseMitra = $this->actingAs($this->pengepulUser)->get(route('setor.show', $order->order_number));
        $responseMitra->assertOk();
        $responseMitra->assertSee($order->order_number);

        // User lain ditolak 403
        $userLain = User::factory()->create();
        $responseUserLain = $this->actingAs($userLain)->get(route('setor.show', $order->order_number));
        $responseUserLain->assertForbidden();
    }

    public function test_customer_dapat_membatalkan_order_saat_status_masih_menunggu(): void
    {
        $order = OrderSetor::create([
            'order_number'    => OrderSetor::generateOrderNumber(),
            'user_id'         => $this->customer->id,
            'mitra_id'        => $this->mitra->id,
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'harga_per_kg'    => 3500,
            'total_estimasi'  => 17500,
            'metode'          => 'jemput',
            'status'          => OrderSetor::STATUS_MENUNGGU,
        ]);

        $response = $this->actingAs($this->customer)->post(route('setor.batal', $order->order_number), [
            'alasan' => 'Ingin menambah sampah lain terlebih dahulu',
        ]);

        $response->assertRedirect(route('setor.show', $order->order_number));
        $orderFresh = $order->fresh();
        $this->assertEquals(OrderSetor::STATUS_DIBATALKAN, $orderFresh->status);
        $this->assertNotNull($orderFresh->cancelled_at);
        $this->assertEquals('Ingin menambah sampah lain terlebih dahulu', $orderFresh->cancel_reason);

        // Halaman show menampilkan banner dibatalkan
        $responseShow = $this->actingAs($this->customer)->get(route('setor.show', $order->order_number));
        $responseShow->assertSee('Order Dibatalkan');
        $responseShow->assertSee('Ingin menambah sampah lain terlebih dahulu');
    }

    public function test_order_tidak_dapat_dibatalkan_jika_sudah_diproses_mitra(): void
    {
        $order = OrderSetor::create([
            'order_number'    => OrderSetor::generateOrderNumber(),
            'user_id'         => $this->customer->id,
            'mitra_id'        => $this->mitra->id,
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'harga_per_kg'    => 3500,
            'total_estimasi'  => 17500,
            'metode'          => 'jemput',
            'status'          => OrderSetor::STATUS_DITERIMA,
        ]);

        $response = $this->actingAs($this->customer)->post(route('setor.batal', $order->order_number));

        $orderFresh = $order->fresh();
        $this->assertEquals(OrderSetor::STATUS_DITERIMA, $orderFresh->status);
        $this->assertNull($orderFresh->cancelled_at);
    }

    public function test_order_setor_muncul_pada_halaman_aktivitas_customer(): void
    {
        $order = OrderSetor::create([
            'order_number'    => OrderSetor::generateOrderNumber(),
            'user_id'         => $this->customer->id,
            'mitra_id'        => $this->mitra->id,
            'category_id'     => $this->kategoriPlastik->id,
            'perkiraan_berat' => 5,
            'harga_per_kg'    => 3500,
            'total_estimasi'  => 17500,
            'metode'          => 'jemput',
            'status'          => OrderSetor::STATUS_MENUNGGU,
        ]);

        // Tab Berjalan dengan filter setor
        $response = $this->actingAs($this->customer)->get(route('aktivitas', ['tab' => 'berjalan', 'f' => 'setor']));
        $response->assertOk();
        $response->assertSee('Setor Plastik · 5 kg');
        $response->assertSee('Menunggu konfirmasi');
        $response->assertSee('Bank Sampah Hijau Lestari');

        // Selesaikan order
        $order->update(['status' => OrderSetor::STATUS_SELESAI]);

        // Muncul di tab Riwayat
        $responseRiwayat = $this->actingAs($this->customer)->get(route('aktivitas', ['tab' => 'riwayat', 'f' => 'setor']));
        $responseRiwayat->assertOk();
        $responseRiwayat->assertSee('Setor Plastik · 5 kg');
        $responseRiwayat->assertSee('Setoran selesai');
    }
}
