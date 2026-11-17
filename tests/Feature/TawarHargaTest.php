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
            ->call('openOffer')
            ->assertSet('showOffer', true)
            ->set('offerAmount', '280000')
            ->call('submitOffer')
            ->assertSet('showOffer', false);

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
            ->set('offerAmount', '200000')
            ->call('submitOffer');

        $offer = Offer::where('conversation_id', $this->conversation->id)->first();
        $this->assertNotNull($offer->expires_at);
        $this->assertTrue($offer->expires_at->isFuture());
        $this->assertEqualsWithDelta(
            now()->addHours(24)->timestamp,
            $offer->expires_at->timestamp,
            5 // toleransi 5 detik
        );
    }

    public function test_penjual_tidak_dapat_mengajukan_tawaran_pertama(): void
    {
        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('offerAmount', '200000')
            ->call('submitOffer')
            ->assertHasErrors(['offerAmount']);
    }

    public function test_tawaran_lama_ditolak_saat_lawan_bicara_menawar_balik(): void
    {
        // Pembeli menawar
        Livewire::actingAs($this->pembeli)->test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('offerAmount', '250000')
            ->call('submitOffer');

        $firstOffer = $this->conversation->offers()->latest('id')->first();

        // Penjual menawar balik
        Livewire::actingAs($this->penjual)->test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('offerAmount', '300000')
            ->call('submitOffer');

        $this->assertEquals('ditolak', $firstOffer->fresh()->status);
        $this->assertDatabaseHas('offers', [
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->penjual->id,
            'amount'          => 300000,
            'status'          => Offer::MENUNGGU,
        ]);
    }

    public function test_validasi_amount_harus_di_bawah_harga_barang(): void
    {
        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('offerAmount', '400000') // di atas harga listing 350.000
            ->call('submitOffer')
            ->assertHasErrors(['offerAmount']);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_validasi_amount_minimal_1000(): void
    {
        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->set('offerAmount', '500')
            ->call('submitOffer')
            ->assertHasErrors(['offerAmount']);
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
            ->call('acceptOffer', $offer->id);

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
            ->call('rejectOffer', $offer->id);

        $this->assertEquals(Offer::DITOLAK, $offer->fresh()->status);
    }

    public function test_pihak_yang_menawar_tidak_dapat_menerima_tawarannya_sendiri(): void
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
            ->call('acceptOffer', $offer->id)
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

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation])
            ->assertForbidden();
    }

    // ─────────────────────────── 6.2 — Kedaluwarsa ───────────────────────────

    public function test_tawaran_kedaluwarsa_otomatis_dibatalkan_di_render(): void
    {
        $offer = Offer::create([
            'conversation_id' => $this->conversation->id,
            'offerer_id'      => $this->pembeli->id,
            'amount'          => 280000,
            'status'          => Offer::MENUNGGU,
            'expires_at'      => now()->subHour(), // sudah lewat
        ]);

        $this->actingAs($this->penjual);

        Livewire::test(ChatRoom::class, ['conversation' => $this->conversation]);

        $this->assertEquals('dibatalkan', $offer->fresh()->status);
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
            ->assertSee('280.000');
    }

    public function test_tawaran_diterima_ditampilkan_di_chat_room(): void
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
