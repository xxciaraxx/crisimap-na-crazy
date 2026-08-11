<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;

class BeneficiaryPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['focal_person', 'admin', 'super_admin'], true); }
    public function view(User $user, Beneficiary $beneficiary): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasRole('focal_person', 'admin', 'super_admin'); }
    public function update(User $user, Beneficiary $beneficiary): bool { return $user->hasRole('focal_person', 'admin', 'super_admin'); }
    public function delete(User $user, Beneficiary $beneficiary): bool { return $user->hasRole('focal_person', 'admin', 'super_admin'); }
    public function reject(User $user, Beneficiary $beneficiary): bool { return $this->update($user, $beneficiary) && $beneficiary->status === 'Pending'; }
}
