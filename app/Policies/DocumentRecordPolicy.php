<?php

namespace App\Policies;

use App\Models\DocumentRecord;
use App\Models\User;

class DocumentRecordPolicy
{
    public function view(User $user, DocumentRecord $document): bool
    {
        return $user->role !== 'social_officer'
            ? in_array($user->role, ['focal_person', 'admin', 'super_admin'], true)
            : $document->caseRecord?->encoded_by === $user->id;
    }
    public function update(User $user, DocumentRecord $document): bool
    {
        return $document->status !== 'finalized' && $this->view($user, $document) && in_array($user->role, ['social_officer', 'focal_person', 'super_admin'], true);
    }
    public function finalize(User $user, DocumentRecord $document): bool { return $this->update($user, $document); }
}
