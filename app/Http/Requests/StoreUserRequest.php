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
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => ['required', 'in:social_officer,focal_person,admin,super_admin', new SingleAccountRole],
            'status' => ['required', 'in:pending,approved,rejected'],
        ];
    }
}
