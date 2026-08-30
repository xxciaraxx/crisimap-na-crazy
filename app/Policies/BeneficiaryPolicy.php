<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;

class BeneficiaryPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['focal_person', 'admin', 'super_admin'], true); }
    public function view(User $user, Beneficiary $beneficiary): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['focal_person', 'admin', 'super_admin'], true); }
    public function update(User $user, Beneficiary $beneficiary): bool { return in_array($user->role, ['focal_person', 'admin', 'super_admin'], true) && $beneficiary->status === 'Pending'; }
    public function delete(User $user, Beneficiary $beneficiary): bool { return in_array($user->role, ['admin', 'super_admin'], true); }
    public function decide(User $user, Beneficiary $beneficiary): bool { return in_array($user->role, ['admin', 'super_admin'], true) && $beneficiary->status === 'Pending'; }
    public function release(User $user, Beneficiary $beneficiary): bool { return in_array($user->role, ['focal_person', 'super_admin'], true) && $beneficiary->status === 'Approved'; }
}
