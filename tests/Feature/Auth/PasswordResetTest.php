<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $this->get('/verify-reset-code')->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->withSession(['password_reset.email' => $user->email, 'password_reset.verified_at' => now()->timestamp])
            ->get('/reset-password')->assertOk();

        $response = $this->withSession(['password_reset.email' => $user->email, 'password_reset.verified_at' => now()->timestamp])
            ->post('/reset-password', ['password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!']);

        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));
    }
}
