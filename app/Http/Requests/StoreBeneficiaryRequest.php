<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'case_record_id' => ['nullable', 'exists:case_records,id'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['required', 'in:Male,Female,Other'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'contact' => ['nullable', 'string', 'max:50'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'assistance_type_id' => ['nullable', 'exists:assistance_types,id'],
            'address' => ['nullable', 'string', 'max:255'],
            // Keep manually pinned locations in Tagudin and its immediate vicinity.
            'latitude' => ['nullable', 'numeric', 'between:16.75,17.15'],
            'longitude' => ['nullable', 'numeric', 'between:120.20,120.65'],
            'case_info' => ['nullable', 'string'],
            'date_of_assistance' => ['nullable', 'date'],
            'status' => ['required', 'in:Pending,Approved,Released,Completed'],
            'override_eligibility' => ['nullable', 'boolean'],
            'override_justification' => ['nullable', 'required_if:override_eligibility,1', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }
}
