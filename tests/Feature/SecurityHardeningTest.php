<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_a_long_passphrase(): void
    {
        $this->post('/register', [
            'name' => 'Amina Assessor',
            'email' => 'amina@example.test',
            'password' => str_repeat('a', 14),
            'password_confirmation' => str_repeat('a', 14),
        ])->assertSessionHasErrors('password');

        $this->post('/register', [
            'name' => 'Amina Assessor',
            'email' => 'amina@example.test',
            'password' => 'a secure test passphrase',
            'password_confirmation' => 'a secure test passphrase',
        ])->assertRedirect('/email/verify');
    }

    public function test_failed_logins_are_throttled_without_revealing_account_existence(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'unknown@example.test',
                'password' => 'incorrect password',
            ])->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        }

        $this->post('/login', [
            'email' => 'unknown@example.test',
            'password' => 'incorrect password',
        ])->assertSessionHasErrors(['email' => 'Too many login attempts. Please try again later.']);
    }

    public function test_successful_login_rehashes_legacy_bcrypt_passwords_to_argon2id(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::driver('bcrypt')->make('a secure test passphrase'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'a secure test passphrase',
        ])->assertRedirect('/dashboard');

        $this->assertSame('argon2id', password_get_info($user->refresh()->password)['algoName']);
    }

    public function test_public_responses_include_browser_security_headers(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("object-src 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_unverified_users_cannot_access_the_assessment_workspace(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/email/verify');
    }
}
