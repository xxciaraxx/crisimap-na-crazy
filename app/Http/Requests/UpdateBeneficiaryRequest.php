<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'sex' => ['required', 'in:Male,Female,Other'],
            'contact' => ['nullable', 'string', 'max:50'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'assistance_type_id' => ['nullable', 'exists:assistance_types,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'case_info' => ['nullable', 'string'],
            'date_of_assistance' => ['nullable', 'date'],
            'status' => ['required', 'in:Pending,Approved,Released,Completed'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }
}
