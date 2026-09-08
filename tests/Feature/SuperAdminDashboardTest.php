<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Beneficiary;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_administrator_receives_the_operational_dashboard(): void
    {
        $administrator = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.super-admin');
    }

    public function test_service_roles_keep_the_existing_dashboard(): void
    {
        foreach (['admin', 'focal_person', 'social_officer'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertViewIs('dashboard');
        }
    }

    public function test_mswdo_head_dashboard_lists_pending_applications_for_review(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $pending = Beneficiary::create([
            'name' => 'Pending Applicant',
            'age' => 40,
            'sex' => 'Female',
            'status' => 'Pending',
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Applications awaiting approval')
            ->assertSee($pending->name)
            ->assertSee(route('beneficiaries.show', $pending), false);
    }

    public function test_dashboard_has_safe_empty_states(): void
    {
        $administrator = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No accounts awaiting approval.')
            ->assertSee('Vacant');
    }

    public function test_pending_account_can_be_approved_from_the_dashboard_workflow(): void
    {
        $administrator = User::factory()->create(['role' => 'super_admin']);
        $pending = User::factory()->create(['role' => 'admin', 'status' => 'pending']);

        $this->actingAs($administrator)
            ->post(route('users.approve', $pending))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $pending->id, 'status' => 'approved']);
        $this->actingAs($administrator)->get(route('dashboard'))->assertSee($pending->name);
    }

    public function test_singleton_role_cannot_be_approved_twice(): void
    {
        $administrator = User::factory()->create(['role' => 'super_admin']);
        $first = User::factory()->create(['role' => 'focal_person', 'status' => 'pending']);
        $second = User::factory()->create(['role' => 'focal_person', 'status' => 'pending']);

        $this->actingAs($administrator)->post(route('users.approve', $first));
        $this->actingAs($administrator)->post(route('users.approve', $second))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $second->id, 'status' => 'pending']);
    }

    public function test_rejection_requires_and_stores_a_reason(): void
    {
        $administrator = User::factory()->create(['role' => 'super_admin']);
        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($administrator)->post(route('users.reject', $pending))
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($administrator)->post(route('users.reject', $pending), [
            'rejection_reason' => 'Please submit the required supporting documents.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $pending->id,
            'status' => 'rejected',
            'rejection_reason' => 'Please submit the required supporting documents.',
        ]);
    }
}
