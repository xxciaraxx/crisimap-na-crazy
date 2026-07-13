<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_beneficiary(): void
    {
        $user = User::factory()->create([
            'role' => 'social_welfare_officer',
        ]);

        $this->actingAs($user);

        $response = $this->post('/beneficiaries', [
            'name' => 'Juan Dela Cruz',
            'age' => 40,
            'sex' => 'Male',
            'contact' => '09171234567',
            'status' => 'Pending',
            'case_info' => 'Test case',
        ]);

        $response->assertRedirect('/beneficiaries');
        $this->assertDatabaseHas('beneficiaries', ['name' => 'Juan Dela Cruz']);
    }
}
