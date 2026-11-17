<?php

namespace Tests\Feature;

use App\Livewire\ChatRoom;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 6.2.4 — Testing fitur Tawar harga: ajukan, terima, tolak, kedaluwarsa (PPLTIMB-63).
 */
class TawarHargaTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;
    private User $pembeli;
    private User $penggunaLain;
    private Listing $barang;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);

        $this->penjual      = User::factory()->create(['name' => 'Budi Penjual']);
        $this->pembeli      = User::factory()->create(['name' => 'Siti Pembeli']);
        $this->penggunaLain = User::factory()->create(['name' => 'Agus Lain']);

        $kategori = Category::where('type', Category::TYPE_BEKAS)->first() ?? Category::first();

        $this->barang = Listing::create([
            'user_id'       => $this->penjual->id,
            'category_id'   => $kategori->id,
            'title'         => 'Meja Kayu Jati',
            'description'   => 'Meja belajar kayu jati kokoh.',
            'price'         => 350000,
            'is_negotiable' => true,
            'condition'     => 'baik',
            'status'        => Listing::AKTIF,
            'published_at'  => now(),
        ]);

        $this->conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);
    }

    // ─────────────────────────── 6.2.2.1 — Ajukan tawaran ───────────────────

    public function test_pembeli_dapat_mengajukan_tawaran_via_livewire(): void
    {
        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->call('toggleTawarForm')
            ->assertSet('showTawarForm', true)
            ->set('tawarAmount', '280000')
            ->call('ajukanTawar')
            ->assertSet('showTawarForm', false)
            ->assertSet('tawarAmount', '');

        $this->assertDatabaseHas('offers', [
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
        ]);
    }

    public function test_tawaran_menyimpan_expires_at_24_jam(): void
    {
        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '200000')
            ->call('ajukanTawar');

        $offer = Offer::where('conversation_id', $this->conversation->id)->first();
        $this->assertNotNull($offer->expires_at);
        $this->assertTrue($offer->expires_at->isFuture());
        $this->assertEqualsWithDelta(
            now()->addHours(24)->timestamp,
            $offer->expires_at->timestamp,
            5 // toleransi 5 detik
        );
    }

    public function test_penjual_tidak_dapat_mengajukan_tawaran(): void
    {
        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '200000')
            ->call('ajukanTawar')
            ->assertForbidden();
    }

    public function test_tawaran_lama_dibatalkan_saat_tawaran_baru_diajukan(): void
    {
        // Tawaran pertama
        $tawarLama = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 300000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '250000')
            ->call('ajukanTawar');

        $this->assertEquals(Offer::DIBATALKAN, $tawarLama->fresh()->status);

        $this->assertDatabaseHas('offers', [
            'conversation_id' => $this->conversation->id,
            'amount'          => 250000,
            'status'          => Offer::MENUNGGU,
        ]);
    }

    public function test_validasi_amount_wajib_diisi(): void
    {
        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '')
            ->call('ajukanTawar')
            ->assertHasErrors(['tawarAmount']);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_validasi_amount_minimal_1000(): void
    {
        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '500')
            ->call('ajukanTawar')
            ->assertHasErrors(['tawarAmount']);
    }

    public function test_tidak_dapat_menawar_barang_tidak_negotiable(): void
    {
        $this->barang->update(['is_negotiable' => false]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '280000')
            ->call('ajukanTawar')
            ->assertHasErrors(['tawarAmount']);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_tidak_dapat_menawar_barang_tidak_aktif(): void
    {
        $this->barang->update(['status' => Listing::TERJUAL]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('tawarAmount', '280000')
            ->call('ajukanTawar')
            ->assertHasErrors(['tawarAmount']);

        $this->assertDatabaseCount('offers', 0);
    }

    // ─────────────────────────── 6.2.2.2 — Respons penjual ──────────────────

    public function test_penjual_dapat_menerima_tawaran(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->call('terimaTawar', $offer->id);

        $this->assertEquals(Offer::DITERIMA, $offer->fresh()->status);
    }

    public function test_penjual_dapat_menolak_tawaran(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->call('tolakTawar', $offer->id);

        $this->assertEquals(Offer::DITOLAK, $offer->fresh()->status);
    }

    public function test_pembeli_tidak_dapat_menerima_atau_menolak_tawaran(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->call('terimaTawar', $offer->id)
            ->assertForbidden();

        $this->assertEquals(Offer::MENUNGGU, $offer->fresh()->status);
    }

    public function test_pengguna_lain_tidak_dapat_merespons_tawaran(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->penggunaLain);

        // penggunaLain bukan bagian dari conversation ini → forbidden di mount
        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->assertForbidden();
    }

    public function test_tidak_bisa_merespons_tawaran_yang_sudah_diproses(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::DITOLAK, // sudah ditolak
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->call('terimaTawar', $offer->id);

        // Tetap ditolak, tidak berubah
        $this->assertEquals(Offer::DITOLAK, $offer->fresh()->status);
    }

    // ─────────────────────────── 6.2 — Kedaluwarsa ───────────────────────────

    public function test_tawaran_kedaluwarsa_ditolak_respons_penjual(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->subHour(), // sudah lewat
        ]);

        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->call('terimaTawar', $offer->id);

        // Harus berubah menjadi kedaluwarsa, bukan diterima
        $this->assertEquals(Offer::KEDALUWARSA, $offer->fresh()->status);
    }

    public function test_tawaran_aktif_ditampilkan_di_chat_room(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->assertSee('280.000')
            ->assertSee('Menunggu respons');
    }

    public function test_tawaran_diterima_ditampilkan_sebagai_harga_deal(): void
    {
        Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::DITERIMA,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->assertSee('Tawaran disepakati')
            ->assertSee('Harga deal')
            ->assertSee('280.000');
    }

    // ─────────────────────────── 6.2 — Route HTTP backup ────────────────────

    public function test_route_tawar_store_hanya_bisa_diakses_pembeli(): void
    {
        $response = $this->actingAs($this->penjual)
            ->post(route('tawar.store', $this->conversation), ['amount' => 200000]);

        $response->assertForbidden();
    }

    public function test_route_tawar_respons_hanya_bisa_diakses_penjual(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 200000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->pembeli)
            ->post(route('tawar.respons', [$this->conversation, $offer]), ['action' => 'diterima']);

        $response->assertForbidden();
    }

    public function test_route_tawar_store_berhasil_simpan_ke_database(): void
    {
        $response = $this->actingAs($this->pembeli)
            ->post(route('tawar.store', $this->conversation), ['amount' => 275000]);

        $response->assertRedirect(route('pesan.show', $this->conversation));

        $this->assertDatabaseHas('offers', [
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 275000,
            'status'          => Offer::MENUNGGU,
        ]);
    }

    public function test_route_tawar_respons_berhasil_mengubah_status(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 275000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->penjual)
            ->post(route('tawar.respons', [$this->conversation, $offer]), ['action' => 'diterima']);

        $response->assertRedirect(route('pesan.show', $this->conversation));
        $this->assertEquals(Offer::DITERIMA, $offer->fresh()->status);
    }

    public function test_guest_diarahkan_ke_login_untuk_route_tawar(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 200000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->addHours(24),
        ]);

        $this->post(route('tawar.store', $this->conversation), ['amount' => 200000])
            ->assertRedirect('/masuk');

        $this->post(route('tawar.respons', [$this->conversation, $offer]), ['action' => 'diterima'])
            ->assertRedirect('/masuk');
    }
}

