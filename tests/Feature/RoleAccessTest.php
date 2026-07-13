<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_cannot_access_user_management(): void
    {
        $user = User::factory()->create([
            'role' => 'social_welfare_officer',
        ]);

        $this->actingAs($user);

        $response = $this->get('/users');

        $response->assertStatus(403);
    }
}
