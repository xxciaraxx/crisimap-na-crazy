<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\CaseRecord;
use App\Models\DocumentRecord;
use App\Models\GeneralIntakeSheet;
use App\Models\HouseholdMember;
use App\Models\OfficialSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTemplateAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_intake_sheet_renders_sectors_and_financial_fields(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        $intake = GeneralIntakeSheet::create([
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Juan Dela Cruz',
            'barangay_id' => $barangay->id,
            'client_sectors' => ['Solo Parent', 'WEDC', 'Others', 'No Regular Income'],
            'income_resources' => ['Employed', 'Seasonal'],
            'employed_members_count' => 2,
            'seasonal_members_count' => 1,
            'combined_family_income' => 7500.00,
            'monthly_expenses' => 6000.00,
            'supporting_documents' => ['Valid ID', 'Medical Certificate', 'Barangay Certification'],
            'encoded_by' => $officer->id,
        ]);

        $caseRecord = CaseRecord::create([
            'general_intake_sheet_id' => $intake->id,
            'date_of_entry' => now()->toDateString(),
            'client_name' => 'Juan Dela Cruz',
            'barangay_id' => $barangay->id,
            'encoded_by' => $officer->id,
            'status' => 'submitted',
        ]);

        $doc = DocumentRecord::create([
            'general_intake_sheet_id' => $intake->id,
            'case_record_id' => $caseRecord->id,
            'template' => 'general_intake_sheet',
            'prepared_by' => $officer->id,
        ]);

        $response = $this->actingAs($officer)->get(route('case-records.documents.preview', [$caseRecord, $doc]));
        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString("CLIENT'S SECTOR", $content);
        $this->assertStringContainsString('WEDC', $content);
        $this->assertStringContainsString('Others', $content);
        $this->assertStringContainsString('No Regular Income', $content);
        $this->assertStringContainsString('Combined family income', $content);
        $this->assertStringContainsString('7,500.00', $content);
        $this->assertStringContainsString('SUPPORTING DOCUMENTS SUBMITTED', $content);
        $this->assertStringContainsString('Valid ID', $content);
    }

    public function test_application_form_renders_disaster_categories_household_and_signatures(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        OfficialSetting::updateOrCreate(['key' => 'social_worker_name'], ['value' => 'LAARNI L. ACOSTA, RSW']);
        OfficialSetting::updateOrCreate(['key' => 'social_worker_title'], ['value' => 'Municipal Social Welfare and Development Officer']);

        $caseRecord = CaseRecord::create([
            'date_of_entry' => now()->toDateString(),
            'client_name' => 'Maria Clara',
            'barangay_id' => $barangay->id,
            'encoded_by' => $officer->id,
            'status' => 'submitted',
        ]);

        HouseholdMember::create([
            'case_record_id' => $caseRecord->id,
            'name' => 'Pedro Clara',
            'relationship_to_client' => 'Son',
            'age' => 12,
            'sex' => 'Male',
        ]);

        $doc = DocumentRecord::create([
            'case_record_id' => $caseRecord->id,
            'template' => 'application_form',
            'payload' => [
                'resettled' => 'resettled',
                'disaster_type' => 'ND',
                'date_of_occurrence' => 'August 15, 2026',
                'program_category' => ['Cultural Minorities', 'ESA', 'AICS'],
                'family_head_name' => 'Crispin Clara',
                'estimated_damage' => 'Php 50,000',
                'casualties' => 'None',
            ],
            'prepared_by' => $officer->id,
        ]);

        $response = $this->actingAs($officer)->get(route('case-records.documents.preview', [$caseRecord, $doc]));
        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('APPLICATION FORM', $content);
        $this->assertStringContainsString('Resettled', $content);
        $this->assertStringContainsString('ND', $content);
        $this->assertStringContainsString('August 15, 2026', $content);
        $this->assertStringContainsString('Cultural Minorities', $content);
        $this->assertStringContainsString('ESA', $content);
        $this->assertStringContainsString('Crispin Clara', $content);
        $this->assertStringContainsString('Pedro Clara', $content);
        $this->assertStringContainsString('Food Assistance', $content);
        $this->assertStringContainsString('Signature or Thumb mark of client', $content);
        $this->assertStringContainsString('LAARNI L. ACOSTA, RSW', $content);
    }

    public function test_certificate_of_eligibility_renders_social_worker_and_mayor_signatories(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag'], ['code' => 'bitalag']);

        OfficialSetting::updateOrCreate(['key' => 'social_worker_name'], ['value' => 'LAARNI L. ACOSTA, RSW']);
        OfficialSetting::updateOrCreate(['key' => 'social_worker_title'], ['value' => 'Municipal Social Welfare and Development Officer']);
        OfficialSetting::updateOrCreate(['key' => 'mayor_name'], ['value' => 'HON. EVANGELINE I. VERZOSA']);
        OfficialSetting::updateOrCreate(['key' => 'mayor_title'], ['value' => 'Municipal Mayor']);

        $caseRecord = CaseRecord::create([
            'date_of_entry' => now()->toDateString(),
            'client_name' => 'Rosa Parks',
            'barangay_id' => $barangay->id,
            'encoded_by' => $officer->id,
            'status' => 'submitted',
        ]);

        $doc = DocumentRecord::create([
            'case_record_id' => $caseRecord->id,
            'template' => 'certificate_of_eligibility',
            'payload' => [
                'case_narrative' => 'Verified indigent resident requiring immediate financial augmentation.',
            ],
            'prepared_by' => $officer->id,
        ]);

        $response = $this->actingAs($officer)->get(route('case-records.documents.preview', [$caseRecord, $doc]));
        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('BA FORM 206', $content);
        $this->assertStringContainsString('Series of 1986', $content);
        $this->assertStringContainsString('CERTIFICATE OF ELIGIBILITY', $content);
        $this->assertStringContainsString('LAARNI L. ACOSTA, RSW', $content);
        $this->assertStringContainsString('HON. EVANGELINE I. VERZOSA', $content);
        $this->assertStringContainsString('APPROVED:', $content);
    }

    public function test_document_record_templates_has_exactly_four_templates(): void
    {
        $this->assertCount(4, DocumentRecord::TEMPLATES);
        $this->assertArrayHasKey('general_intake_sheet', DocumentRecord::TEMPLATES);
        $this->assertArrayHasKey('case_record', DocumentRecord::TEMPLATES);
        $this->assertArrayHasKey('application_form', DocumentRecord::TEMPLATES);
        $this->assertArrayHasKey('certificate_of_eligibility', DocumentRecord::TEMPLATES);
        $this->assertArrayNotHasKey('reimbursement_expense_receipt', DocumentRecord::TEMPLATES);
    }
}
