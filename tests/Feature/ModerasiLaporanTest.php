<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 11.1.4 — Testing alur lapor dan moderasi admin (PPLTIMB-152).
 */
class ModerasiLaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $userPelapor;
    private User $userTerlapor;
    private User $userLain;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->admin = tap(User::factory()->create(['name' => 'Admin Reloop', 'email' => 'admin@reloop.test']), function (User $u) {
            $u->forceFill(['role' => User::ADMIN])->save();
        });

        $this->userPelapor = User::factory()->create(['name' => 'Budi Pelapor']);
        $this->userPelapor->profile()->create();

        $this->userTerlapor = User::factory()->create(['name' => 'Joko Terlapor']);
        $this->userTerlapor->profile()->create();

        $this->userLain = User::factory()->create(['name' => 'Siti Pengamat']);
        $this->userLain->profile()->create();

        $kategori = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->userTerlapor->id,
            'category_id'   => $kategori->id,
            'title'         => 'Sepeda Polygon Bekas Mulus',
            'description'   => 'Kondisi normal siap gowes',
            'price'         => 750000,
            'condition'     => 'baik',
            'status'        => Listing::AKTIF,
            'is_negotiable' => true,
        ]);
    }

    public function test_guest_tidak_bisa_mengakses_halaman_lapor_dan_moderasi(): void
    {
        $this->get(route('lapor.create', ['tipe' => 'listing', 'id' => $this->listing->id]))
            ->assertRedirect(route('login'));

        $this->get(route('laporan.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.moderasi.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_biasa_dilarang_mengakses_halaman_moderasi_admin(): void
    {
        $this->actingAs($this->userPelapor)
            ->get(route('admin.moderasi.index'))
            ->assertForbidden();
    }

    public function test_admin_bisa_mengakses_halaman_moderasi(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.moderasi.index'))
            ->assertOk()
            ->assertSee('Moderasi');
    }

    public function test_pengguna_bisa_melaporkan_barang_milik_orang_lain(): void
    {
        $response = $this->actingAs($this->userPelapor)->post(route('lapor.store'), [
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'description'     => 'Barang ini ternyata bajakan dan deskripsi tidak sesuai fakta.',
        ]);

        $response->assertRedirect(route('laporan.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'status'          => 'pending',
        ]);
    }

    public function test_pengguna_tidak_bisa_melaporkan_barang_milik_sendiri(): void
    {
        $response = $this->actingAs($this->userTerlapor)->post(route('lapor.store'), [
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'lainnya',
            'description'     => 'Saya mencoba lapor barang saya sendiri.',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reports', [
            'reporter_id'     => $this->userTerlapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
        ]);
    }

    public function test_pengguna_bisa_melaporkan_pengguna_lain(): void
    {
        $response = $this->actingAs($this->userPelapor)->post(route('lapor.store'), [
            'reportable_type' => 'user',
            'reportable_id'   => $this->userTerlapor->id,
            'reason'          => 'perilaku_buruk',
            'description'     => 'Pengguna ini berkata kasar saat negosiasi.',
        ]);

        $response->assertRedirect(route('laporan.index'));

        $this->assertDatabaseHas('reports', [
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'user',
            'reportable_id'   => $this->userTerlapor->id,
            'reason'          => 'perilaku_buruk',
            'status'          => 'pending',
        ]);
    }

    public function test_pengguna_tidak_bisa_melaporkan_diri_sendiri(): void
    {
        $response = $this->actingAs($this->userPelapor)->post(route('lapor.store'), [
            'reportable_type' => 'user',
            'reportable_id'   => $this->userPelapor->id,
            'reason'          => 'lainnya',
            'description'     => 'Lapor diri sendiri.',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reports', [
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'user',
            'reportable_id'   => $this->userPelapor->id,
        ]);
    }

    public function test_hanya_pihak_terlibat_yang_bisa_melaporkan_pesanan(): void
    {
        $order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->userPelapor->id,
            'seller_id'        => $this->userTerlapor->id,
            'agreed_price'     => 750000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10',
            'status'           => Order::STATUS_DIBAYAR,
        ]);

        // Pengguna luar (userLain) mencoba lapor pesanan
        $responseLain = $this->actingAs($this->userLain)->post(route('lapor.store'), [
            'reportable_type' => 'order',
            'reportable_id'   => $order->id,
            'reason'          => 'sengketa_transaksi',
            'description'     => 'Saya hanya orang lain yang coba lapor.',
        ]);

        $responseLain->assertForbidden();
        $this->assertDatabaseMissing('reports', [
            'reporter_id'     => $this->userLain->id,
            'reportable_type' => 'order',
            'reportable_id'   => $order->id,
        ]);

        // Pembeli (userPelapor) berhak lapor
        $responseBuyer = $this->actingAs($this->userPelapor)->post(route('lapor.store'), [
            'reportable_type' => 'order',
            'reportable_id'   => $order->id,
            'reason'          => 'sengketa_transaksi',
            'description'     => 'Penjual tidak datang ke lokasi COD dan tidak dapat dihubungi.',
        ]);

        $responseBuyer->assertRedirect(route('laporan.index'));
        $this->assertDatabaseHas('reports', [
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'order',
            'reportable_id'   => $order->id,
            'reason'          => 'sengketa_transaksi',
        ]);
    }

    public function test_pelapor_bisa_melihat_riwayat_laporannya(): void
    {
        Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'description'     => 'Keterangan laporan saya',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->userPelapor)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Laporan Saya')
            ->assertSee('Keterangan laporan saya')
            ->assertSee('Sedang Ditinjau');
    }

    public function test_admin_bisa_menonaktifkan_barang(): void
    {
        $report = Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'description'     => 'Barang palsu',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.moderasi.deactive_listing', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->listing->refresh();
        $this->assertEquals(Listing::DIHAPUS, $this->listing->status);

        $report->refresh();
        $this->assertEquals(Report::STATUS_RESOLVED, $report->status);
        $this->assertEquals(Report::ACTION_DEACTIVATE_LISTING, $report->action_taken);
    }

    public function test_admin_bisa_menonaktifkan_akun_pengguna(): void
    {
        $report = Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'user',
            'reportable_id'   => $this->userTerlapor->id,
            'reason'          => 'perilaku_buruk',
            'description'     => 'Spam dan pelecehan',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.moderasi.deactive_user', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->userTerlapor->refresh();
        $this->assertFalse((bool) $this->userTerlapor->is_active);

        $report->refresh();
        $this->assertEquals(Report::STATUS_RESOLVED, $report->status);
        $this->assertEquals(Report::ACTION_DEACTIVATE_USER, $report->action_taken);
    }

    public function test_admin_putusan_sengketa_cairkan_ke_penjual(): void
    {
        $order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->userPelapor->id,
            'seller_id'        => $this->userTerlapor->id,
            'agreed_price'     => 750000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10',
            'status'           => Order::STATUS_DISERAHKAN,
        ]);

        $report = Report::create([
            'reporter_id'     => $this->userTerlapor->id,
            'reportable_type' => 'order',
            'reportable_id'   => $order->id,
            'reason'          => 'sengketa_transaksi',
            'description'     => 'Barang sudah diserahkan tapi pembeli tidak mau klik konfirmasi.',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.moderasi.resolve_dispute', $report), [
                'decision' => 'payout_seller',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_SELESAI, $order->status);

        $report->refresh();
        $this->assertEquals(Report::STATUS_RESOLVED, $report->status);
        $this->assertEquals(Report::ACTION_PAYOUT_SELLER, $report->action_taken);
    }

    public function test_admin_putusan_sengketa_refund_ke_pembeli(): void
    {
        $this->listing->forceFill(['status' => Listing::DIPESAN])->save();

        $order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->userPelapor->id,
            'seller_id'        => $this->userTerlapor->id,
            'agreed_price'     => 750000,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Jl. Merdeka No. 10',
            'status'           => Order::STATUS_DIBAYAR,
        ]);

        $report = Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'order',
            'reportable_id'   => $order->id,
            'reason'          => 'sengketa_transaksi',
            'description'     => 'Penjual menolak serah terima barang.',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.moderasi.resolve_dispute', $report), [
                'decision' => 'refund_buyer',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);

        $this->listing->refresh();
        $this->assertEquals(Listing::AKTIF, $this->listing->status);

        $report->refresh();
        $this->assertEquals(Report::STATUS_RESOLVED, $report->status);
        $this->assertEquals(Report::ACTION_REFUND_BUYER, $report->action_taken);
    }

    public function test_admin_bisa_mengabaikan_laporan(): void
    {
        $report = Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'lainnya',
            'description'     => 'Laporan tidak relevan',
            'status'          => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.moderasi.dismiss', $report), [
                'notes' => 'Laporan tidak terbukti melanggar ketentuan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('info');

        $report->refresh();
        $this->assertEquals(Report::STATUS_REJECTED, $report->status);
        $this->assertEquals(Report::ACTION_DISMISSED, $report->action_taken);
        $this->assertEquals('Laporan tidak terbukti melanggar ketentuan.', $report->admin_notes);
    }

    public function test_pengguna_tidak_bisa_mengirim_laporan_duplikat_saat_masih_pending(): void
    {
        Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'description'     => 'Laporan awal yang masih menunggu.',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->userPelapor)->post(route('lapor.store'), [
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'description'     => 'Laporan kedua untuk barang yang sama.',
        ]);

        $response->assertRedirect(route('laporan.index'));
        $response->assertSessionHas('info');

        // Pastikan hanya ada 1 laporan di database, bukan 2
        $this->assertCount(1, Report::where('reportable_type', 'listing')->where('reportable_id', $this->listing->id)->get());
    }

    public function test_form_lapor_otomatis_redirect_ke_laporan_jika_masih_pending(): void
    {
        Report::create([
            'reporter_id'     => $this->userPelapor->id,
            'reportable_type' => 'listing',
            'reportable_id'   => $this->listing->id,
            'reason'          => 'penipuan',
            'description'     => 'Sudah dilaporkan sebelumnya.',
            'status'          => 'pending',
        ]);

        $response = $this->actingAs($this->userPelapor)->get(route('lapor.create', [
            'tipe' => 'listing',
            'id'   => $this->listing->id,
        ]));

        $response->assertRedirect(route('laporan.index'));
        $response->assertSessionHas('info');
    }
}
