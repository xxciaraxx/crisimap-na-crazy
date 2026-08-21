<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_pending_and_rejected_users_cannot_authenticate(): void
    {
        $pending = User::factory()->create(['email' => 'pending@example.com', 'status' => 'pending']);
        $rejected = User::factory()->create(['email' => 'rejected@example.com', 'status' => 'rejected', 'rejection_reason' => 'Missing required documents.']);

        $this->post('/login', ['email' => $pending->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $rejected->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_with_initial_credentials_is_sent_to_the_forced_credential_page(): void
    {
        $user = User::factory()->create(['must_complete_setup' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('account.setup.create', absolute: false));

        $this->get('/dashboard')->assertRedirect(route('account.setup.create', absolute: false));
    }

    public function test_forced_credential_page_saves_the_administrators_name_and_uses_email_as_username(): void
    {
        $user = User::factory()->create(['role' => 'super_admin', 'must_complete_setup' => true]);

        $this->actingAs($user)->post(route('account.setup.store'), [
            'name' => 'Jennifer Fajardo',
            'email' => 'jennifer.fajardo@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jennifer Fajardo',
            'email' => 'jennifer.fajardo@example.com',
            'username' => 'jennifer.fajardo@example.com',
            'role' => 'super_admin',
            'must_complete_setup' => false,
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
