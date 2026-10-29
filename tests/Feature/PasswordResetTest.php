<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
        Notification::fake();
    }

    public function test_forgot_password_link_and_reference_illustrations_are_available(): void
    {
        $this->get('/masuk')->assertSee('href="'.route('password.request').'"', false);
        $this->get('/lupa-sandi')->assertOk()->assertSee('Kirim kode')
            ->assertSee('assets/illust/auth/password.png')->assertDontSee('nomor HP');
        $this->withSession(['password_reset_email' => 'demo@example.com'])
            ->get('/lupa-sandi/terkirim')->assertOk()->assertSee('Cek email kamu')->assertSee('Kode verifikasi');
    }

    public function test_registered_email_receives_reset_notification_and_unknown_email_has_same_response(): void
    {
        $user = User::factory()->create();
        $this->post('/lupa-sandi', ['email' => $user->email])->assertRedirect(route('password.sent'));
        Notification::assertSentTo($user, PasswordResetCode::class, function ($notification) {
            return preg_match('/^[0-9]{6}$/', $notification->code) === 1;
        });
        $this->post('/lupa-sandi', ['email' => 'unknown@example.com'])->assertRedirect(route('password.sent'));
        Notification::assertCount(1);
    }

    public function test_new_password_can_login_old_password_and_used_reset_token_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee('Atur kata sandi baru')->assertSee('password_confirmation');
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post('/atur-ulang-sandi', $payload)->assertRedirect(route('password.complete'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->post('/atur-ulang-sandi', $payload)->assertSessionHasErrors('email');
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])->assertSessionHasErrors('identifier');
        $this->assertGuest();
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'new-password-123'])->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);
    }

    public function test_mismatched_and_short_passwords_do_not_change_account_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        foreach ([['long-password', 'different-password'], ['short', 'short']] as [$password, $confirmation]) {
            $this->post('/atur-ulang-sandi', [
                'email' => $user->email, 'token' => $token,
                'password' => $password, 'password_confirmation' => $confirmation,
            ])->assertSessionHasErrors('password');
            $this->assertTrue(Hash::check('password', $user->fresh()->password));
            $this->assertArrayNotHasKey('password', session()->getOldInput());
            $this->assertArrayNotHasKey('password_confirmation', session()->getOldInput());
        }
    }

    public function test_invalid_and_expired_tokens_cannot_reset_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post('/atur-ulang-sandi', $payload + ['token' => 'wrong-token'])->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post('/atur-ulang-sandi', $payload + ['token' => $token])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_request_is_validated_and_rate_limited(): void
    {
        $this->post('/lupa-sandi', ['email' => 'invalid'])->assertSessionHasErrors('email');
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/lupa-sandi', ['email' => 'unknown@example.com'])->assertRedirect(route('password.sent'));
        }
        $this->post('/lupa-sandi', ['email' => 'unknown@example.com'])->assertStatus(429);
    }

    public function test_phone_numbers_are_rejected_by_email_recovery_and_reset_endpoints(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $token = Password::createToken($user);
        foreach (['081234567890', '6281234567890', '+6281234567890'] as $phone) {
            $this->post('/lupa-sandi', ['email' => $phone])->assertSessionHasErrors('email');
            $this->post('/atur-ulang-sandi', [
                'email' => $phone, 'token' => $token,
                'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
            ])->assertSessionHasErrors('email');
        }
        Notification::assertNothingSent();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_email_code_unlocks_reset_form_and_is_single_use(): void
    {
        $user = User::factory()->create();
        $this->post('/lupa-sandi', ['email' => $user->email])->assertRedirect(route('password.sent'));
        $code = Notification::sent($user, PasswordResetCode::class)->first()->code;
        $response = $this->post('/lupa-sandi/verifikasi', ['code' => $code]);
        $response->assertRedirect();
        $this->get($response->headers->get('Location'))->assertOk()
            ->assertSee('Atur kata sandi baru')->assertSee('Ulangi kata sandi baru')
            ->assertSee('Simpan kata sandi')->assertSee('data-password-toggle', false);
        $this->assertFalse(Cache::has('password-reset-code:'.hash('sha256', $user->email)));
        $this->withSession(['password_reset_email' => $user->email])
            ->post('/lupa-sandi/verifikasi', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_expired_code_and_code_for_another_email_cannot_unlock_reset_form(): void
    {
        $user = User::factory()->create();
        $this->post('/lupa-sandi', ['email' => $user->email]);
        $code = Notification::sent($user, PasswordResetCode::class)->first()->code;
        $this->withSession(['password_reset_email' => 'other@example.com'])
            ->post('/lupa-sandi/verifikasi', ['code' => $code])->assertSessionHasErrors('code');
        $this->travel(11)->minutes();
        $this->withSession(['password_reset_email' => $user->email])
            ->post('/lupa-sandi/verifikasi', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_resend_waits_sixty_seconds_and_invalidates_previous_code(): void
    {
        $user = User::factory()->create();
        $this->post('/lupa-sandi', ['email' => $user->email]);
        $this->post('/lupa-sandi', ['email' => $user->email])->assertRedirect(route('password.sent'));
        Notification::assertSentToTimes($user, PasswordResetCode::class, 1);
        $previousHash = Cache::get('password-reset-code:'.hash('sha256', $user->email));
        $this->travel(61)->seconds();
        $this->post('/lupa-sandi', ['email' => $user->email])->assertRedirect(route('password.sent'));
        Notification::assertSentToTimes($user, PasswordResetCode::class, 2);
        $this->assertNotSame($previousHash, Cache::get('password-reset-code:'.hash('sha256', $user->email)));
    }

    public function test_five_wrong_codes_block_even_correct_code_from_another_ip(): void
    {
        $user = User::factory()->create();
        $this->post('/lupa-sandi', ['email' => $user->email]);
        $code = Notification::sent($user, PasswordResetCode::class)->first()->code;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($attempt + 1)])
                ->post('/lupa-sandi/verifikasi', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->post('/lupa-sandi/verifikasi', ['code' => $code])
            ->assertSessionHasErrors(['code' => 'Terlalu banyak percobaan. Coba lagi setelah 10 menit.']);
    }
}
