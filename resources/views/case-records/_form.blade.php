@php($members = old('members', isset($caseRecord) ? $caseRecord->householdMembers->map(fn ($member) => $member->toArray())->all() : [[]]))

@if ($errors->any())
    <div class="rounded border border-red-300 bg-red-50 p-4 text-sm text-red-800">Please correct the highlighted case-record details.</div>
@endif

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    @if(isset($intake) && $intake)<input type="hidden" name="general_intake_sheet_id" value="{{ $intake->id }}"><div class="md:col-span-2 rounded border border-sky-200 bg-sky-50 p-3 text-sm">Prefilled from General Intake Sheet <a class="font-medium text-blue-700" href="{{ route('intakes.show', $intake) }}">{{ $intake->intake_number }}</a>. Fields below form the detailed supporting assessment.</div>@endif
    <div><label class="block text-sm font-medium">Date of Entry</label><input type="date" name="date_of_entry" value="{{ old('date_of_entry', isset($caseRecord) ? $caseRecord->date_of_entry?->format('Y-m-d') : ($intake?->intake_date?->format('Y-m-d') ?? now()->toDateString())) }}" class="mt-1 w-full rounded border px-3 py-2" required></div>
    <div><label class="block text-sm font-medium">Client / Household Head</label><input name="client_name" value="{{ old('client_name', $caseRecord->client_name ?? ($intake?->client_name ?? '')) }}" class="mt-1 w-full rounded border px-3 py-2" required></div>
    <div><label class="block text-sm font-medium">Barangay</label><select name="barangay_id" class="mt-1 w-full rounded border px-3 py-2"><option value="">Select barangay</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(old('barangay_id', $caseRecord->barangay_id ?? ($intake?->barangay_id ?? '')) == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium">Signed Case Record</label><input type="file" name="client_signature" accept=".jpg,.jpeg,.png,.pdf" class="mt-1 w-full text-sm"><p class="mt-1 text-xs text-gray-500">Upload a scan/photo of the signed logbook page (JPG, PNG, or PDF; max 4 MB).</p></div>
</div>

<div><label class="block text-sm font-medium">Problem Presented</label><textarea name="problem_presented" rows="3" class="mt-1 w-full rounded border px-3 py-2" placeholder="State the client or household's concern">{{ old('problem_presented', $caseRecord->problem_presented ?? '') }}</textarea></div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div><label class="block text-sm font-medium">House Structure</label><input name="house_structure" value="{{ old('house_structure', $caseRecord->house_structure ?? '') }}" class="mt-1 w-full rounded border px-3 py-2" placeholder="e.g. Concrete / light materials"></div>
    <div><label class="block text-sm font-medium">Water Source</label><input name="water_source" value="{{ old('water_source', $caseRecord->water_source ?? '') }}" class="mt-1 w-full rounded border px-3 py-2"></div>
    <div><label class="block text-sm font-medium">Electricity</label><input name="electricity" value="{{ old('electricity', $caseRecord->electricity ?? '') }}" class="mt-1 w-full rounded border px-3 py-2" placeholder="e.g. Connected / none"></div>
</div>

@php($isProgramMember = old('is_government_program_member', isset($caseRecord) ? (int) $caseRecord->is_government_program_member : null))
@php($selectedPrograms = old('government_programs', $caseRecord->government_programs ?? []))
<fieldset class="rounded border border-sky-200 bg-sky-50 p-4">
    <legend class="px-1 text-sm font-semibold text-gray-800">Other DSWD / Government Program Membership</legend>
    <p class="text-sm text-gray-600">Is the applicant currently a member or beneficiary of another government assistance program?</p>
    <div class="mt-3 flex flex-wrap gap-5 text-sm">
        <label><input type="radio" name="is_government_program_member" value="1" @checked($isProgramMember === 1) required> Yes</label>
        <label><input type="radio" name="is_government_program_member" value="0" @checked($isProgramMember === 0) required> No</label>
    </div>
    <div id="program-options" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @if($isProgramMember !== 1) hidden @endif>
        @foreach (['4Ps', 'Social Pension for Indigent Senior Citizens', 'PWD ID or Benefits', 'Sustainable Livelihood Program (SLP)', 'Other'] as $program)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="government_programs[]" value="{{ $program }}" @checked(in_array($program, $selectedPrograms, true))> {{ $program }}</label>
        @endforeach
        <div class="md:col-span-2"><label class="block text-sm font-medium">Other program (please specify)</label><input name="other_government_program" value="{{ old('other_government_program', $caseRecord->other_government_program ?? '') }}" class="mt-1 w-full rounded border px-3 py-2" placeholder="Specify the other DSWD or government program"></div>
    </div>
</fieldset>

<div>
    <div class="mb-2 flex items-center justify-between"><div><h3 class="font-semibold text-gray-800">Household Members</h3><p class="text-sm text-gray-500">Encode every person listed in the physical case-record logbook.</p></div><button type="button" id="add-member" class="rounded bg-sky-600 px-3 py-2 text-sm text-white">Add member</button></div>
    <div class="overflow-x-auto rounded border">
        <table class="min-w-[1100px] w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-2 text-left">Name</th><th class="p-2 text-left">Relation</th><th class="p-2 text-left">Birthday / Age</th><th class="p-2 text-left">Sex</th><th class="p-2 text-left">Civil Status</th><th class="p-2 text-left">Religion</th><th class="p-2 text-left">Education</th><th class="p-2 text-left">Occupation</th><th class="p-2 text-left">Income</th><th class="p-2"></th></tr></thead><tbody id="members-body"></tbody></table>
    </div>
</div>

<template id="member-template"><tr class="border-t member-row"><td class="p-2"><input name="members[__INDEX__][name]" class="w-40 rounded border px-2 py-1" required></td><td class="p-2"><input name="members[__INDEX__][relationship_to_client]" class="w-28 rounded border px-2 py-1"></td><td class="p-2"><input type="date" name="members[__INDEX__][birth_date]" data-birth-date class="rounded border px-2 py-1"><input type="number" name="members[__INDEX__][age]" data-age min="0" max="120" placeholder="Age" readonly class="mt-1 w-20 rounded border bg-slate-50 px-2 py-1"></td><td class="p-2"><select name="members[__INDEX__][sex]" class="rounded border px-2 py-1"><option value="">-</option><option>Male</option><option>Female</option><option>Other</option></select></td><td class="p-2"><input name="members[__INDEX__][civil_status]" class="w-28 rounded border px-2 py-1"></td><td class="p-2"><input name="members[__INDEX__][religion]" class="w-28 rounded border px-2 py-1"></td><td class="p-2"><input name="members[__INDEX__][educational_attainment]" class="w-32 rounded border px-2 py-1"></td><td class="p-2"><input name="members[__INDEX__][occupation]" class="w-32 rounded border px-2 py-1"></td><td class="p-2"><input type="number" step="0.01" min="0" name="members[__INDEX__][income]" class="w-28 rounded border px-2 py-1"></td><td class="p-2"><button type="button" class="remove-member text-red-600">Remove</button></td></tr></template>

<script>
    const body = document.getElementById('members-body'), template = document.getElementById('member-template').innerHTML;
    let memberIndex = 0;
    const fillAge = (row) => {
        const birthday = row.querySelector('[data-birth-date]')?.value;
        const ageField = row.querySelector('[data-age]');
        if (!ageField) return;
        if (!birthday) { ageField.value = ''; return; }
        const date = new Date(`${birthday}T00:00:00`), today = new Date();
        let age = today.getFullYear() - date.getFullYear();
        const beforeBirthday = today.getMonth() < date.getMonth() || (today.getMonth() === date.getMonth() && today.getDate() < date.getDate());
        ageField.value = !Number.isNaN(date.valueOf()) && age >= 0 ? age - Number(beforeBirthday) : '';
    };
    const addMember = (member = {}) => {
        const holder = document.createElement('tbody'); holder.innerHTML = template.replaceAll('__INDEX__', memberIndex++); const row = holder.firstElementChild;
        Object.entries(member).forEach(([key, value]) => { const field = row.querySelector(`[name$="[${key}]"]`); if (field && value !== null) field.value = String(value).slice(0, 10); });
        body.appendChild(row); fillAge(row);
    };
    @json($members).forEach(addMember);
    document.getElementById('add-member').addEventListener('click', () => addMember());
    body.addEventListener('click', event => { if (event.target.classList.contains('remove-member')) event.target.closest('tr').remove(); });
    body.addEventListener('change', event => { if (event.target.matches('[data-birth-date]')) fillAge(event.target.closest('tr')); });
    const programOptions = document.getElementById('program-options');
    document.querySelectorAll('[name="is_government_program_member"]').forEach(input => input.addEventListener('change', () => {
        programOptions.hidden = document.querySelector('[name="is_government_program_member"]:checked')?.value !== '1';
    }));
</script>
