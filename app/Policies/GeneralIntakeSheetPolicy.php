<?php

namespace App\Policies;

use App\Models\GeneralIntakeSheet;
use App\Models\User;

class GeneralIntakeSheetPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['social_officer', 'focal_person', 'admin', 'super_admin'], true); }
    public function view(User $user, GeneralIntakeSheet $intake): bool { return $user->role !== 'social_officer' || $intake->encoded_by === $user->id; }
    public function create(User $user): bool { return $user->role === 'social_officer'; }
    public function update(User $user, GeneralIntakeSheet $intake): bool { return $user->role === 'social_officer' && $intake->encoded_by === $user->id && $intake->status === 'draft'; }
}
