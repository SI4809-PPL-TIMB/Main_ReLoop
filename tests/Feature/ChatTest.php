<?php

namespace Tests\Feature;

use App\Livewire\ChatRoom;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 6.1.4 — Testing fitur Chat per barang antara pembeli dan penjual (PPLTIMB-62).
 */
class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $penjual;
    private User $pembeli;
    private User $penggunaLain;
    private Listing $barang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);

        $this->penjual = User::factory()->create(['name' => 'Budi Penjual']);
        $this->pembeli = User::factory()->create(['name' => 'Siti Pembeli']);
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
    }

    public function test_guest_diarahkan_ke_login_saat_mengakses_pesan(): void
    {
        $conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $this->get('/pesan')->assertRedirect('/masuk');
        $this->get('/barang/' . $this->barang->id . '/chat')->assertRedirect('/masuk');
        $this->get('/pesan/' . $conversation->id)->assertRedirect('/masuk');
        $this->post('/pesan/' . $conversation->id, ['body' => 'Tes'])->assertRedirect('/masuk');
    }

    public function test_pengguna_dapat_melihat_daftar_percakapan(): void
    {
        Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $response = $this->actingAs($this->pembeli)->get('/pesan');

        $response->assertOk();
        $response->assertSee('Meja Kayu Jati');
        $response->assertSee('Budi Penjual');
    }

    public function test_pembeli_dapat_memulai_chat_dari_detail_barang(): void
    {
        $response = $this->actingAs($this->pembeli)->get('/barang/' . $this->barang->id . '/chat');

        $conversation = Conversation::where('listing_id', $this->barang->id)
            ->where('buyer_id', $this->pembeli->id)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertEquals($this->penjual->id, $conversation->seller_id);
        $response->assertRedirect(route('pesan.show', $conversation));
    }

    public function test_tidak_bisa_chat_ke_barang_milik_sendiri(): void
    {
        // Penjual mencoba membuka chat ke barangnya sendiri
        $response = $this->actingAs($this->penjual)->get('/barang/' . $this->barang->id . '/chat');

        $response->assertRedirect(route('barang.detail', $this->barang));
        $response->assertSessionHas('error', 'Tidak bisa mengirim pesan ke barang milik sendiri.');

        $this->assertDatabaseMissing('conversations', [
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->penjual->id,
        ]);
    }

    public function test_percakapan_tidak_diduplikasi_untuk_barang_dan_pembeli_yang_sama(): void
    {
        // Panggilan pertama
        $this->actingAs($this->pembeli)->get('/barang/' . $this->barang->id . '/chat');

        // Panggilan kedua
        $this->actingAs($this->pembeli)->get('/barang/' . $this->barang->id . '/chat');

        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_pengguna_lain_tidak_dapat_mengakses_ruang_chat(): void
    {
        $conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $response = $this->actingAs($this->penggunaLain)->get('/pesan/' . $conversation->id);
        $response->assertForbidden();
    }

    public function test_pesan_terkirim_dan_tersimpan_di_database_melalui_post(): void
    {
        $conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $response = $this->actingAs($this->pembeli)->post('/pesan/' . $conversation->id, [
            'body' => 'Halo kak, apakah barangnya masih ada?',
        ]);

        $response->assertRedirect(route('pesan.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id'       => $this->pembeli->id,
            'body'            => 'Halo kak, apakah barangnya masih ada?',
        ]);
    }

    public function test_komponen_livewire_chat_room_dapat_mengirim_dan_menampilkan_pesan(): void
    {
        $conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $conversation])
            ->set('body', 'Permisi kak, bisa nego tipis?')
            ->call('sendMessage')
            ->assertSet('body', '')
            ->assertDispatched('message-sent')
            ->assertSee('Permisi kak, bisa nego tipis?');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id'       => $this->pembeli->id,
            'body'            => 'Permisi kak, bisa nego tipis?',
        ]);
    }

    public function test_pesan_masuk_otomatis_ditandai_sebagai_dibaca_saat_dilihat(): void
    {
        $conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        // Pembeli mengirim pesan
        $pesan = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $this->pembeli->id,
            'body'            => 'Halo penjual!',
            'read_at'         => null,
        ]);

        $this->assertNull($pesan->fresh()->read_at);

        // Penjual membuka ruang chat
        $this->actingAs($this->penjual);
        Livewire::test(ChatRoom::class, ['conversation' => $conversation]);

        // Pesan harus sudah bertanda read_at terisi
        $this->assertNotNull($pesan->fresh()->read_at);
    }

    public function test_validasi_pesan_tidak_boleh_kosong(): void
    {
        $conversation = Conversation::create([
            'listing_id' => $this->barang->id,
            'buyer_id'   => $this->pembeli->id,
            'seller_id'  => $this->penjual->id,
        ]);

        $this->actingAs($this->pembeli);

        Livewire::test(ChatRoom::class, ['conversation' => $conversation])
            ->set('body', '')
            ->call('sendMessage')
            ->assertHasErrors(['body']);

        $this->assertDatabaseCount('messages', 0);
    }
}

