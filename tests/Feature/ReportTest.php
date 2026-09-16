<?php

namespace Tests\Feature;

use App\Models\AssistanceType;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_only_show_released_beneficiaries(): void
    {
        $head = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $assistanceType = AssistanceType::create(['name' => 'Medical']);

        foreach (['Pending Applicant' => 'Pending', 'Approved Applicant' => 'Approved', 'Released Applicant' => 'Released', 'Completed Applicant' => 'Completed'] as $name => $status) {
            Beneficiary::create([
                'name' => $name,
                'age' => 40,
                'sex' => 'Female',
                'assistance_type_id' => $assistanceType->id,
                'status' => $status,
                'amount' => 1000,
                'date_of_assistance' => now()->toDateString(),
                'released_by' => $status === 'Released' ? $head->id : null,
                'released_at' => $status === 'Released' ? now() : null,
            ]);
        }

        $response = $this->actingAs($head)->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('Released Applicant')
            ->assertDontSee('Pending Applicant')
            ->assertDontSee('Approved Applicant')
            ->assertDontSee('Completed Applicant');
    }

    public function test_social_officer_can_see_all_released_records(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer', 'status' => 'approved']);
        $otherOfficer = User::factory()->create(['role' => 'social_officer', 'status' => 'approved']);
        $assistanceType = AssistanceType::create(['name' => 'Medical']);
        Beneficiary::create([
            'name' => 'Released By Another Officer',
            'age' => 40,
            'sex' => 'Female',
            'assistance_type_id' => $assistanceType->id,
            'status' => 'Released',
            'encoded_by' => $otherOfficer->id,
            'amount' => 1000,
            'date_of_assistance' => now()->toDateString(),
        ]);

        $this->actingAs($officer)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Released By Another Officer');
    }

    public function test_excel_report_download_uses_the_released_records_export(): void
    {
        $head = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        Beneficiary::create([
            'name' => 'Released Export Applicant',
            'age' => 40,
            'sex' => 'Female',
            'status' => 'Released',
            'amount' => 1000,
            'date_of_assistance' => now()->toDateString(),
        ]);
        Excel::fake();

        $this->actingAs($head)->get(route('reports.excel'))->assertOk();

        Excel::assertDownloaded('crisimap-report.xlsx');
    }

    public function test_report_analytics_matches_released_records_by_assistance_type(): void
    {
        $head = User::factory()->create(['role' => 'admin', 'status' => 'approved']);
        $medical = AssistanceType::create(['name' => 'Medical Assistance', 'color' => '#123456']);
        $burial = AssistanceType::create(['name' => 'Burial Assistance', 'color' => '#654321']);

        foreach ([
            [$medical->id, 1000],
            [$medical->id, 1500],
            [$burial->id, 2000],
        ] as [$assistanceTypeId, $amount]) {
            Beneficiary::create([
                'name' => 'Released Applicant '.$amount,
                'age' => 40,
                'sex' => 'Female',
                'status' => 'Released',
                'assistance_type_id' => $assistanceTypeId,
                'amount' => $amount,
                'date_of_assistance' => now()->toDateString(),
            ]);
        }

        $this->actingAs($head)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Medical Assistance')
            ->assertSee('PHP 2,500.00')
            ->assertSee('66.7%')
            ->assertSee('Burial Assistance')
            ->assertSee('PHP 2,000.00')
            ->assertSee('33.3%')
            ->assertSee('#123456')
            ->assertSee('#654321')
            ->assertSee('assistance-volume-chart')
            ->assertSee('assistance-distribution-chart');
    }
}
