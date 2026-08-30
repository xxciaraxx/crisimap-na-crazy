@if($errors->any())<div class="rounded border border-red-300 bg-red-50 p-3 text-red-800">Please correct the highlighted intake information.</div>@endif
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
<label>Intake date<input required type="date" name="intake_date" value="{{ old('intake_date', isset($intake) ? $intake->intake_date?->format('Y-m-d') : now()->toDateString()) }}" class="mt-1 w-full rounded border"></label>
<label>Program<select name="program" class="mt-1 w-full rounded border">@foreach(['AICS','ESA','Others'] as $v)<option @selected(old('program', $intake->program ?? 'AICS') === $v)>{{ $v }}</option>@endforeach</select></label>
<label>Client type<select name="client_type" class="mt-1 w-full rounded border"><option value="new" @selected(old('client_type', $intake->client_type ?? 'new') === 'new')>New</option><option value="returning" @selected(old('client_type', $intake->client_type ?? '') === 'returning')>Returning</option></select></label>
<label>Client / applicant<input required name="client_name" value="{{ old('client_name', $intake->client_name ?? '') }}" class="mt-1 w-full rounded border"></label>
<label>Beneficiary<input name="beneficiary_name" value="{{ old('beneficiary_name', $intake->beneficiary_name ?? '') }}" class="mt-1 w-full rounded border"></label><label>Contact<input name="contact" value="{{ old('contact', $intake->contact ?? '') }}" class="mt-1 w-full rounded border"></label>
<label>Age<input type="number" name="age" min="0" max="120" value="{{ old('age', $intake->age ?? '') }}" class="mt-1 w-full rounded border"></label>
<label>Sex<select name="sex" class="mt-1 w-full rounded border"><option value="">Select</option><option value="Male" @selected(old('sex', $intake->sex ?? '') === 'Male')>Male</option><option value="Female" @selected(old('sex', $intake->sex ?? '') === 'Female')>Female</option><option value="Other" @selected(old('sex', $intake->sex ?? '') === 'Other')>Other</option></select></label>
<label>Civil Status<input name="civil_status" value="{{ old('civil_status', $intake->civil_status ?? '') }}" class="mt-1 w-full rounded border"></label>
<label>Occupation<input name="occupation" value="{{ old('occupation', $intake->occupation ?? '') }}" class="mt-1 w-full rounded border"></label>
<label>Barangay<select name="barangay_id" class="mt-1 w-full rounded border"><option value="">Select</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(old('barangay_id', $intake->barangay_id ?? '') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label><label>Address<input name="address" value="{{ old('address', $intake->address ?? '') }}" class="mt-1 w-full rounded border"></label>
<label>Requested amount (Php)<input type="number" step=".01" name="requested_amount" value="{{ old('requested_amount', $intake->requested_amount ?? '') }}" class="mt-1 w-full rounded border"></label><label>Monthly income (Php)<input type="number" step=".01" name="monthly_income" value="{{ old('monthly_income', $intake->monthly_income ?? '') }}" class="mt-1 w-full rounded border"></label>
</div>

<label class="block mt-4">Purpose of assistance<textarea name="purpose_of_assistance" class="mt-1 w-full rounded border">{{ old('purpose_of_assistance', $intake->purpose_of_assistance ?? '') }}</textarea></label>
<label class="block mt-4">Diagnosis / cause of death (when applicable)<textarea name="diagnosis_or_cause_of_death" class="mt-1 w-full rounded border">{{ old('diagnosis_or_cause_of_death', $intake->diagnosis_or_cause_of_death ?? '') }}</textarea></label>

<div class="mt-6 border-t pt-4 grid grid-cols-1 gap-6 md:grid-cols-3">
    <div class="rounded border p-4 bg-gray-50">
        <h4 class="font-semibold text-sm mb-2 text-gray-800">CLIENT'S SECTOR</h4>
        @php
            $clientSectors = old('client_sectors', $intake->client_sectors ?? []);
        @endphp
        <div class="space-y-1 text-sm">
            @foreach(['Solo Parent', 'WEDC', 'Senior Citizens', '4Ps Beneficiary', 'PWDs', 'No Regular Income', 'Others'] as $sector)
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="client_sectors[]" value="{{ $sector }}" @checked(in_array($sector, $clientSectors))>
                    <span>{{ $sector }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="rounded border p-4 bg-gray-50">
        <h4 class="font-semibold text-sm mb-2 text-gray-800">INCOME & FINANCIAL RESOURCES</h4>
        @php
            $incomeResources = old('income_resources', $intake->income_resources ?? []);
        @endphp
        <div class="space-y-3 text-sm">
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="income_resources[]" value="Employed" @checked(in_array('Employed', $incomeResources))>
                    <span>Employed</span>
                </label>
                <div class="ml-6 mt-1">
                    <label class="text-xs text-gray-600">Number of members working:</label>
                    <input type="number" min="0" name="employed_members_count" value="{{ old('employed_members_count', $intake->employed_members_count ?? '') }}" class="w-full rounded border px-2 py-1 text-sm">
                </div>
            </div>
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="income_resources[]" value="Seasonal" @checked(in_array('Seasonal', $incomeResources))>
                    <span>Seasonal</span>
                </label>
                <div class="ml-6 mt-1">
                    <label class="text-xs text-gray-600">Number of members working:</label>
                    <input type="number" min="0" name="seasonal_members_count" value="{{ old('seasonal_members_count', $intake->seasonal_members_count ?? '') }}" class="w-full rounded border px-2 py-1 text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-700 font-medium">Combined family income (Php/month):</label>
                <input type="number" step=".01" min="0" name="combined_family_income" value="{{ old('combined_family_income', $intake->combined_family_income ?? '') }}" class="w-full rounded border px-2 py-1 text-sm mt-1">
            </div>
        </div>
    </div>

    <div class="rounded border p-4 bg-gray-50">
        <h4 class="font-semibold text-sm mb-2 text-gray-800">BUDGET & EXPENSES</h4>
        <div class="text-sm">
            <label class="text-xs text-gray-700 font-medium">Monthly expenses of the family (Php):</label>
            <input type="number" step=".01" min="0" name="monthly_expenses" value="{{ old('monthly_expenses', $intake->monthly_expenses ?? '') }}" class="w-full rounded border px-2 py-1 text-sm mt-1">
            <p class="text-xs text-gray-500 mt-2">(Utility bills, Maintenance, Medication, Mortgage/Rent, Debt and others)</p>
        </div>
    </div>
</div>

<div class="mt-6 border-t pt-4">
    <h4 class="font-semibold text-sm mb-2 text-gray-800">SUPPORTING DOCUMENTS SUBMITTED</h4>
    @php
        $submittedDocs = old('supporting_documents', $intake->supporting_documents ?? []);
        $docList = [
            ['Valid ID', 'Certificate of Indigency', 'Medical Certificate', 'Medical Abstract', 'Hospital Billing Statement'],
            ['Prescription', 'Imaging Request', 'Laboratory Request', 'Funeral Contract', 'Death Certificate'],
            ['Enrollment Fee', 'Certificate of Enrollment', 'Fare Ticket', 'Letter of Request', 'Barangay Certification', 'Certificate of Residency', 'Others']
        ];
    @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
        @foreach($docList as $col)
            <div class="space-y-1">
                @foreach($col as $doc)
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="supporting_documents[]" value="{{ $doc }}" @checked(in_array($doc, $submittedDocs))>
                        <span>{{ $doc }}</span>
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<label class="block mt-6">Social worker assessment<textarea name="social_worker_assessment" rows="4" class="mt-1 w-full rounded border">{{ old('social_worker_assessment', $intake->social_worker_assessment ?? '') }}</textarea></label>
<div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2"><label>Interviewer name<input name="interviewer_name" value="{{ old('interviewer_name', $intake->interviewer_name ?? auth()->user()->name) }}" class="mt-1 w-full rounded border"></label><label>Position<input name="interviewer_position" value="{{ old('interviewer_position', $intake->interviewer_position ?? 'Social Officer') }}" class="mt-1 w-full rounded border"></label></div>
