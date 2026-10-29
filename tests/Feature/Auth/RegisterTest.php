<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function halaman_daftar_dapat_diakses(): void
    {
        $response = $this->get('/daftar');

        $response->assertStatus(200);
        $response->assertSee('Buat akun');
    }

    // ───────────────────────── Daftar berhasil ─────────────────────────

    #[Test]
    public function daftar_berhasil_dengan_email(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Azzahra Test',
            'identifier' => 'azzahra@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        // Setelah daftar, pengguna diarahkan ke halaman verifikasi dulu
        $response->assertRedirect('/verifikasi');

        $this->assertDatabaseHas('users', [
            'name'  => 'Azzahra Test',
            'email' => 'azzahra@reloop.test',
        ]);
    }

    #[Test]
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

    #[Test]
    public function email_duplikat_ditolak(): void
    {
        User::factory()->create(['email' => 'duplikat@reloop.test']);

        $response = $this->post('/daftar', [
            'name'       => 'Duplikat',
            'identifier' => 'duplikat@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertCount(1, User::where('email', 'duplikat@reloop.test')->get());
    }

    #[Test]
    public function format_email_tidak_valid_ditolak(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Email Salah',
            'identifier' => 'bukan-email',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    // ───────────────────────── Akun Siap ─────────────────────────

    #[Test]
    public function halaman_selesai_dapat_diakses(): void
    {
        $response = $this->get('/selesai');

        $response->assertStatus(200);
        $response->assertSee('Makasih! Akunmu sudah siap');
    }

    // TODO: tambah test alur verifikasi (kode benar -> /selesai, kode salah ditolak)
    // setelah cara kerja storeVerification di RegisterController dipastikan.
}