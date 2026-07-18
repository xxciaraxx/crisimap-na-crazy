<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRecordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date_of_entry' => ['required', 'date'], 'client_name' => ['required', 'string', 'max:255'],
            'barangay_id' => ['nullable', 'exists:barangays,id'], 'problem_presented' => ['nullable', 'string'],
            'house_structure' => ['nullable', 'string', 'max:255'], 'water_source' => ['nullable', 'string', 'max:255'],
            'electricity' => ['nullable', 'string', 'max:255'], 'client_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'members' => ['nullable', 'array'], 'members.*.name' => ['nullable', 'string', 'max:255'],
            'members.*.relationship_to_client' => ['nullable', 'string', 'max:100'], 'members.*.birth_date' => ['nullable', 'date'],
            'members.*.age' => ['nullable', 'integer', 'min:0', 'max:120'], 'members.*.sex' => ['nullable', 'in:Male,Female,Other'],
            'members.*.civil_status' => ['nullable', 'string', 'max:100'], 'members.*.religion' => ['nullable', 'string', 'max:100'],
            'members.*.educational_attainment' => ['nullable', 'string', 'max:255'], 'members.*.occupation' => ['nullable', 'string', 'max:255'],
            'members.*.income' => ['nullable', 'numeric', 'min:0'], 'members.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
