<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 7.2.4 — Testing alur kelola pesanan: status, dana tertahan, komisi, pembatalan dan penyelesaian otomatis (PPLTIMB-101).
 */
class KelolaPesananTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;
    private User $pembeli;
    private User $userLain;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([WilayahSeeder::class, CategorySeeder::class]);

        $this->penjual = User::factory()->create(['name' => 'Siti Rahma']);
        $this->penjual->profile()->create([
            'total_sold'   => 0,
            'streak_count' => 0,
            'total_points' => 0,
        ]);

        $this->pembeli = User::factory()->create(['name' => 'Bima Sena']);
        $this->pembeli->profile()->create([
            'streak_count' => 0,
            'total_points' => 0,
        ]);

        $this->userLain = User::factory()->create(['name' => 'Pengguna Asing']);
        $this->userLain->profile()->create();

        $kategori = Category::where('type', 'bekas')->first();

        $this->listing = Listing::create([
            'user_id'       => $this->penjual->id,
            'category_id'   => $kategori->id,
            'title'         => 'Kursi Kayu Jati Ukir',
            'description'   => 'Kayu jati solid mulus',
            'price'         => 350000,
            'condition'     => 'baik',
            'status'        => Listing::DIPESAN,
            'is_negotiable' => true,
        ]);
    }

    private function createOrder(string $status = Order::STATUS_DIBAYAR, float $price = 320000): Order
    {
        $order = Order::create([
            'order_number'     => Order::generateOrderNumber(),
            'listing_id'       => $this->listing->id,
            'buyer_id'         => $this->pembeli->id,
            'seller_id'        => $this->penjual->id,
            'agreed_price'     => $price,
            'delivery_method'  => Order::DELIVERY_COD,
            'delivery_address' => 'Sukamaju',
            'status'           => $status,
            'seller_confirmed_at' => $status === Order::STATUS_DISERAHKAN ? now() : null,
            'auto_complete_at'    => $status === Order::STATUS_DISERAHKAN ? now()->addDays(3) : null,
        ]);

        $platformFee = round($price * Payment::KOMISI_RATE);
        Payment::create([
            'order_id'     => $order->id,
            'amount'       => $price,
            'platform_fee' => $platformFee,
            'method'       => Payment::METHOD_VA,
            'bank'         => 'bca',
            'status'       => $status === Order::STATUS_MENUNGGU_BAYAR ? Payment::STATUS_MENUNGGU : Payment::STATUS_BERHASIL,
            'paid_at'      => $status === Order::STATUS_MENUNGGU_BAYAR ? null : now(),
        ]);

        return $order;
    }

    public function test_detail_pesanan_menampilkan_banner_dana_tertahan_dan_rincian_komisi_3_persen(): void
    {
        $order = $this->createOrder(Order::STATUS_DIBAYAR, 320000);

        // Akses sebagai pembeli
        $responsePembeli = $this->actingAs($this->pembeli)->get(route('order.show', $order->order_number));
        $responsePembeli->assertOk();
        $responsePembeli->assertSee('Dana tertahan');
        $responsePembeli->assertSee('Aman di Reloop sampai kamu konfirmasi barang diterima.');
        $responsePembeli->assertSee('Chat penjual');
        $responsePembeli->assertSee('Batalkan pesanan');

        // Akses sebagai penjual
        $responsePenjual = $this->actingAs($this->penjual)->get(route('order.show', $order->order_number));
        $responsePenjual->assertOk();
        $responsePenjual->assertSee('Dana tertahan');
        $responsePenjual->assertSee('Cair ke saldomu setelah pembeli konfirmasi barang diterima.');
        $responsePenjual->assertSee('Tandai sudah diserahkan');
        $responsePenjual->assertSee('komisi platform 3%');
        $responsePenjual->assertSee('Rp 310.400'); // 320.000 - 3% (9.600)
    }

    public function test_penjual_dapat_menandai_barang_telah_diserahkan(): void
    {
        $order = $this->createOrder(Order::STATUS_DIBAYAR);

        $response = $this->actingAs($this->penjual)->post(route('order.diserahkan', $order->order_number));

        $response->assertRedirect(route('order.show', $order->order_number));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_DISERAHKAN, $order->status);
        $this->assertNotNull($order->seller_confirmed_at);
        $this->assertNotNull($order->auto_complete_at);
        $this->assertTrue($order->auto_complete_at->isAfter(now()->addDays(2)));
    }

    public function test_selain_penjual_dilarang_menandai_barang_diserahkan(): void
    {
        $order = $this->createOrder(Order::STATUS_DIBAYAR);

        // Pembeli mencoba menandai diserahkan -> harus 403
        $responsePembeli = $this->actingAs($this->pembeli)->post(route('order.diserahkan', $order->order_number));
        $responsePembeli->assertForbidden();

        // User lain mencoba -> harus 403
        $responseAsing = $this->actingAs($this->userLain)->post(route('order.diserahkan', $order->order_number));
        $responseAsing->assertForbidden();

        $this->assertEquals(Order::STATUS_DIBAYAR, $order->fresh()->status);
    }

    public function test_penjual_tidak_bisa_menandai_diserahkan_jika_pesanan_belum_dibayar(): void
    {
        $order = $this->createOrder(Order::STATUS_MENUNGGU_BAYAR);

        $response = $this->actingAs($this->penjual)->postJson(route('order.diserahkan', $order->order_number));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Hanya pesanan yang sudah dibayar yang dapat diserahkan.');

        $this->assertEquals(Order::STATUS_MENUNGGU_BAYAR, $order->fresh()->status);
    }

    public function test_pembeli_dapat_mengonfirmasi_penerimaan_barang_dan_menyelesaikan_pesanan(): void
    {
        $order = $this->createOrder(Order::STATUS_DISERAHKAN);

        $response = $this->actingAs($this->pembeli)->post(route('order.terima', $order->order_number));

        $response->assertRedirect(route('order.show', $order->order_number));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(Order::STATUS_SELESAI, $order->status);
        $this->assertNotNull($order->buyer_confirmed_at);

        // Status listing harus berubah menjadi terjual
        $this->assertEquals(Listing::TERJUAL, $this->listing->fresh()->status);

        // Penjual mendapat penambahan riwayat penjualan & streak/poin
        $this->penjual->profile->refresh();
        $this->assertEquals(1, $this->penjual->profile->total_sold);
        $this->assertEquals(1, $this->penjual->profile->streak_count);
        $this->assertEquals(50, $this->penjual->profile->total_points);

        // Pembeli mendapat streak & poin
        $this->pembeli->profile->refresh();
        $this->assertEquals(1, $this->pembeli->profile->streak_count);
        $this->assertEquals(30, $this->pembeli->profile->total_points);
    }

    public function test_selain_pembeli_dilarang_mengonfirmasi_penerimaan_barang(): void
    {
        $order = $this->createOrder(Order::STATUS_DISERAHKAN);

        // Penjual tidak bisa konfirmasi terima barang
        $responsePenjual = $this->actingAs($this->penjual)->post(route('order.terima', $order->order_number));
        $responsePenjual->assertForbidden();

        // User lain dilarang
        $responseAsing = $this->actingAs($this->userLain)->post(route('order.terima', $order->order_number));
        $responseAsing->assertForbidden();

        $this->assertEquals(Order::STATUS_DISERAHKAN, $order->fresh()->status);
    }

    public function test_pembeli_tidak_bisa_konfirmasi_terima_jika_barang_belum_diserahkan(): void
    {
        $order = $this->createOrder(Order::STATUS_DIBAYAR);

        $response = $this->actingAs($this->pembeli)->postJson(route('order.terima', $order->order_number));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Pesanan belum diserahkan oleh penjual.');

        $this->assertEquals(Order::STATUS_DIBAYAR, $order->fresh()->status);
    }

    public function test_pembeli_atau_penjual_dapat_membatalkan_pesanan_sebelum_diserahkan(): void
    {
        // 1. Dibatalkan oleh pembeli saat masih menunggu bayar
        $order1 = $this->createOrder(Order::STATUS_MENUNGGU_BAYAR);
        $response1 = $this->actingAs($this->pembeli)->post(route('order.batal', $order1->order_number), [
            'reason' => 'Ingin ganti alamat serah terima',
        ]);
        $response1->assertRedirect(route('order.show', $order1->order_number));
        $order1->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order1->status);
        $this->assertEquals('Ingin ganti alamat serah terima', $order1->cancel_reason);
        $this->assertEquals(Listing::AKTIF, $this->listing->fresh()->status); // Barang aktif lagi!

        // 2. Dibatalkan oleh penjual saat sudah dibayar (sebelum diserahkan)
        $this->listing->update(['status' => Listing::DIPESAN]);
        $order2 = $this->createOrder(Order::STATUS_DIBAYAR);
        $response2 = $this->actingAs($this->penjual)->post(route('order.batal', $order2->order_number));
        $response2->assertRedirect(route('order.show', $order2->order_number));
        $order2->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order2->status);
        $this->assertEquals(Listing::AKTIF, $this->listing->fresh()->status); // Barang aktif lagi!
    }

    public function test_pesanan_yang_sudah_diserahkan_atau_selesai_tidak_dapat_dibatalkan(): void
    {
        $order = $this->createOrder(Order::STATUS_DISERAHKAN);

        $response = $this->actingAs($this->pembeli)->postJson(route('order.batal', $order->order_number));
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Pesanan yang sudah diserahkan atau selesai tidak dapat dibatalkan.');

        $this->assertEquals(Order::STATUS_DISERAHKAN, $order->fresh()->status);
    }

    public function test_scheduler_membatalkan_pesanan_menunggu_bayar_yang_kedaluwarsa(): void
    {
        Carbon::setTestNow('2026-11-17 10:00:00');

        $orderKadaluwarsa = $this->createOrder(Order::STATUS_MENUNGGU_BAYAR);
        // Set waktu pembuatan lebih dari 24 jam yang lalu
        $orderKadaluwarsa->created_at = now()->subHours(25);
        $orderKadaluwarsa->saveQuietly();

        $orderBaru = $this->createOrder(Order::STATUS_MENUNGGU_BAYAR);
        $orderBaru->created_at = now()->subHours(5);
        $orderBaru->saveQuietly();

        // Jalankan artisan command scheduler
        $this->artisan('orders:process-auto')
            ->assertSuccessful()
            ->expectsOutputToContain('1 pesanan dibatalkan otomatis');

        $this->assertEquals(Order::STATUS_DIBATALKAN, $orderKadaluwarsa->fresh()->status);
        $this->assertEquals(Listing::AKTIF, $this->listing->fresh()->status);
        $this->assertEquals(Payment::STATUS_KEDALUWARSA, $orderKadaluwarsa->payment->fresh()->status);

        // Pesanan baru tetap menunggu bayar
        $this->assertEquals(Order::STATUS_MENUNGGU_BAYAR, $orderBaru->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_scheduler_menyelesaikan_pesanan_diserahkan_setelah_lewat_batas_auto_complete(): void
    {
        Carbon::setTestNow('2026-11-17 10:00:00');

        $orderLewatBatas = $this->createOrder(Order::STATUS_DISERAHKAN);
        // Set auto_complete_at sudah lewat (misal 1 jam yang lalu)
        $orderLewatBatas->update([
            'seller_confirmed_at' => now()->subDays(3)->subHour(),
            'auto_complete_at'    => now()->subHour(),
        ]);

        $orderMasihAdaWaktu = $this->createOrder(Order::STATUS_DISERAHKAN);
        $orderMasihAdaWaktu->update([
            'seller_confirmed_at' => now()->subDay(),
            'auto_complete_at'    => now()->addDays(2),
        ]);

        // Jalankan artisan command scheduler
        $this->artisan('orders:process-auto')
            ->assertSuccessful()
            ->expectsOutputToContain('1 pesanan diselesaikan otomatis');

        $this->assertEquals(Order::STATUS_SELESAI, $orderLewatBatas->fresh()->status);
        $this->assertNotNull($orderLewatBatas->fresh()->buyer_confirmed_at);
        $this->assertEquals(Listing::TERJUAL, $this->listing->fresh()->status);

        // Pesanan yang masih ada waktu tetap dalam status diserahkan
        $this->assertEquals(Order::STATUS_DISERAHKAN, $orderMasihAdaWaktu->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_user_lain_dilarang_mengakses_detail_pesanan(): void
    {
        $order = $this->createOrder(Order::STATUS_DIBAYAR);

        $response = $this->actingAs($this->userLain)->get(route('order.show', $order->order_number));
        $response->assertForbidden();
    }

    public function test_chat_url_penjual_mengarahkan_ke_percakapan_atau_inbox_tanpa_error(): void
    {
        $order = $this->createOrder(Order::STATUS_DIBAYAR);

        // Buat percakapan antara pembeli dan penjual untuk listing ini
        $conversation = \App\Models\Conversation::create([
            'listing_id' => $this->listing->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $responsePenjual = $this->actingAs($this->penjual)->get(route('order.show', $order->order_number));
        $responsePenjual->assertOk();
        $responsePenjual->assertSee(route('pesan.show', $conversation));

        $responsePembeli = $this->actingAs($this->pembeli)->get(route('order.show', $order->order_number));
        $responsePembeli->assertOk();
        $responsePembeli->assertSee(route('pesan.show', $conversation));
    }

    public function test_pembatalan_dengan_alasan_kosong_menggunakan_alasan_default(): void
    {
        $order = $this->createOrder(Order::STATUS_MENUNGGU_BAYAR);

        $response = $this->actingAs($this->pembeli)->post(route('order.batal', $order->order_number), [
            'reason' => '   ', // spasi kosong
        ]);

        $response->assertRedirect(route('order.show', $order->order_number));
        $order->refresh();
        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->status);
        $this->assertEquals('Dibatalkan oleh pembeli', $order->cancel_reason);
    }

    public function test_scheduler_membatalkan_pesanan_jika_payment_expired_at_terlewati(): void
    {
        $order = $this->createOrder(Order::STATUS_MENUNGGU_BAYAR);
        $order->payment->update(['expired_at' => now()->subMinute()]);

        $this->artisan('orders:process-auto')
            ->assertSuccessful()
            ->expectsOutputToContain('1 pesanan dibatalkan otomatis');

        $this->assertEquals(Order::STATUS_DIBATALKAN, $order->fresh()->status);
        $this->assertEquals(Listing::AKTIF, $this->listing->fresh()->status);
    }
}
