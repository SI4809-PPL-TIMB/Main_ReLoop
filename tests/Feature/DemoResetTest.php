<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\MitraPengepul;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 8.2.4 · Perintah demo:reset (PPLTIMB-149).
 * Sengaja tanpa RefreshDatabase: demo:reset sendiri menjalankan migrate:fresh di database SQLite memori.
 */
class DemoResetTest extends TestCase
{
    private const TABEL = ['users', 'profiles', 'listings', 'listing_photos', 'orders', 'payments', 'mitra_pengepul', 'mitra_harga', 'mitra_area', 'mitra_jam'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function jumlah(): array
    {
        return collect(self::TABEL)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
    }

    public function test_reset_mengembalikan_data_ke_kondisi_awal(): void
    {
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        $awal = $this->jumlah();
        $this->assertSame(9, $awal['users']);
        $this->assertSame(64, $awal['listings']);
        $this->assertSame(6, $awal['mitra_pengepul']);
        $statusAwal = $this->statusBarang();

        // Simulasi demo berjalan: data baru, data diubah, data dihapus, file diunggah.
        User::factory()->create(['email' => 'tamu@demo.test']);
        $hijau = MitraPengepul::where('nama_usaha', 'Bank Sampah Hijau Lestari')->firstOrFail();
        $hijau->harga()->update(['harga_per_kg' => 99999]);
        MitraPengepul::where('nama_usaha', 'CV Daur Jaya')->firstOrFail()->delete();
        Listing::query()->limit(5)->update(['status' => Listing::TERJUAL]);
        Storage::disk('public')->put('listings/1/foto.jpg', 'x');
        Storage::disk('public')->put('avatars/1/a.jpg', 'x');
        Storage::disk('public')->put('lain/tetap.txt', 'x');

        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();

        $this->assertSame($awal, $this->jumlah());
        $this->assertDatabaseMissing('users', ['email' => 'tamu@demo.test']);
        $this->assertSame(3500, MitraPengepul::where('nama_usaha', 'Bank Sampah Hijau Lestari')->firstOrFail()
            ->harga()->where('category_id', Category::where('slug', 'plastik')->value('id'))->value('harga_per_kg'));
        $this->assertTrue(MitraPengepul::where('nama_usaha', 'CV Daur Jaya')->exists());
        $this->assertSame($statusAwal, $this->statusBarang());

        Storage::disk('public')->assertMissing(['listings/1/foto.jpg', 'avatars/1/a.jpg']);
        Storage::disk('public')->assertExists('lain/tetap.txt');
    }

    public function test_batal_jika_konfirmasi_ditolak(): void
    {
        $this->artisan('demo:reset', ['--force' => true]);
        User::factory()->create(['email' => 'tamu@demo.test']);
        Storage::disk('public')->put('listings/1/foto.jpg', 'x');

        $this->artisan('demo:reset')
            ->expectsConfirmation('Lanjutkan reset data demo?', 'no')
            ->assertFailed();

        $this->assertDatabaseHas('users', ['email' => 'tamu@demo.test']);
        Storage::disk('public')->assertExists('listings/1/foto.jpg');
    }

    public function test_jalan_jika_konfirmasi_diterima(): void
    {
        $this->artisan('migrate');
        User::factory()->create(['email' => 'tamu@demo.test']);

        $this->artisan('demo:reset')
            ->expectsConfirmation('Lanjutkan reset data demo?', 'yes')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'tamu@demo.test']);
        $this->assertTrue(User::where('email', 'admin@reloop.test')->firstOrFail()->isAdmin());
    }

    private function statusBarang(): array
    {
        return Listing::query()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->orderBy('status')->pluck('total', 'status')->all();
    }
}
