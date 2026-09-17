<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_mswdo_head_can_open_data_backup_and_export_beneficiary_records(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        Beneficiary::create([
            'name' => 'Rejected Applicant',
            'age' => 40,
            'sex' => 'Female',
            'status' => 'Rejected',
            'rejection_reason' => 'Incomplete documents.',
        ]);

        $this->actingAs($administrator)
            ->get(route('data-backup.index'))
            ->assertOk()
            ->assertSee('Data Backup')
            ->assertSee(route('data-backup.export'), false)
            ->assertSee(route('beneficiaries.import.create'), false);

        $this->actingAs($administrator)
            ->get(route('data-backup.export'))
            ->assertDownload();
    }

    public function test_service_roles_cannot_access_data_backup(): void
    {
        $socialOfficer = User::factory()->create(['role' => 'social_officer']);

        $this->actingAs($socialOfficer)
            ->get(route('data-backup.index'))
            ->assertForbidden();
    }

    public function test_focal_person_can_open_and_export_data_backup(): void
    {
        $focalPerson = User::factory()->create(['role' => 'focal_person']);

        $this->actingAs($focalPerson)
            ->get(route('data-backup.index'))
            ->assertOk()
            ->assertSee('Download Excel Backup');

        $this->actingAs($focalPerson)
            ->get(route('data-backup.export'))
            ->assertDownload();
    }
}
