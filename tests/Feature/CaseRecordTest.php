<?php

namespace Tests\Feature;

use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_case_record_with_household_members(): void
    {
        $user = User::factory()->create(['role' => 'social_welfare_officer']);

        $response = $this->actingAs($user)->post('/case-records', [
            'date_of_entry' => '2026-07-16',
            'client_name' => 'Maria Santos',
            'problem_presented' => 'Medical assistance request',
            'house_structure' => 'Light materials',
            'water_source' => 'Community water system',
            'electricity' => 'Connected',
            'members' => [[
                'name' => 'Maria Santos',
                'relationship_to_client' => 'Client',
                'age' => 42,
                'sex' => 'Female',
                'occupation' => 'Vendor',
                'income' => 4500,
            ]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('case_records', ['client_name' => 'Maria Santos', 'encoded_by' => $user->id]);
        $record = CaseRecord::firstOrFail();
        $this->assertDatabaseHas('household_members', ['case_record_id' => $record->id, 'name' => 'Maria Santos']);
    }
}
