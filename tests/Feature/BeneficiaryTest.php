<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_create_beneficiary_directly(): void
    {
        $user = User::factory()->create([
            'role' => 'focal_person',
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

        $response->assertStatus(405);
        $this->assertDatabaseMissing('beneficiaries', ['name' => 'Juan Dela Cruz']);
    }

    public function test_duplicate_check_flags_matching_beneficiary(): void
    {
        $user = User::factory()->create([
            'role' => 'focal_person',
        ]);

        $this->actingAs($user);

        Beneficiary::create([
            'name' => 'Maria Santos',
            'age' => 34,
            'sex' => 'Female',
            'contact' => '09170000001',
            'address' => 'Poblacion, Tagudin',
            'date_of_assistance' => Carbon::now()->subDays(10)->toDateString(),
            'status' => 'Completed',
        ]);

        $response = $this->getJson('/beneficiaries/duplicate-check?name=Maria+Santos&age=34&contact=09170000001&address=Poblacion,+Tagudin');

        $response->assertOk()
            ->assertJsonPath('duplicate_count', 1)
            ->assertJsonPath('eligible', false);
    }

    public function test_direct_creation_cannot_bypass_duplicate_checks(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($supervisor);

        Beneficiary::create([
            'name' => 'Pedro Reyes',
            'age' => 45,
            'sex' => 'Male',
            'contact' => '09171112222',
            'address' => 'Centro, Tagudin',
            'date_of_assistance' => Carbon::now()->subDays(20)->toDateString(),
            'status' => 'Released',
        ]);

        $response = $this->post('/beneficiaries', [
            'name' => 'Pedro Reyes',
            'age' => 45,
            'sex' => 'Male',
            'contact' => '09171112222',
            'address' => 'Centro, Tagudin',
            'status' => 'Pending',
            'override_eligibility' => 1,
            'override_justification' => 'Emergency medical need approved by supervisor.',
        ]);

        $response->assertStatus(405);
        $this->assertDatabaseCount('beneficiaries', 1);
    }

    public function test_pending_match_is_reported_without_claiming_a_released_match(): void
    {
        $user = User::factory()->create(['role' => 'focal_person']);
        $this->actingAs($user);

        Beneficiary::create([
            'name' => 'Ana Reyes',
            'age' => 30,
            'sex' => 'Female',
            'contact' => '09170000002',
            'status' => 'Pending',
        ]);

        $this->getJson('/beneficiaries/duplicate-check?name=Ana+Reyes&age=30&contact=09170000002')
            ->assertOk()
            ->assertJsonPath('duplicate_count', 1)
            ->assertJsonPath('potential_duplicate', null)
            ->assertJsonPath('eligible', true);
    }

    public function test_an_ineligible_pending_application_cannot_be_approved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Beneficiary::create([
            'name' => 'Luis Cruz', 'age' => 51, 'sex' => 'Male', 'contact' => '09170000003',
            'status' => 'Released', 'date_of_assistance' => now()->subDays(10),
        ]);
        $application = Beneficiary::create([
            'name' => 'Luis Cruz', 'age' => 51, 'sex' => 'Male', 'contact' => '09170000003',
            'status' => 'Pending', 'eligibility_status' => 'Eligible',
        ]);

        $this->actingAs($admin)->post(route('beneficiaries.decide', $application), ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $application->id,
            'status' => 'Pending',
            'eligibility_status' => 'Ineligible',
        ]);
    }
}
