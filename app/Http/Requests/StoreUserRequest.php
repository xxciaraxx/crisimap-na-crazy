<?php

namespace App\Http\Requests;

use App\Rules\SingleAccountRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role' => ['required', 'in:social_officer,focal_person,admin,super_admin', new SingleAccountRole],
            'status' => ['required', 'in:pending,approved,rejected'],
        ];
    }
}
