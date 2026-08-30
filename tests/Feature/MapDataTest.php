<?php

namespace Tests\Feature;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_officer_only_receives_markers_for_their_own_applications_without_sensitive_fields(): void
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

        $response->assertOk()->assertJsonCount(1, 'beneficiaries')
            ->assertJsonPath('beneficiaries.0.details.name', 'Protected beneficiary')
            ->assertJsonMissing(['address' => 'Confidential street address'])
            ->assertJsonMissing(['case_info' => 'Sensitive medical narrative'])
            ->assertJsonMissing(['name' => 'Private Client']);
    }
}
