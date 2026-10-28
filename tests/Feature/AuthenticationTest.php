<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        RateLimiter::clear('login:'.hash('sha256', '127.0.0.1'));
    }

    public function test_login_page_has_password_toggle_and_no_social_login(): void
    {
        $this->get('/masuk')->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-eye-show', false)
            ->assertSee('type="email"', false)
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
        $this->post('/masuk', ['identifier' => $user->phone, 'password' => 'password'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest();
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
            ->assertSessionHasErrors('identifier');
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
}
