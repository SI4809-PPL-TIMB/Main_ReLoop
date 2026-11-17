<?php

namespace Tests\Feature;

use App\Livewire\ChatRoom;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlurTawarAktivitasTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private Listing $listing;
    private Conversation $conv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CategorySeeder::class);

        $this->seller = User::factory()->create();
        $this->seller->profile()->create();
        $this->buyer = User::factory()->create();
        $this->buyer->profile()->create(['patokan' => 'Jl. Merdeka 10']);

        $this->listing = Listing::create([
            'user_id' => $this->seller->id, 'category_id' => Category::where('type', 'bekas')->first()->id,
            'title' => 'Meja Kayu', 'slug' => 'meja-kayu', 'description' => 'Mulus', 'price' => 500000,
            'condition' => 'baik', 'status' => Listing::AKTIF, 'is_negotiable' => true,
        ]);
        $this->conv = Conversation::create(['listing_id' => $this->listing->id, 'buyer_id' => $this->buyer->id, 'seller_id' => $this->seller->id]);
    }

    public function test_tawar_balik_terima_lalu_checkout_memakai_harga_tawaran(): void
    {
        Livewire::actingAs($this->buyer)->test(ChatRoom::class, ['conversation' => $this->conv])
            ->set('offerAmount', '400000')->call('submitOffer')->assertHasNoErrors();

        Livewire::actingAs($this->seller)->test(ChatRoom::class, ['conversation' => $this->conv])
            ->set('offerAmount', '450000')->call('submitOffer')->assertHasNoErrors();

        $balik = $this->conv->offers()->latest('id')->first();
        $this->assertSame('ditolak', $this->conv->offers()->oldest('id')->first()->status);

        Livewire::actingAs($this->buyer)->test(ChatRoom::class, ['conversation' => $this->conv])
            ->call('acceptOffer', $balik->id);

        $this->actingAs($this->buyer)->get(route('order.checkout', $this->listing))
            ->assertOk()->assertSee('Harga tawaran disepakati')->assertSee('Rp 450.000')->assertSee('Kirim pakai delivery');

        $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method' => 'kirim', 'delivery_address' => 'Jl. Merdeka 10', 'payment_method' => 'snap', 'offer_id' => $balik->id,
        ])->assertSuccessful();

        $order = Order::first();
        $this->assertSame(450000, (int) $order->agreed_price);
        $this->assertSame('kirim', $order->delivery_method);
        $this->assertSame(Listing::DIPESAN, $this->listing->fresh()->status);
    }

    public function test_tawaran_tidak_valid_ditolak(): void
    {
        Livewire::actingAs($this->buyer)->test(ChatRoom::class, ['conversation' => $this->conv])
            ->set('offerAmount', '600000')->call('submitOffer')->assertHasErrors('offerAmount');
        Livewire::actingAs($this->seller)->test(ChatRoom::class, ['conversation' => $this->conv])
            ->set('offerAmount', '400000')->call('submitOffer')->assertHasErrors('offerAmount'); // penjual tak bisa memulai
        $this->assertSame(0, $this->conv->offers()->count());
    }

    public function test_pihak_yang_menawar_tidak_bisa_menerima_tawarannya_sendiri(): void
    {
        $t = Livewire::actingAs($this->buyer)->test(ChatRoom::class, ['conversation' => $this->conv])
            ->set('offerAmount', '400000')->call('submitOffer');
        $id = $this->conv->offers()->first()->id;
        $t->call('acceptOffer', $id)->assertForbidden();
    }

    public function test_aktivitas_menampilkan_order_untuk_pembeli_dan_penjual(): void
    {
        $this->actingAs($this->buyer)->postJson(route('order.store', $this->listing), [
            'delivery_method' => 'cod', 'delivery_address' => 'x', 'payment_method' => 'snap',
        ])->assertSuccessful();

        $this->actingAs($this->buyer)->get(route('aktivitas'))->assertOk()->assertSee('Meja Kayu')->assertSee('Menunggu pembayaran');
        $this->actingAs($this->seller)->get(route('aktivitas', ['f' => 'jual']))->assertOk()->assertSee('Meja Kayu');
        $this->actingAs($this->seller)->get(route('aktivitas', ['f' => 'belanja']))->assertOk()->assertDontSee('Meja Kayu');
        $this->actingAs($this->buyer)->get(route('aktivitas', ['f' => 'setor']))->assertOk()->assertSee('Setor belum tersedia');
    }

    public function test_tombol_tawar_di_detail_membuka_sheet_tawar(): void
    {
        $this->actingAs($this->buyer)->get(route('pesan.mulai', ['listing' => $this->listing, 'tawar' => 1]))
            ->assertRedirect(route('pesan.show', [$this->conv, 'tawar' => 1]));
    }
}
