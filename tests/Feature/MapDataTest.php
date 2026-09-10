<?php

namespace Tests\Feature;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\GeneralIntakeSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_officer_receives_the_same_full_map_view_as_focal_person(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $otherOfficer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag']);
        $type = AssistanceType::firstOrCreate(['name' => 'Medical'], ['color' => '#E53935']);
        foreach ([[$officer, 'Maria Santos'], [$otherOfficer, 'Private Client']] as [$encoder, $name]) {
            Beneficiary::create([
                'name' => $name, 'age' => 40, 'sex' => 'Female', 'barangay_id' => $barangay->id,
                'assistance_type_id' => $type->id, 'address' => 'Confidential street address',
                'case_info' => 'Sensitive medical narrative', 'latitude' => 16.9301, 'longitude' => 120.3604,
                'date_of_assistance' => now(), 'encoded_by' => $encoder->id, 'status' => 'Released',
            ]);
        }

        $response = $this->actingAs($officer)->getJson(route('map.data'));

        $response->assertOk()->assertJsonCount(2, 'beneficiaries')
            ->assertJsonPath('beneficiaries.0.details.beneficiary', 'Maria Santos')
            ->assertJsonPath('beneficiaries.0.details.name', 'Maria Santos')
            ->assertJsonPath('beneficiaries.1.details.name', 'Private Client');
    }

    public function test_map_uses_registered_beneficiary_name_from_general_intake_sheet(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $type = AssistanceType::firstOrCreate(['name' => 'Education'], ['color' => '#1E88E5']);
        $intake = GeneralIntakeSheet::create([
            'intake_date' => now()->toDateString(),
            'program' => 'AICS',
            'client_type' => 'new',
            'client_name' => 'Parent Applicant',
            'beneficiary_name' => 'Registered Beneficiary',
            'status' => 'approved',
            'encoded_by' => $officer->id,
        ]);
        Beneficiary::create([
            'name' => 'Legacy Client Name',
            'general_intake_sheet_id' => $intake->id,
            'age' => 12,
            'sex' => 'Other',
            'assistance_type_id' => $type->id,
            'latitude' => 16.9301,
            'longitude' => 120.3604,
            'date_of_assistance' => now(),
            'status' => 'Released',
            'encoded_by' => $officer->id,
        ]);

        $this->actingAs($officer)
            ->getJson(route('map.data'))
            ->assertOk()
            ->assertJsonPath('beneficiaries.0.details.beneficiary', 'Registered Beneficiary')
            ->assertJsonPath('beneficiaries.0.details.name', 'Registered Beneficiary');
    }
}
