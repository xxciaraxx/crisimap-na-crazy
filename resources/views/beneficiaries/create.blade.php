<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Assistance Application</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div id="duplicate-alert" class="hidden mb-4 rounded border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900"></div>
                <div id="eligibility-alert" class="hidden mb-4 rounded border border-sky-300 bg-sky-50 p-4 text-sm text-sky-900"></div>

                @if($caseRecord)
                    <div class="mb-4 rounded border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        This application is linked to case <strong>{{ $caseRecord->case_number }}</strong>. Client details have been prefilled; review them before submitting.
                    </div>
                @endif

                <form id="beneficiary-form" method="POST" action="{{ route('beneficiaries.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @if($caseRecord)<input type="hidden" name="case_record_id" value="{{ $caseRecord->id }}">@endif
                    <input type="hidden" name="status" value="Pending">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm">Full Name</label><input name="name" value="{{ old('name', $caseRecord?->client_name) }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Age</label><input type="number" name="age" value="{{ old('age', $caseRecord?->householdMembers->firstWhere('name', $caseRecord?->client_name)?->age) }}" class="w-full border rounded px-3 py-2" required></div>
                        <div><label class="block text-sm">Birth Date</label><input type="date" name="birth_date" value="{{ old('birth_date', $caseRecord?->householdMembers->firstWhere('name', $caseRecord?->client_name)?->birth_date?->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Sex</label><select name="sex" class="w-full border rounded px-3 py-2"><option value="Male" @selected(old('sex', $caseRecord?->householdMembers->firstWhere('name', $caseRecord?->client_name)?->sex) === 'Male')>Male</option><option value="Female" @selected(old('sex', $caseRecord?->householdMembers->firstWhere('name', $caseRecord?->client_name)?->sex) === 'Female')>Female</option><option value="Other" @selected(old('sex', $caseRecord?->householdMembers->firstWhere('name', $caseRecord?->client_name)?->sex) === 'Other')>Other</option></select></div>
                        <div><label class="block text-sm">Contact</label><input name="contact" value="{{ old('contact') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Barangay</label><select name="barangay_id" class="w-full border rounded px-3 py-2"><option value="">Select</option>@foreach($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(old('barangay_id', $caseRecord?->barangay_id) == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Assistance Type</label><select name="assistance_type_id" class="w-full border rounded px-3 py-2"><option value="">Select</option>@foreach($assistanceTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                        <div><label class="block text-sm">Amount</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-sm">Address</label><input name="address" value="{{ old('address') }}" class="w-full border rounded px-3 py-2"></div>
                        @include('beneficiaries._location-picker', ['beneficiary' => null])
                        <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm"><p class="text-gray-500">Application status</p><p class="font-semibold">Pending review</p></div>
                        <div><label class="block text-sm">Attachment</label><input type="file" name="attachment" class="w-full"></div>
                        <div><label class="block text-sm">Additional Documents</label><input type="file" name="attachments[]" multiple class="w-full"></div>
                    </div>
                    <div>
                        <label class="block text-sm">Case Information</label>
                        <textarea name="case_info" rows="4" class="w-full border rounded px-3 py-2">{{ old('case_info', $caseRecord?->problem_presented) }}</textarea>
                    </div>
                    @if($canOverrideEligibility)
                        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input type="checkbox" name="override_eligibility" value="1" class="rounded border-gray-300">
                                Supervisor override for the 90-day rule
                            </label>
                            <div>
                                <label class="block text-sm">Override Justification</label>
                                <textarea name="override_justification" rows="3" class="w-full border rounded px-3 py-2" placeholder="Explain why the override is necessary">{{ old('override_justification') }}</textarea>
                            </div>
                        </div>
                    @endif
                    <div class="flex gap-3">
                        <button class="bg-green-600 text-white px-4 py-2 rounded">Save</button>
                        <a href="{{ route('beneficiaries.index') }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const duplicateAlert = document.getElementById('duplicate-alert');
        const eligibilityAlert = document.getElementById('eligibility-alert');
        const form = document.getElementById('beneficiary-form');
        const duplicateEndpoint = @json(route('beneficiaries.duplicate-check'));
        const inputsToWatch = ['name', 'age', 'birth_date', 'contact', 'address', 'barangay_id'];
        let timer = null;

        const renderAlert = (element, message, visible) => {
            element.textContent = message;
            element.classList.toggle('hidden', ! visible);
        };

        const fetchDuplicateStatus = async () => {
            const params = new URLSearchParams();
            inputsToWatch.forEach((field) => {
                const input = form.elements[field];
                if (input && input.value) {
                    params.append(field, input.value);
                }
            });

            if (! params.toString()) {
                duplicateAlert.classList.add('hidden');
                eligibilityAlert.classList.add('hidden');
                return;
            }

            const response = await fetch(`${duplicateEndpoint}?${params.toString()}`);
            const payload = await response.json();

            if (payload.duplicate_count > 0) {
                const latest = payload.potential_duplicate;
                const latestText = latest
                    ? ` Latest released match: ${latest.name} (${latest.reference_code}).`
                    : ' A matching application is already on file, but no released assistance was found.';
                renderAlert(duplicateAlert, `Potential duplicate detected.${latestText}${payload.next_eligible_at ? ` Next eligible date: ${payload.next_eligible_at}.` : ''}`, true);
            } else {
                duplicateAlert.classList.add('hidden');
            }

            if (payload.eligible) {
                renderAlert(eligibilityAlert, 'Eligibility check passed. The 90-day rule does not block this application.', true);
            } else {
                renderAlert(eligibilityAlert, `This application is within the 90-day rule. ${payload.next_eligible_at ? `Next eligible date: ${payload.next_eligible_at}.` : ''}`, true);
            }
        };

        inputsToWatch.forEach((field) => {
            const input = form.elements[field];
            if (! input) {
                return;
            }

            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(fetchDuplicateStatus, 350);
            });
            input.addEventListener('change', fetchDuplicateStatus);
        });
    </script>
</x-app-layout>
