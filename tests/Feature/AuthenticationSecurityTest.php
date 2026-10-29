<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    public function test_login_rejects_missing_and_invalid_csrf_tokens(): void
    {
        // Laravel normally bypasses CSRF checks in tests. Enable the real check.
        $this->app['env'] = 'local';
        $user = User::factory()->create();
        $credentials = ['identifier' => $user->email, 'password' => 'password'];

        $this->withSession(['_token' => 'valid-token'])
            ->post('/masuk', $credentials)->assertStatus(419);
        $this->post('/masuk', $credentials + ['_token' => 'invalid-token'])
            ->assertStatus(419);
        $this->assertGuest();
    }

    public function test_login_and_logout_succeed_with_valid_csrf_tokens(): void
    {
        $this->app['env'] = 'local';
        $user = User::factory()->create();

        $this->withSession(['_token' => 'valid-token'])->post('/masuk', [
            '_token' => 'valid-token',
            'identifier' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);

        $this->post('/keluar', ['_token' => session()->token()])
            ->assertRedirect('/masuk');
        $this->assertGuest();
        $this->get('/profil')->assertRedirect('/masuk');
    }

    public function test_logout_rejects_missing_and_invalid_csrf_tokens_without_ending_session(): void
    {
        $this->app['env'] = 'local';
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['_token' => 'valid-token']);

        $this->post('/keluar')->assertStatus(419);
        $this->post('/keluar', ['_token' => 'invalid-token'])->assertStatus(419);
        $this->assertAuthenticatedAs($user);
        $this->get('/profil')->assertOk();
    }

    public function test_rate_limit_is_scoped_to_ip_and_returns_a_json_retry_message(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/masuk', [
                'identifier' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/masuk', [
            'identifier' => $user->email,
            'password' => 'password',
        ])->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonStructure(['message'])
            ->assertJsonFragment(['message' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 60 detik.']);
        $this->assertGuest();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->post('/masuk', ['identifier' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profil');
        $this->assertAuthenticatedAs($user);
    }

    public function test_another_account_can_login_after_logout_without_previous_session_data(): void
    {
        $first = User::factory()->create(['name' => 'Pengguna Pertama']);
        $second = User::factory()->create(['name' => 'Pengguna Kedua']);

        $this->post('/masuk', ['identifier' => $first->email, 'password' => 'password'])
            ->assertRedirect('/profil');
        $this->withSession(['private_note' => 'first-account-data'])
            ->post('/keluar')->assertRedirect('/masuk')->assertSessionMissing('private_note');
        $this->post('/masuk', ['identifier' => $second->email, 'password' => 'password'])
            ->assertRedirect('/profil');

        $this->assertAuthenticatedAs($second);
        $this->get('/profil')->assertOk()->assertSee('Pengguna Kedua')->assertDontSee('Pengguna Pertama');
        $this->assertNull(session('private_note'));
    }
}
