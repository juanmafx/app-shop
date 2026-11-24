<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
}
