<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SingleAccountRole implements ValidationRule
{
    public function __construct(private readonly ?int $exceptUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, ['super_admin', 'admin', 'focal_person'], true)) {
            return;
        }
        $exists = User::query()->where('role', $value)->when($this->exceptUserId, fn ($query) => $query->whereKeyNot($this->exceptUserId))->exists();
        if ($exists) {
            $fail('Only one '.ucwords(str_replace('_', ' ', $value)).' account is allowed. Edit the existing account instead.');
        }
    }
}
