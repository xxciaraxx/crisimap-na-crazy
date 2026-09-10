<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\CaseRecord;
use App\Models\DocumentRecord;
use App\Models\GeneralIntakeSheet;
use App\Models\OfficialSetting;
use App\Models\AssistanceType;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntakeDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_welfare_officer_can_print_owned_general_intake_sheet(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $intake = GeneralIntakeSheet::create([
            'intake_date' => '2026-09-08',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Maria Santos',
            'encoded_by' => $officer->id,
            'status' => 'draft',
        ]);

        $this->actingAs($officer)
            ->get(route('intakes.print', $intake))
            ->assertOk()
            ->assertViewIs('intakes.print')
            ->assertSee('GENERAL INTAKE SHEET')
            ->assertSee('FAMILY COMPOSITION');
    }

    public function test_focal_person_can_print_any_general_intake_sheet(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $focalPerson = User::factory()->create(['role' => 'focal_person']);
        $intake = GeneralIntakeSheet::create([
            'intake_date' => '2026-09-08',
            'program' => 'AICS',
            'client_type' => 'returning',
            'client_name' => 'Juan Dela Cruz',
            'encoded_by' => $officer->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($focalPerson)
            ->get(route('intakes.print', $intake))
            ->assertOk()
            ->assertViewIs('intakes.print')
            ->assertSee('Juan Dela Cruz');
    }

    public function test_social_officer_creates_intake_without_a_case_record(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);
        $this->actingAs($officer)->post(route('intakes.store'), ['intake_date' => now()->toDateString(), 'program' => 'AICS', 'client_type' => 'new', 'client_name' => 'Ana Cruz', 'barangay_id' => $barangay->id])->assertRedirect();
        $intake = \App\Models\GeneralIntakeSheet::firstOrFail();
        $this->assertDatabaseMissing('case_records', ['general_intake_sheet_id' => $intake->id]);
    }

    public function test_focal_person_can_view_and_create_intake_sheet(): void
    {
        $focal = User::factory()->create(['role' => 'focal_person']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        $this->actingAs($focal)->get(route('intakes.index'))->assertOk();
        $this->actingAs($focal)->get(route('intakes.create'))->assertOk();
        $this->actingAs($focal)->post(route('intakes.store'), [
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Maria Santos',
            'barangay_id' => $barangay->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('general_intake_sheets', [
            'client_name' => 'Maria Santos',
            'encoded_by' => $focal->id,
            'status' => 'draft',
        ]);
        $this->assertMatchesRegularExpression('/^TAG-AICS-'.now()->format('Y').'-\d{5}$/', GeneralIntakeSheet::where('client_name', 'Maria Santos')->value('intake_number'));
    }

    public function test_social_officer_can_submit_a_draft_intake_for_focal_review(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        $this->actingAs($officer)->post(route('intakes.store'), [
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Rosa Dela Cruz',
            'barangay_id' => $barangay->id,
        ])->assertRedirect();
        $intake = \App\Models\GeneralIntakeSheet::where('client_name', 'Rosa Dela Cruz')->firstOrFail();

        $this->actingAs($officer)->post(route('intakes.submit', $intake))->assertRedirect(route('intakes.show', $intake));
        $this->assertDatabaseHas('general_intake_sheets', ['id' => $intake->id, 'status' => 'submitted']);
    }

    public function test_submitted_intake_appears_in_focal_applications_with_full_form_review(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $focal = User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);

        $this->actingAs($officer)->post(route('intakes.store'), [
            'action' => 'submit',
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Applications Review Applicant',
        ])->assertRedirect();

        $intake = GeneralIntakeSheet::where('client_name', 'Applications Review Applicant')->firstOrFail();

        $this->actingAs($focal)->get(route('applications.index'))
            ->assertOk()
            ->assertSee($intake->intake_number)
            ->assertSee('Review full form');

        $this->actingAs($focal)->get(route('applications.intakes.review', $intake))
            ->assertOk()
            ->assertSee('Applications Review Applicant')
            ->assertSee('Approve Intake')
            ->assertSee('Reject Intake');
    }

    public function test_focal_person_must_choose_a_category_for_eligibility(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $focal = User::factory()->create(['role' => 'focal_person']);
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Ana Cruz', 'encoded_by' => $officer->id, 'status' => 'submitted']);
        $this->actingAs($focal)->from(route('case-records.show', $case))->post(route('case-records.decide', $case), ['decision' => 'eligible'])->assertSessionHasErrors('assistance_type_id');
        $this->assertDatabaseMissing('beneficiaries', ['case_record_id' => $case->id]);
    }

    public function test_focal_person_can_edit_a_submitted_intake_sheet(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer', 'status' => 'approved']);
        $focal = User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        $this->actingAs($officer)->post(route('intakes.store'), [
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Edit Allowed Applicant',
            'barangay_id' => $barangay->id,
        ])->assertRedirect();

        $intake = GeneralIntakeSheet::where('client_name', 'Edit Allowed Applicant')->firstOrFail();
        $this->actingAs($officer)->post(route('intakes.submit', $intake))->assertRedirect(route('intakes.show', $intake));

        $this->actingAs($focal)
            ->get(route('intakes.edit', $intake))
            ->assertOk()
            ->assertSee('Update the official A4 intake form.');

        $this->actingAs($focal)
            ->from(route('intakes.edit', $intake))
            ->put(route('intakes.update', $intake), [
                'intake_date' => now()->toDateString(),
                'program' => 'AICS',
                'client_type' => 'returning',
                'client_name' => 'Edited Focal Applicant',
                'barangay_id' => $barangay->id,
            ])
            ->assertRedirect(route('intakes.show', $intake));

        $this->assertDatabaseHas('general_intake_sheets', ['id' => $intake->id, 'client_name' => 'Edited Focal Applicant']);

        $this->actingAs($focal)
            ->post(route('intakes.submit', $intake))
            ->assertRedirect(route('intakes.show', $intake));

        $intake->refresh();
        $this->assertSame('eligibility_review', $intake->workflow_stage);
        $this->actingAs($focal)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee($intake->intake_number)
            ->assertSee('Review full form');
    }

    public function test_focal_person_can_approve_or_reject_a_submitted_intake_sheet(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer', 'status' => 'approved']);
        $focal = User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        $this->actingAs($officer)->post(route('intakes.store'), [
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Rosa Dela Cruz',
            'barangay_id' => $barangay->id,
        ])->assertRedirect();

        $intake = GeneralIntakeSheet::where('client_name', 'Rosa Dela Cruz')->firstOrFail();
        $this->actingAs($officer)->post(route('intakes.submit', $intake))->assertRedirect(route('intakes.show', $intake));

        $this->actingAs($focal)->get(route('intakes.show', $intake))
            ->assertOk()
            ->assertSee('Approve Intake')
            ->assertSee('Reject Intake');

        $this->actingAs($focal)->post(route('intakes.decide', $intake), ['decision' => 'approved'])
            ->assertRedirect(route('intakes.show', $intake));
        $this->assertDatabaseHas('general_intake_sheets', ['id' => $intake->id, 'status' => 'approved']);

        $this->actingAs($officer)->post(route('intakes.store'), [
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Luz Reyes',
            'barangay_id' => $barangay->id,
        ])->assertRedirect();
        $rejectedIntake = GeneralIntakeSheet::where('client_name', 'Luz Reyes')->firstOrFail();
        $this->actingAs($officer)->post(route('intakes.submit', $rejectedIntake))->assertRedirect(route('intakes.show', $rejectedIntake));

        $this->actingAs($focal)->post(route('intakes.decide', $rejectedIntake), ['decision' => 'rejected', 'rejection_reason' => 'Incomplete documents'])
            ->assertRedirect(route('intakes.show', $rejectedIntake));
        $this->assertDatabaseHas('general_intake_sheets', ['id' => $rejectedIntake->id, 'status' => 'rejected', 'rejection_reason' => 'Incomplete documents']);
    }

    public function test_focal_person_submits_directly_to_mswdo_head_and_head_approval_updates_the_label(): void
    {
        $focal = User::factory()->create(['role' => 'focal_person', 'status' => 'approved']);
        $head = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        AssistanceType::create(['name' => 'Medical']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        $this->actingAs($focal)->post(route('intakes.store'), [
            'action' => 'submit',
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Direct Intake',
            'purpose_of_assistance' => 'Medical',
            'barangay_id' => $barangay->id,
        ])->assertRedirect();

        $intake = GeneralIntakeSheet::where('client_name', 'Direct Intake')->firstOrFail();
        $this->assertSame('Waiting for the MSWDO Head\'s approval', $intake->statusLabel());

        $this->actingAs($head)->post(route('intakes.head-decide', $intake), ['decision' => 'approved'])
            ->assertRedirect(route('intakes.show', $intake));

        $intake->refresh();
        $this->assertSame('approved', $intake->workflow_stage);
        $this->assertSame('Approved, waiting for the funds to be released', $intake->statusLabel());
        $this->assertDatabaseMissing('case_records', ['general_intake_sheet_id' => $intake->id]);
        $this->assertDatabaseHas('beneficiaries', ['general_intake_sheet_id' => $intake->id, 'status' => 'Approved', 'approved_by' => $head->id]);

        $this->actingAs($focal)->get(route('intakes.show', $intake))
            ->assertOk()
            ->assertSee('Release Assistance');

        $beneficiary = $intake->beneficiaries()->firstOrFail();
        $this->actingAs($focal)->post(route('beneficiaries.release', $beneficiary))
            ->assertRedirect();

        $this->assertDatabaseHas('beneficiaries', ['id' => $beneficiary->id, 'status' => 'Released', 'released_by' => $focal->id]);
        $this->assertDatabaseHas('general_intake_sheets', ['id' => $intake->id, 'status' => 'released', 'workflow_stage' => 'released']);
        $this->assertDatabaseHas('beneficiaries', [
            'id' => $beneficiary->id,
            'latitude' => 16.9355,
            'longitude' => 120.4442,
        ]);
    }

    public function test_intake_sequence_remains_continuous_when_the_year_changes(): void
    {
        $first = GeneralIntakeSheet::create([
            'intake_date' => '2025-12-31',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'First Year Applicant',
            'status' => 'draft',
        ]);
        $second = GeneralIntakeSheet::create([
            'intake_date' => '2026-01-01',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Second Year Applicant',
            'status' => 'draft',
        ]);

        $this->assertSame('TAG-AICS-2025-00001', $first->intake_number);
        $this->assertSame('TAG-AICS-2026-00002', $second->intake_number);
    }

    public function test_an_applicant_cannot_receive_a_second_intake_code(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $existing = GeneralIntakeSheet::create([
            'intake_date' => '2026-01-01',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Estrelia Sanchez',
            'status' => 'draft',
        ]);

        $this->actingAs($officer)->from(route('intakes.create'))->post(route('intakes.store'), [
            'intake_date' => '2026-09-08',
            'program' => 'AICS',
            'client_type' => 'returning',
            'client_name' => '  estrelia sanchez ',
        ])->assertRedirect(route('intakes.create'))
            ->assertSessionHasErrors(['client_name' => "This applicant already has intake code {$existing->intake_number}. Open the existing intake instead of creating another one."]);

        $this->assertDatabaseCount('general_intake_sheets', 1);
    }

    public function test_intake_code_is_preserved_when_status_changes_and_cannot_be_reassigned(): void
    {
        $intake = GeneralIntakeSheet::create([
            'intake_date' => '2026-01-01',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Status Stable Applicant',
            'status' => 'draft',
        ]);
        $originalNumber = $intake->intake_number;

        $intake->update(['status' => 'released', 'intake_number' => 'TAG-AICS-2026-99999']);

        $this->assertSame($originalNumber, $intake->fresh()->intake_number);
    }

    public function test_an_intake_cannot_be_updated_to_another_applicants_name(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $existing = GeneralIntakeSheet::create([
            'intake_date' => '2026-01-01',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Existing Applicant',
            'status' => 'released',
        ]);
        $intake = GeneralIntakeSheet::create([
            'intake_date' => '2026-01-02',
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Another Applicant',
            'encoded_by' => $officer->id,
            'status' => 'draft',
        ]);

        $this->actingAs($officer)
            ->from(route('intakes.edit', $intake))
            ->put(route('intakes.update', $intake), [
                'intake_date' => '2026-01-02',
                'program' => 'AICS',
                'client_type' => 'returning',
                'client_name' => '  existing   applicant ',
            ])
            ->assertRedirect(route('intakes.edit', $intake))
            ->assertSessionHasErrors([
                'client_name' => "This applicant already has intake code {$existing->intake_number}. Open the existing intake instead of creating another one.",
            ]);

        $this->assertSame('Another Applicant', $intake->fresh()->client_name);
    }

    public function test_intake_applicant_history_identifies_returning_and_new_applicants(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $type = AssistanceType::create(['name' => 'Medical']);
        Beneficiary::create([
            'name' => 'Ana Cruz', 'age' => 40, 'sex' => 'Female', 'status' => 'Released',
            'assistance_type_id' => $type->id, 'date_of_assistance' => '2024-01-15',
        ]);
        Beneficiary::create([
            'name' => 'Ana Cruz', 'age' => 40, 'sex' => 'Female', 'status' => 'Released',
            'assistance_type_id' => $type->id, 'date_of_assistance' => '2025-07-15',
        ]);

        $this->actingAs($officer)->getJson(route('intakes.applicant-history', ['name' => 'Ana Cruz']))
            ->assertOk()->assertJsonPath('found', true)->assertJsonPath('application_count', 2)
            ->assertJsonPath('applications.1.elapsed_since_previous', '1 year 6 months later')
            ->assertJsonPath('applications.0.assistance_type', 'Medical');

        $this->actingAs($officer)->getJson(route('intakes.applicant-history', ['name' => 'New Person']))
            ->assertOk()->assertJsonPath('found', false)->assertJsonPath('message', 'New applicant: no previous records found.');
    }

    public function test_document_draft_can_be_edited_and_finalized_snapshot_is_stable(): void
    {
        Storage::fake('sensitive');
        $officer = User::factory()->create(['role' => 'social_officer']);
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Ana Cruz', 'encoded_by' => $officer->id]);
        $this->actingAs($officer)->get(route('case-records.documents.index', $case))->assertOk();
        $document = DocumentRecord::where('case_record_id', $case->id)->firstOrFail();
        $this->actingAs($officer)->put(route('case-records.documents.update', [$case, $document]), ['fields' => ['signatory_name' => 'Original Signatory', 'case_narrative' => 'Assessment'], 'notes' => 'Reviewed'])->assertRedirect();
        $this->actingAs($officer)->post(route('case-records.documents.finalize', [$case, $document]))->assertRedirect();
        $document->refresh();
        $this->assertSame('finalized', $document->status);
        Storage::disk('sensitive')->assertExists($document->snapshot_path);
        OfficialSetting::create(['key' => 'mswdo_head_name', 'value' => 'Changed Person']);
        $this->assertSame('Original Signatory', $document->fresh()->payload['signatory_name']);
    }
}
