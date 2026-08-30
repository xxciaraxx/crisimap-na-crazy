<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\CaseRecord;
use App\Models\DocumentRecord;
use App\Models\OfficialSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntakeDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_officer_creates_intake_and_linked_case(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);
        $this->actingAs($officer)->post(route('intakes.store'), ['intake_date' => now()->toDateString(), 'program' => 'AICS', 'client_type' => 'new', 'client_name' => 'Ana Cruz', 'barangay_id' => $barangay->id])->assertRedirect();
        $intake = \App\Models\GeneralIntakeSheet::firstOrFail();
        $this->actingAs($officer)->post(route('case-records.store'), ['general_intake_sheet_id' => $intake->id, 'date_of_entry' => now()->toDateString(), 'client_name' => 'Ana Cruz', 'barangay_id' => $barangay->id, 'is_government_program_member' => false])->assertRedirect();
        $this->assertDatabaseHas('case_records', ['general_intake_sheet_id' => $intake->id, 'client_name' => 'Ana Cruz']);
    }

    public function test_focal_person_must_choose_a_category_for_eligibility(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $focal = User::factory()->create(['role' => 'focal_person']);
        $case = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Ana Cruz', 'encoded_by' => $officer->id, 'status' => 'submitted']);
        $this->actingAs($focal)->from(route('case-records.show', $case))->post(route('case-records.decide', $case), ['decision' => 'eligible'])->assertSessionHasErrors('assistance_type_id');
        $this->assertDatabaseMissing('beneficiaries', ['case_record_id' => $case->id]);
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
