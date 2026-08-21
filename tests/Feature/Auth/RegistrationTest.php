<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Choose a position');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'focal_person',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'focal_person', 'status' => 'pending']);
    }

    public function test_registration_automatically_assigns_social_worker_officer_when_leadership_positions_are_filled(): void
    {
        User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);

        $this->get('/register')->assertDontSee('Choose a position');

        $response = $this->post('/register', [
            'name' => 'Social Worker',
            'email' => 'worker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('login', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'worker@example.com', 'role' => 'social_officer', 'status' => 'pending']);
    }
}
