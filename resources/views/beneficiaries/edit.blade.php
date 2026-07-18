<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Beneficiary</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('beneficiaries.update', $beneficiary) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm">Full Name</label><input name="name" value="{{ old('name', $beneficiary->name) }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Originating Case Record</label><select name="case_record_id" class="w-full border rounded px-3 py-2"><option value="">No linked case record</option>@foreach($caseRecords as $caseRecord)<option value="{{ $caseRecord->id }}" @selected(old('case_record_id', $beneficiary->case_record_id) == $caseRecord->id)>{{ $caseRecord->case_number }} — {{ $caseRecord->client_name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Age</label><input type="number" name="age" value="{{ old('age', $beneficiary->age) }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Birth Date</label><input type="date" name="birth_date" value="{{ old('birth_date', $beneficiary->birth_date?->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Sex</label><select name="sex" class="w-full border rounded px-3 py-2"><option value="Male" @selected($beneficiary->sex === 'Male')>Male</option><option value="Female" @selected($beneficiary->sex === 'Female')>Female</option><option value="Other" @selected($beneficiary->sex === 'Other')>Other</option></select></div>
                        <div><label class="block text-sm">Contact</label><input name="contact" value="{{ old('contact', $beneficiary->contact) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Barangay</label><select name="barangay_id" class="w-full border rounded px-3 py-2"><option value="">Select</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected($beneficiary->barangay_id == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Assistance Type</label><select name="assistance_type_id" class="w-full border rounded px-3 py-2"><option value="">Select</option>@foreach($assistanceTypes as $type)<option value="{{ $type->id }}" @selected($beneficiary->assistance_type_id == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Amount</label><input type="number" step="0.01" name="amount" value="{{ old('amount', $beneficiary->amount) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Date of Assistance</label><input type="date" name="date_of_assistance" value="{{ old('date_of_assistance', $beneficiary->date_of_assistance?->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Address</label><input name="address" value="{{ old('address', $beneficiary->address) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Latitude</label><input name="latitude" value="{{ old('latitude', $beneficiary->latitude) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Longitude</label><input name="longitude" value="{{ old('longitude', $beneficiary->longitude) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Status</label><select name="status" class="w-full border rounded px-3 py-2"><option value="Pending" @selected($beneficiary->status === 'Pending')>Pending</option><option value="Approved" @selected($beneficiary->status === 'Approved')>Approved</option><option value="Released" @selected($beneficiary->status === 'Released')>Released</option><option value="Completed" @selected($beneficiary->status === 'Completed')>Completed</option></select></div>
                        <div><label class="block text-sm">Attachment</label><input type="file" name="attachment" class="w-full"></div>
                        <div><label class="block text-sm">Additional Documents</label><input type="file" name="attachments[]" multiple class="w-full"></div>
                    </div>
                    <div>
                        <label class="block text-sm">Case Information</label>
                        <textarea name="case_info" rows="4" class="w-full border rounded px-3 py-2">{{ old('case_info', $beneficiary->case_info) }}</textarea>
                    </div>
                    @if($canOverrideEligibility)
                        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input type="checkbox" name="override_eligibility" value="1" class="rounded border-gray-300" @checked(old('override_eligibility'))>
                                Supervisor override for the 90-day rule
                            </label>
                            <div>
                                <label class="block text-sm">Override Justification</label>
                                <textarea name="override_justification" rows="3" class="w-full border rounded px-3 py-2">{{ old('override_justification', $beneficiary->override_justification) }}</textarea>
                            </div>
                        </div>
                    @endif
                    <div class="flex gap-3">
                        <button class="bg-green-600 text-white px-4 py-2 rounded">Update</button>
                        <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
