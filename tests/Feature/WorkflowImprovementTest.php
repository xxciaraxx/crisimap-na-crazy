<?php

namespace Tests\Feature;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowImprovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_mswdo_head_can_reject_a_pending_application_with_remarks(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $beneficiary = Beneficiary::create(['name' => 'Maria Santos', 'age' => 40, 'sex' => 'Female', 'status' => 'Pending']);

        $this->actingAs($user)->post(route('beneficiaries.decide', $beneficiary), [
            'decision' => 'rejected',
            'rejection_reason' => 'Supporting documents are incomplete.',
        ])->assertRedirect();

        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'status' => 'Rejected',
            'rejection_reason' => 'Supporting documents are incomplete.',
            'rejected_by' => $user->id,
        ]);
    }

    public function test_get_request_to_a_decision_url_returns_to_the_application(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $beneficiary = Beneficiary::create(['name' => 'Maria Santos', 'age' => 40, 'sex' => 'Female', 'status' => 'Pending']);

        $this->actingAs($user)->get(route('beneficiaries.decide.redirect', $beneficiary))
            ->assertRedirect(route('beneficiaries.show', $beneficiary));
    }

    public function test_eligible_application_and_approved_certificate_are_printable(): void
    {
        $user = User::factory()->create(['role' => 'focal_person']);
        $beneficiary = Beneficiary::create([
            'name' => 'Maria Santos', 'age' => 40, 'sex' => 'Female', 'status' => 'Approved',
            'eligibility_status' => 'Eligible', 'approved_at' => now(),
        ]);

        $this->actingAs($user)->get(route('beneficiaries.application-form', $beneficiary))->assertOk();
        $this->actingAs($user)->get(route('beneficiaries.certificate', $beneficiary))->assertOk();
    }

    public function test_case_record_is_printable(): void
    {
        $user = User::factory()->create(['role' => 'social_officer']);
        $caseRecord = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Maria Santos', 'encoded_by' => $user->id]);

        $this->actingAs($user)->get(route('case-records.print', $caseRecord))->assertOk();
    }

    public function test_history_mode_only_returns_approved_workflow_records_and_resolves_period(): void
    {
        $user = User::factory()->create(['role' => 'focal_person']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag']);
        $type = AssistanceType::create(['name' => 'Medical']);
        foreach ([['Approved', '2026-02-15'], ['Pending', '2026-02-15'], ['Released', '2025-12-15']] as [$status, $date]) {
            Beneficiary::create(['name' => $status.' Person', 'age' => 40, 'sex' => 'Female', 'status' => $status, 'barangay_id' => $barangay->id, 'assistance_type_id' => $type->id, 'date_of_assistance' => $date, 'latitude' => 16.93, 'longitude' => 120.36]);
        }

        $this->actingAs($user)->getJson(route('map.data', ['view' => 'history', 'period' => '2026-Q1']))
            ->assertOk()->assertJsonCount(1, 'beneficiaries')->assertJsonPath('beneficiaries.0.status', 'Approved');
    }

    public function test_default_map_only_returns_released_beneficiaries(): void
    {
        $user = User::factory()->create(['role' => 'focal_person']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag']);
        $type = AssistanceType::firstOrCreate(['name' => 'Medical']);
        foreach (['Pending', 'Approved', 'Released'] as $status) {
            Beneficiary::create(['name' => $status.' Person', 'age' => 40, 'sex' => 'Female', 'status' => $status, 'barangay_id' => $barangay->id, 'assistance_type_id' => $type->id, 'latitude' => 16.93, 'longitude' => 120.36]);
        }

        $this->actingAs($user)->getJson(route('map.data'))
            ->assertOk()->assertJsonCount(1, 'beneficiaries')->assertJsonPath('beneficiaries.0.status', 'Released');
    }
}
