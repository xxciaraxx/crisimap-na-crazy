<?php

namespace Tests\Feature;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_officer_only_receives_markers_for_their_own_cases_without_sensitive_fields(): void
    {
        $officer = User::factory()->create(['role' => 'social_officer']);
        $otherOfficer = User::factory()->create(['role' => 'social_officer']);
        $barangay = Barangay::firstOrCreate(['name' => 'Bitalag']);
        $type = AssistanceType::firstOrCreate(['name' => 'Medical'], ['color' => '#E53935']);
        $ownCase = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Maria Santos', 'barangay_id' => $barangay->id, 'encoded_by' => $officer->id]);
        $otherCase = CaseRecord::create(['date_of_entry' => now(), 'client_name' => 'Private Client', 'barangay_id' => $barangay->id, 'encoded_by' => $otherOfficer->id]);

        foreach ([[$ownCase, 'Maria Santos'], [$otherCase, 'Private Client']] as [$case, $name]) {
            Beneficiary::create([
                'name' => $name, 'age' => 40, 'sex' => 'Female', 'barangay_id' => $barangay->id,
                'assistance_type_id' => $type->id, 'case_record_id' => $case->id, 'address' => 'Confidential street address',
                'case_info' => 'Sensitive medical narrative', 'latitude' => 16.9301, 'longitude' => 120.3604,
                'date_of_assistance' => now(), 'encoded_by' => $officer->id, 'status' => 'Released',
            ]);
        }

        $response = $this->actingAs($officer)->getJson(route('map.data'));

        $response->assertOk()->assertJsonCount(1, 'beneficiaries')
            ->assertJsonPath('beneficiaries.0.details.name', 'Maria Santos')
            ->assertJsonMissing(['address' => 'Confidential street address'])
            ->assertJsonMissing(['case_info' => 'Sensitive medical narrative'])
            ->assertJsonMissing(['name' => 'Private Client']);
    }
}
