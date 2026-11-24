<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_carry_hardening_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public function test_content_security_policy_forbids_inline_script_without_nonce(): void
    {
        $policy = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
    }

    public function test_inline_route_script_uses_the_policy_nonce(): void
    {
        $response = $this->get('/');
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $response->headers->get('Content-Security-Policy'), $matches);

        $this->assertStringContainsString('nonce="'.$matches[1].'"', $response->getContent());
    }

    public function test_hsts_is_sent_only_over_https(): void
    {
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_welcome_page_does_not_disclose_runtime_versions(): void
    {
        $content = $this->get('/')->getContent();

        $this->assertStringNotContainsString('laravelVersion', $content);
        $this->assertStringNotContainsString('phpVersion', $content);
    }

    public function test_password_reset_response_does_not_reveal_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'absent@example.test']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
    }

    public function test_registration_is_rate_limited(): void
    {
        foreach (range(1, 6) as $attempt) {
            $this->post('/register', []);
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_registration_rejects_weak_passwords(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'weak@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
    }

    public function test_passwords_are_stored_as_salted_hashes(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'hash@example.test',
            'password' => 'Correct-Horse-Battery-9',
            'password_confirmation' => 'Correct-Horse-Battery-9',
        ]);

        $stored = User::where('email', 'hash@example.test')->firstOrFail()->password;

        $this->assertNotSame('Correct-Horse-Battery-9', $stored);
        $this->assertSame('bcrypt', Hash::info($stored)['algoName']);
    }

    public function test_existing_lower_cost_hashes_remain_valid_after_cost_increase(): void
    {
        $legacyHash = password_hash('Legacy-Password-10', PASSWORD_BCRYPT, ['cost' => 10]);
        $hasher = new BcryptHasher(['rounds' => 12, 'verify' => true]);

        $this->assertTrue($hasher->check('Legacy-Password-10', $legacyHash));
        $this->assertTrue($hasher->needsRehash($legacyHash));
    }

    public function test_serialized_user_hides_credentials(): void
    {
        $payload = User::factory()->create()->toArray();

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
    }

    public function test_state_changing_request_without_csrf_token_is_rejected(): void
    {
        $this->app['env'] = 'local';

        $this->post('/login', ['email' => 'a@example.test', 'password' => 'x'])->assertStatus(419);
    }

    public function test_api_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_cors_allows_no_origin_by_default(): void
    {
        $this->assertSame([], array_values(config('cors.allowed_origins')));
    }

    public function test_password_reset_with_invalid_token_does_not_reveal_account_existence(): void
    {
        $user = User::factory()->create();

        $known = $this->resetErrorFor($user->email);
        $unknown = $this->resetErrorFor('absent@example.test');

        $this->assertSame($known, $unknown);
    }

    public function test_sessions_created_before_a_password_change_are_signed_out(): void
    {
        $user = User::factory()->create();
        $previousHash = $user->password;
        $user->update(['password' => Hash::make('Another-Password-123')]);

        $this->actingAs($user->fresh())
            ->withSession(['password_hash_web' => $previousHash])
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_password_change_keeps_the_current_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['password_hash_web' => $user->password])->put('/password', [
            'current_password' => 'password',
            'password' => 'Another-Password-123',
            'password_confirmation' => 'Another-Password-123',
        ])->assertSessionHasNoErrors();

        $this->get('/dashboard')->assertOk();
    }

    public function test_password_change_rotates_remember_token_and_revokes_api_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('device');
        $rememberToken = $user->remember_token;

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'Another-Password-123',
            'password_confirmation' => 'Another-Password-123',
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_password_reset_revokes_api_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('device');

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'Another-Password-123',
            'password_confirmation' => 'Another-Password-123',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_account_deletion_removes_api_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('device');

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertSessionHasNoErrors();

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_login_is_rate_limited_per_client_across_accounts(): void
    {
        foreach (range(1, 20) as $attempt) {
            $this->post('/login', ['email' => "user{$attempt}@example.test"]);
        }

        $this->post('/login', ['email' => 'another@example.test'])->assertStatus(429);
    }

    public function test_account_deletion_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $attempt) {
            $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'wrong-password']);
        }

        $this->actingAs($user)->from('/profile')->delete('/profile', ['password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_password_confirmation_rejects_non_string_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/confirm-password')
            ->post('/confirm-password', ['password' => ['password']])
            ->assertSessionHasErrors('password');
    }

    public function test_email_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();
        $email = $user->email;

        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => 'attacker@example.test',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame($email, $user->fresh()->email);
    }

    public function test_email_change_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create();
        $email = $user->email;

        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => 'attacker@example.test',
            'current_password' => 'wrong-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertSame($email, $user->fresh()->email);
    }

    public function test_name_change_does_not_require_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => 'Renamed User',
            'email' => $user->email,
            'current_password' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Renamed User', $user->fresh()->name);
    }

    public function test_expired_api_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device');
        $token->accessToken->forceFill(['created_at' => now()->subMinutes(1441)])->save();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_blank_token_lifetime_falls_back_to_the_default(): void
    {
        $config = $this->evaluateConfig('sanctum.php', ['SANCTUM_EXPIRATION' => '']);

        $this->assertSame(1440, $config['expiration']);
    }

    public function test_session_cookie_is_secure_in_production_even_when_the_env_disables_it(): void
    {
        $config = $this->evaluateConfig('session.php', ['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => 'false']);

        $this->assertTrue($config['secure']);
    }

    public function test_cors_allowlist_ignores_whitespace(): void
    {
        $config = $this->evaluateConfig('cors.php', ['CORS_ALLOWED_ORIGINS' => 'https://a.example.test, https://b.example.test']);

        $this->assertSame(['https://a.example.test', 'https://b.example.test'], array_values($config['allowed_origins']));
    }

    private function resetErrorFor(string $email): string
    {
        $response = $this->from('/reset-password/token')->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $email,
            'password' => 'Another-Password-123',
            'password_confirmation' => 'Another-Password-123',
        ]);
        $message = $response->getSession()->get('errors')->first('email');
        $this->flushSession();

        return $message;
    }

    private function evaluateConfig(string $file, array $environment): array
    {
        $previous = [];

        foreach ($environment as $key => $value) {
            $previous[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        try {
            return require config_path($file);
        } finally {
            foreach ($previous as $key => [$env, $server]) {
                if ($env === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $env;
                }

                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
            }
        }
    }
}
