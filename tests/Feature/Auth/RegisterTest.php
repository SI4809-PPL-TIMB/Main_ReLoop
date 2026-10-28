<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test untuk PBI 2.1 — Daftar Akun
 *
 * Jalankan dengan: php artisan test --filter=RegisterTest
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────────────── Halaman form ─────────────────────────

    /** @test */
    public function halaman_daftar_dapat_diakses(): void
    {
        $response = $this->get('/daftar');

        $response->assertStatus(200);
        $response->assertSee('Buat akun');
    }

    // ───────────────────────── Daftar berhasil ─────────────────────────

    /** @test */
    public function daftar_berhasil_dengan_email(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Azzahra Test',
            'identifier' => 'azzahra@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertRedirect('/selesai');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name'  => 'Azzahra Test',
            'email' => 'azzahra@reloop.test',
            'phone' => null,
        ]);
    }

    /** @test */
    public function daftar_berhasil_dengan_nomor_hp(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Budi Test',
            'identifier' => '081234567890',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertRedirect('/selesai');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name'  => 'Budi Test',
            'phone' => '081234567890',
            'email' => null,
        ]);
    }

    /** @test */
    public function daftar_berhasil_normalisasi_nomor_plus62(): void
    {
        $this->post('/daftar', [
            'name'       => 'Citra Test',
            'identifier' => '+6281234567890',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => '081234567890',
        ]);
    }

    /** @test */
    public function password_tersimpan_sebagai_hash_bukan_teks_asli(): void
    {
        $this->post('/daftar', [
            'name'       => 'Hash Test',
            'identifier' => 'hash@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $user = User::where('email', 'hash@reloop.test')->first();

        $this->assertNotNull($user);
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(password_verify('password123', $user->password));
    }

    // ───────────────────────── Validasi gagal ─────────────────────────

    /** @test */
    public function email_duplikat_ditolak(): void
    {
        User::factory()->create(['email' => 'duplikat@reloop.test', 'phone' => null]);

        $response = $this->post('/daftar', [
            'name'       => 'Duplikat',
            'identifier' => 'duplikat@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertCount(1, User::where('email', 'duplikat@reloop.test')->get());
    }

    /** @test */
    public function nomor_hp_duplikat_ditolak(): void
    {
        User::factory()->create(['phone' => '081111111111', 'email' => null]);

        $response = $this->post('/daftar', [
            'name'       => 'Duplikat HP',
            'identifier' => '081111111111',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertCount(1, User::where('phone', '081111111111')->get());
    }

    /** @test */
    public function password_kurang_dari_8_karakter_ditolak(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Pendek',
            'identifier' => 'pendek@reloop.test',
            'password'   => '1234567', // 7 karakter
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'pendek@reloop.test']);
    }

    /** @test */
    public function syarat_dan_ketentuan_tidak_dicentang_ditolak(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Tanpa S&K',
            'identifier' => 'tanpask@reloop.test',
            'password'   => 'password123',
            // 'terms' tidak dikirim
        ]);

        $response->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'tanpask@reloop.test']);
    }

    /** @test */
    public function nama_wajib_diisi(): void
    {
        $response = $this->post('/daftar', [
            'name'       => '',
            'identifier' => 'tanpanama@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function identifier_wajib_diisi(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Tanpa Identifier',
            'identifier' => '',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('identifier');
    }

    // ───────────────────────── Akun Siap & Lokasi ─────────────────────────

    /** @test */
    public function halaman_selesai_dapat_diakses(): void
    {
        $response = $this->get('/selesai');

        $response->assertStatus(200);
        $response->assertSee('Makasih! Akunmu sudah siap');
    }

    /** @test */
    public function halaman_pengaturan_lokasi_dapat_diakses(): void
    {
        $response = $this->get('/lokasi');

        $response->assertStatus(200);
        $response->assertSee('Kamu tinggal di mana?');
        $response->assertSee('Langkah 2 dari 2');
        $response->assertSee('Mulai pakai Reloop');
    }

    /** @test */
    public function simpan_lokasi_berhasil_dan_redirect_ke_home(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/lokasi', [
            'desa'    => 'Sukamaju',
            'patokan' => 'Dekat masjid',
        ]);

        $response->assertRedirect('/');
    }
}
