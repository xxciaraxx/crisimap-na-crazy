<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page_from_home(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_authenticated_users_are_redirected_to_the_dashboard_from_home(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
    }
}
