<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_registers_as_an_assessor(): void
    {
        $this->post('/register', [
            'name' => 'Amina Assessor',
            'email' => 'amina@example.test',
            'password' => 'a secure test passphrase',
            'password_confirmation' => 'a secure test passphrase',
        ])->assertRedirect('/email/verify');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'amina@example.test', 'role' => User::ROLE_ASSESSOR]);
    }

    public function test_users_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_non_admins_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ASSESSOR]))->get('/admin/users')->assertForbidden();
    }

    public function test_an_admin_can_update_a_users_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $this->actingAs($admin)->patch("/admin/users/{$user->id}/role", ['role' => User::ROLE_REVIEWER])->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => User::ROLE_REVIEWER]);
    }
}
