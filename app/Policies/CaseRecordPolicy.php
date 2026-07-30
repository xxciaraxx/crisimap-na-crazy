<?php

namespace App\Policies;

use App\Models\CaseRecord;
use App\Models\User;

class CaseRecordPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['social_officer', 'focal_person', 'admin', 'super_admin'], true); }
    public function view(User $user, CaseRecord $record): bool { return $user->role !== 'social_officer' || $record->encoded_by === $user->id; }
    public function create(User $user): bool { return $user->role === 'social_officer'; }
    public function update(User $user, CaseRecord $record): bool { return $user->role === 'social_officer' && $record->encoded_by === $user->id && $record->status === 'submitted'; }
    public function advance(User $user, CaseRecord $record): bool { return $user->role === 'focal_person' && $record->status === 'submitted'; }
    public function approve(User $user, CaseRecord $record): bool { return $user->role === 'admin' && $record->status === 'under_review'; }
}
