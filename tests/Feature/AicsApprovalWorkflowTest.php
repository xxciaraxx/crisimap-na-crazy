<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\CaseRecord;
use App\Models\AssistanceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AicsApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_record_auto_generates_a_number_on_create(): void
    {
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Auto Number Test']);

        $this->assertMatchesRegularExpression('/^TAG-AICS-' . now()->format('Y') . '-\d{5}$/', $case->case_number);
    }

    public function test_eligible_case_creates_application_then_head_approves_and_focal_releases(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer', 'status' => 'approved']);
        $focal = User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);
        $head = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);
        $assistanceType = AssistanceType::create(['name' => 'Burial Assistance']);
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Maria Santos', 'barangay_id' => $barangay->id, 'encoded_by' => $officer->id, 'status' => 'submitted']);

        $this->actingAs($focal)->post(route('case-records.decide', $case), ['decision' => 'eligible', 'assistance_type_id' => $assistanceType->id])->assertRedirect();
        $beneficiary = Beneficiary::firstOrFail();
        $this->assertSame($case->id, $beneficiary->case_record_id);
        $this->assertSame('Pending', $beneficiary->status);
        $this->assertSame($assistanceType->id, $beneficiary->assistance_type_id);

        $this->actingAs($head)->post(route('beneficiaries.decide', $beneficiary), ['decision' => 'approved'])->assertRedirect();
        $this->actingAs($focal)->post(route('beneficiaries.release', $beneficiary))->assertRedirect();
        $this->assertDatabaseHas('beneficiaries', ['id' => $beneficiary->id, 'status' => 'Released', 'approved_by' => $head->id, 'released_by' => $focal->id]);
    }

    public function test_case_rejection_creates_no_application(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $focal = User::factory()->create(['role' => 'focal_person']);
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Maria Santos', 'encoded_by' => $officer->id, 'status' => 'submitted']);

        $this->actingAs($focal)->post(route('case-records.decide', $case), ['decision' => 'rejected', 'rejection_reason' => 'Incomplete documents'])->assertRedirect();
        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseHas('case_records', ['id' => $case->id, 'status' => 'rejected']);
    }

    public function test_social_officer_receives_notification_when_focal_person_approves_submitted_intake_case(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer', 'status' => 'approved']);
        $focal = User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);
        $assistanceType = AssistanceType::create(['name' => 'Burial Assistance']);
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Maria Santos', 'barangay_id' => $barangay->id, 'encoded_by' => $officer->id, 'status' => 'submitted']);

        $this->actingAs($focal)->post(route('case-records.decide', $case), ['decision' => 'eligible', 'assistance_type_id' => $assistanceType->id])->assertRedirect();

        $this->assertSame(1, $officer->fresh()->notifications()->count());
        $this->assertSame('Case record approved', $officer->fresh()->notifications()->first()->data['title'] ?? null);
    }

    public function test_policy_gates_decision_and_release_stages(): void
    {
        $focal = User::factory()->create(['role' => 'focal_person']);
        $head = User::factory()->create(['role' => 'admin']);
        $beneficiary = Beneficiary::create(['name' => 'Maria Santos', 'age' => 40, 'sex' => 'Female', 'status' => 'Pending']);
        $this->actingAs($focal)->post(route('beneficiaries.decide', $beneficiary), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($head)->post(route('beneficiaries.release', $beneficiary))->assertForbidden();
        $this->actingAs($focal)->post(route('beneficiaries.release', $beneficiary))->assertForbidden();
    }
}
