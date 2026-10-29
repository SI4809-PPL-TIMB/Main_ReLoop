<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_login_page_has_password_toggle_and_no_social_login(): void
    {
        $this->get('/masuk')->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-eye-show', false)
            ->assertSee('type="email"', false)
            ->assertDontSee('Nomor HP')
            ->assertDontSee('Google')
            ->assertDontSee('Atau lanjut dengan');
    }

    public function test_only_email_can_be_used_to_login(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);
        $this->post('/keluar');
        foreach (['081234567890', '6281234567890', '+6281234567890'] as $phone) {
            $this->post('/masuk', ['identifier' => $phone, 'password' => 'password'])
                ->assertSessionHasErrors('identifier');
            $this->assertGuest();
        }
    }

    public function test_existing_account_can_see_login_and_forgot_password_actions(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->get('/masuk')->assertOk()
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSee('Lupa kata sandi?')
            ->assertSee('type="submit" data-login-submit', false);
        $this->assertGuest();
        $this->get('/lupa-sandi')->assertOk()->assertSee('Kirim kode');
    }

    public function test_failed_login_for_existing_account_keeps_both_actions_available(): void
    {
        $user = User::factory()->create();
        $this->from('/masuk')->followingRedirects()->post('/masuk', [
            'identifier' => $user->email,
            'password' => 'wrong-password',
        ])->assertOk()
            ->assertSee('Email atau kata sandi salah.')
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSee('Lupa kata sandi?')
            ->assertSee('type="submit" data-login-submit', false);
        $this->assertGuest();
    }

    public function test_logout_returns_existing_user_to_login_with_both_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $this->followingRedirects()->post('/keluar')->assertOk()
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSee('Lupa kata sandi?')
            ->assertSee('type="submit" data-login-submit', false);
        $this->assertGuest();
    }

    public function test_login_form_posts_to_backend_with_csrf_protection(): void
    {
        $this->get('/masuk')->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('data-login-form', false)
            ->assertSee('data-login-submit', false);
    }

    public function test_failed_login_shows_error_and_retains_email_without_password(): void
    {
        $this->from('/masuk')->followingRedirects()->post('/masuk', [
            'identifier' => 'unknown@example.com',
            'password' => 'wrong-secret-value',
        ])->assertOk()
            ->assertSee('Email atau kata sandi salah.')
            ->assertSee('value="unknown@example.com"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertDontSee('wrong-secret-value');
    }

    public function test_missing_fields_show_indonesian_validation_messages(): void
    {
        $this->post('/masuk')->assertSessionHasErrors([
            'identifier' => 'Email wajib diisi.',
            'password' => 'Kata sandi wajib diisi.',
        ]);
    }

    public function test_wrong_password_and_unknown_account_have_the_same_error(): void
    {
        $user = User::factory()->create();
        foreach ([$user->email, 'unknown@example.com'] as $identifier) {
            $this->post('/masuk', ['identifier' => $identifier, 'password' => 'wrong'])
                ->assertSessionHasErrors(['identifier' => 'Email atau kata sandi salah.']);
            $this->assertGuest();
            $this->assertArrayNotHasKey('password', session()->getOldInput());
        }
    }

    public function test_sixth_attempt_is_blocked_even_with_valid_credentials(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['identifier' => "unknown{$i}@example.com", 'password' => 'wrong']);
        }
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_returns_to_original_page_and_logout_protects_it_again(): void
    {
        $user = User::factory()->create();
        $this->get('/profil?from=nav')->assertRedirect('/masuk');
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profil?from=nav');
        $this->get('/profil')->assertOk()->assertSee('Keluar');
        $this->post('/keluar')->assertRedirect('/masuk');
        $this->assertGuest();
        $this->get('/profil')->assertRedirect('/masuk');
    }

    public function test_logout_does_not_accept_get_requests(): void
    {
        $this->get('/keluar')->assertStatus(405);
    }

    public function test_invalid_input_is_rate_limited_too(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', ['identifier' => 'invalid'])
                ->assertSessionHasErrors(['identifier', 'password']);
        }
        $this->post('/masuk')->assertStatus(429);
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/masuk')->assertRedirect('/profil');
        $this->post('/masuk')->assertRedirect('/profil');
    }

    public function test_json_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/profil')->assertUnauthorized();
        $this->postJson('/keluar')->assertUnauthorized();
    }

    public function test_login_rotates_session_and_logout_clears_session_data(): void
    {
        $user = User::factory()->create();
        $this->withSession(['private' => 'value', '_token' => 'old-token']);
        $previousId = session()->getId();
        $this->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profil');
        $this->assertNotSame($previousId, session()->getId());
        $this->post('/keluar')->assertSessionMissing('private');
        $this->assertNotSame('old-token', session()->token());
        $this->assertGuest();
    }
}
