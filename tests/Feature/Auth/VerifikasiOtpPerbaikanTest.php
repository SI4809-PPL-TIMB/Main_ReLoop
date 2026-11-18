<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature test untuk PPLTIMB-137:
 * 2.1 Perbaikan - Verifikasi OTP:
 * - Tolak login sebelum terverifikasi
 * - Throttle percobaan kode OTP
 * - Re-daftar tidak terkunci saat OTP kedaluwarsa
 * - Kirim ulang OTP membatalkan kode lama
 */
class VerifikasiOtpPerbaikanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear('otp-verify:*');
    }

    #[Test]
    public function akun_belum_terverifikasi_ditolak_login_dan_diarahkan_ke_verifikasi(): void
    {
        $user = User::factory()->unverified()->create([
            'email'    => 'belum.verif@reloop.test',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/masuk', [
            'identifier' => 'belum.verif@reloop.test',
            'password'   => 'password123',
        ]);

        // Harus diarahkan ke verifikasi dan user tetap berstatus guest
        $response->assertRedirect('/verifikasi');
        $response->assertSessionHasErrors('otp');
        $this->assertGuest();

        // Pastikan session berisi data untuk verifikasi dan OTP baru telah dikirim
        $this->assertEquals($user->id, session('pending_user_id'));
        $this->assertNotNull(session('otp_code'));
        $this->assertTrue(session('otp_expires_at') > now()->timestamp);
    }

    #[Test]
    public function akun_terverifikasi_dapat_login_secara_normal(): void
    {
        $user = User::factory()->create([
            'email'             => 'sudah.verif@reloop.test',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/masuk', [
            'identifier' => 'sudah.verif@reloop.test',
            'password'   => 'password123',
        ]);

        $response->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function percobaan_otp_dibatasi_throttle_setelah_lima_kali_salah(): void
    {
        $user = User::factory()->unverified()->create();

        $this->withSession([
            'pending_user_id' => $user->id,
            'otp_code'        => '12345',
            'otp_expires_at'  => now()->addMinutes(5)->timestamp,
        ]);

        // 5 kali salah
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post('/verifikasi', [
                'otp' => '99999',
            ]);
            $response->assertSessionHasErrors('otp');
            $this->assertGuest();
        }

        // Percobaan ke-6, meskipun kode yang dimasukkan benar, tetap diblokir oleh throttle
        $responseKeenam = $this->post('/verifikasi', [
            'otp' => '12345',
        ]);

        $responseKeenam->assertSessionHasErrors('otp');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('otp'));
        $this->assertGuest();

        // Setelah jeda throttle dibersihkan/selesai, kode benar bisa digunakan
        RateLimiter::clear('otp-verify:' . $user->id . '|127.0.0.1');

        $responseSukses = $this->post('/verifikasi', [
            'otp' => '12345',
        ]);

        $responseSukses->assertRedirect('/lokasi');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function pengguna_dengan_otp_kedaluwarsa_bisa_kirim_ulang_kode(): void
    {
        $user = User::factory()->unverified()->create();

        // Simulasi OTP sudah lewat masa berlaku (kedaluwarsa)
        $this->withSession([
            'pending_user_id' => $user->id,
            'otp_code'        => '11111',
            'otp_expires_at'  => now()->subMinute()->timestamp,
        ]);

        // Coba masukkan kode kedaluwarsa
        $response = $this->post('/verifikasi', [
            'otp' => '11111',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertStringContainsString('kedaluwarsa', session('errors')->first('otp'));
        $this->assertGuest();

        // Pengguna tidak terkunci: pending_user_id tetap ada sehingga bisa kirim ulang
        $this->assertEquals($user->id, session('pending_user_id'));

        // Kirim ulang kode
        $resendResponse = $this->post('/verifikasi/resend');
        $resendResponse->assertSessionHas('success');

        $kodeBaru = session('otp_code');
        $this->assertNotNull($kodeBaru);
        $this->assertNotEquals('11111', $kodeBaru);
        $this->assertTrue(session('otp_expires_at') > now()->timestamp);

        // Masukkan kode baru
        $verifyResponse = $this->post('/verifikasi', [
            'otp' => $kodeBaru,
        ]);

        $verifyResponse->assertRedirect('/lokasi');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function pengguna_dengan_otp_kedaluwarsa_bisa_daftar_ulang_dengan_email_sama(): void
    {
        // Akun lama yang belum terverifikasi
        $user = User::factory()->unverified()->create([
            'name'     => 'Nama Lama',
            'email'    => 'user.ulang@reloop.test',
            'password' => Hash::make('password_lama'),
        ]);

        // Daftar ulang dengan email yang sama dan data baru
        $response = $this->post('/daftar', [
            'name'       => 'Nama Baru',
            'identifier' => 'user.ulang@reloop.test',
            'password'   => 'password_baru123',
            'terms'      => '1',
        ]);

        // Berhasil diarahkan ke verifikasi tanpa error duplicate email
        $response->assertRedirect('/verifikasi');
        $response->assertSessionHas('success');

        // Data akun diperbarui
        $user->refresh();
        $this->assertEquals('Nama Baru', $user->name);
        $this->assertTrue(Hash::check('password_baru123', $user->password));

        // OTP baru bisa digunakan untuk verifikasi
        $otp = session('otp_code');
        $this->assertNotNull($otp);

        $verifyResponse = $this->post('/verifikasi', [
            'otp' => $otp,
        ]);

        $verifyResponse->assertRedirect('/lokasi');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function email_yang_sudah_terverifikasi_tetap_tidak_bisa_didaftarkan_ulang(): void
    {
        User::factory()->create([
            'email'             => 'sudah.aktif@reloop.test',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/daftar', [
            'name'       => 'Pendaftar Duplikat',
            'identifier' => 'sudah.aktif@reloop.test',
            'password'   => 'password123',
            'terms'      => '1',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertStringContainsString('Email ini sudah terdaftar', session('errors')->first('identifier'));
    }

    #[Test]
    public function kirim_ulang_otp_membatalkan_kode_lama(): void
    {
        $user = User::factory()->unverified()->create();

        $kodeLama = '12345';
        $this->withSession([
            'pending_user_id' => $user->id,
            'otp_code'        => $kodeLama,
            'otp_expires_at'  => now()->addMinutes(5)->timestamp,
        ]);

        // Minta kirim ulang
        $this->post('/verifikasi/resend');

        $kodeBaru = session('otp_code');
        $this->assertNotEquals($kodeLama, $kodeBaru);

        // Percobaan memakai kode lama harus ditolak
        $responseKodeLama = $this->post('/verifikasi', [
            'otp' => $kodeLama,
        ]);
        $responseKodeLama->assertSessionHasErrors('otp');
        $this->assertGuest();

        // Percobaan memakai kode baru harus berhasil
        $responseKodeBaru = $this->post('/verifikasi', [
            'otp' => $kodeBaru,
        ]);
        $responseKodeBaru->assertRedirect('/lokasi');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function alur_daftar_normal_tetap_berhasil(): void
    {
        $response = $this->post('/daftar', [
            'name'       => 'Pengguna Normal',
            'identifier' => 'normal@reloop.test',
            'password'   => 'passwordNormal123',
            'terms'      => '1',
        ]);

        $response->assertRedirect('/verifikasi');

        $user = User::where('email', 'normal@reloop.test')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        $otp = session('otp_code');
        $this->assertNotNull($otp);

        $verifyResponse = $this->post('/verifikasi', [
            'otp' => $otp,
        ]);

        $verifyResponse->assertRedirect('/lokasi');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
