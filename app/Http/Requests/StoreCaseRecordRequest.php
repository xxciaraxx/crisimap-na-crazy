<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseRecordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'general_intake_sheet_id' => ['nullable', 'exists:general_intake_sheets,id', 'unique:case_records,general_intake_sheet_id'],
            'date_of_entry' => ['required', 'date'], 'client_name' => ['required', 'string', 'max:255'],
            'barangay_id' => ['nullable', 'exists:barangays,id'], 'problem_presented' => ['nullable', 'string'],
            'house_structure' => ['nullable', 'string', 'max:255'], 'water_source' => ['nullable', 'string', 'max:255'],
            'electricity' => ['nullable', 'string', 'max:255'], 'client_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'is_government_program_member' => ['required', 'boolean'],
            'government_programs' => ['nullable', 'required_if:is_government_program_member,1', 'array', 'min:1'],
            'government_programs.*' => ['string', 'in:4Ps,Social Pension for Indigent Senior Citizens,PWD ID or Benefits,Sustainable Livelihood Program (SLP),Other'],
            'other_government_program' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => in_array('Other', $this->input('government_programs', []), true))],
            'members' => ['nullable', 'array'], 'members.*.name' => ['nullable', 'string', 'max:255'],
            'members.*.relationship_to_client' => ['nullable', 'string', 'max:100'], 'members.*.birth_date' => ['nullable', 'date'],
            'members.*.age' => ['nullable', 'integer', 'min:0', 'max:120'], 'members.*.sex' => ['nullable', 'in:Male,Female,Other'],
            'members.*.civil_status' => ['nullable', 'in:Single,Married,Widowed'], 'members.*.religion' => ['nullable', 'string', 'max:100'],
            'members.*.educational_attainment' => ['nullable', 'string', 'max:255'], 'members.*.occupation' => ['nullable', 'string', 'max:255'],
            'members.*.income' => ['nullable', 'numeric', 'min:0'], 'members.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
