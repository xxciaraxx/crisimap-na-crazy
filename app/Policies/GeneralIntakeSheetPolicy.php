<?php

namespace App\Policies;

use App\Models\GeneralIntakeSheet;
use App\Models\User;

class GeneralIntakeSheetPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['social_officer', 'focal_person', 'admin', 'super_admin'], true); }
    public function view(User $user, GeneralIntakeSheet $intake): bool { return in_array($user->role, User::ROLES, true); }
    public function create(User $user): bool { return in_array($user->role, ['social_officer', 'focal_person'], true); }
    public function update(User $user, GeneralIntakeSheet $intake): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $ownsCurrentDraft = $intake->encoded_by === $user->id
            && in_array($intake->status, ['draft', 'rejected'], true);

        if ($ownsCurrentDraft) {
            return true;
        }

        return $user->role === 'focal_person'
            && in_array($intake->status, ['submitted', 'under_review'], true);
    }
    public function submit(User $user, GeneralIntakeSheet $intake): bool
    {
        if ($intake->status === 'draft') {
            return in_array($user->role, ['social_officer', 'focal_person'], true)
                && $intake->encoded_by === $user->id;
        }

        return $user->role === 'focal_person'
            && in_array($intake->status, ['submitted', 'under_review'], true)
            && $intake->workflow_stage === 'eligibility_review';
    }
    public function decide(User $user, GeneralIntakeSheet $intake): bool { return $user->role === 'focal_person' && in_array($intake->status, ['submitted', 'under_review'], true); }
    public function headDecide(User $user, GeneralIntakeSheet $intake): bool { return in_array($user->role, ['admin', 'super_admin'], true) && $intake->workflow_stage === 'head_approval'; }
}
