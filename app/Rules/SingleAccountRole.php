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
        if (! User::roleSlotAvailable($value, $this->exceptUserId)) {
            $fail('An approved '.User::roleLabel($value).' account already exists. Contact the current officeholder or the System Administrator.');
        }
    }
}
