<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Review {{ App\Models\DocumentRecord::TEMPLATES[$document->template] ?? $document->template }}</h2>
    </x-slot>

    <div class="py-6 mx-auto max-w-4xl">
        <form method="POST" action="{{ route('case-records.documents.update', [$caseRecord, $document]) }}" class="space-y-6 rounded bg-white p-6 shadow">
            @csrf
            @method('PUT')
            <p class="text-sm text-gray-600">Prefilled from the workflow records. Edit document-specific values before finalizing.</p>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Document Date</label>
                    <input type="date" name="fields[date]" value="{{ $fields['date'] ?? now()->toDateString() }}" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                </div>

                @if($document->template === 'application_form')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Family Head Name</label>
                        <input type="text" name="fields[family_head_name]" value="{{ $fields['family_head_name'] ?? '' }}" placeholder="Default: {{ $caseRecord->client_name }}" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Signatory name</label>
                        <input name="fields[signatory_name]" value="{{ $fields['signatory_name'] ?? ($settings['mswdo_head_name'] ?? $settings['social_worker_name'] ?? '') }}" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                    </div>
                @endif
            </div>

            @if($document->template === 'application_form')
                <div class="border-t pt-4 space-y-4">
                    <h3 class="font-semibold text-gray-800 text-sm">Disaster & Category Details</h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Resettlement Status</label>
                            <select name="fields[resettled]" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                                <option value="not_resettled" @selected(($fields['resettled'] ?? '') === 'not_resettled' || ($fields['resettled'] ?? '') === 'Not Resettled' || ($fields['resettled'] ?? false) === false)>Not Resettled</option>
                                <option value="resettled" @selected(($fields['resettled'] ?? '') === 'resettled' || ($fields['resettled'] ?? '') === 'Resettled' || ($fields['resettled'] ?? false) === true)>Resettled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Disaster Victim</label>
                            <select name="fields[disaster_type]" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                                <option value="">None / Not Applicable</option>
                                <option value="ND" @selected(($fields['disaster_type'] ?? '') === 'ND')>ND (Natural Disaster)</option>
                                <option value="MD" @selected(($fields['disaster_type'] ?? '') === 'MD')>MD (Man-made Disaster)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Occurrence</label>
                            <input type="text" name="fields[date_of_occurrence]" value="{{ $fields['date_of_occurrence'] ?? '' }}" placeholder="e.g. August 2026" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type of Assistance (Category)</label>
                        @php
                            $selectedCategories = (array) ($fields['program_category'] ?? []);
                            $categories = ['Cultural Minorities', 'Refugees', 'Evacuees', 'AICS', 'Returnee', 'Squatter', 'ESA', 'Others'];
                        @endphp
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 text-sm">
                            @foreach($categories as $cat)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="fields[program_category][]" value="{{ $cat }}" @checked(in_array($cat, $selectedCategories) || ($cat === 'AICS' && empty($selectedCategories)))>
                                    <span>{{ $cat }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estimated Damage</label>
                            <input type="text" name="fields[estimated_damage]" value="{{ $fields['estimated_damage'] ?? '' }}" placeholder="Php or description" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Casualties (Name)</label>
                            <input type="text" name="fields[casualties]" value="{{ $fields['casualties'] ?? '' }}" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Others (Specify)</label>
                            <input type="text" name="fields[others]" value="{{ $fields['others'] ?? '' }}" class="mt-1 w-full rounded border px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>
            @elseif($document->template === 'certificate_of_eligibility')
                <div class="border-t pt-4 space-y-3 bg-gray-50 p-4 rounded text-sm text-gray-700">
                    <p class="font-semibold text-gray-800">BA Form 206 Official Signatories (Configured in System Settings):</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-gray-500 block">MSWDO Reviewer:</span>
                            <span class="font-medium">{{ $settings['social_worker_name'] ?? 'LAARNI L. ACOSTA, RSW' }}</span><br>
                            <span class="text-xs text-gray-600">{{ $settings['social_worker_title'] ?? 'Municipal Social Welfare and Development Officer' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 block">Approved By:</span>
                            <span class="font-medium">{{ $settings['mayor_name'] ?? 'EVANGELINE I. VERZOSA' }}</span><br>
                            <span class="text-xs text-gray-600">{{ $settings['mayor_title'] ?? 'Municipal Mayor' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    {{ $document->template === 'certificate_of_eligibility' ? 'Records and Case Study Reviewed' : ($document->template === 'application_form' ? 'Reason / Problem Narrative' : 'Case narrative') }}
                </label>
                <textarea name="fields[case_narrative]" rows="3" class="mt-1 w-full rounded border px-3 py-2 text-sm">{{ $fields['case_narrative'] ?? ($caseRecord->problem_presented ?? $intake?->social_worker_assessment ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Document notes (Internal)</label>
                <textarea name="notes" rows="2" class="mt-1 w-full rounded border px-3 py-2 text-sm">{{ $document->notes }}</textarea>
            </div>

            <button class="rounded bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700">Save for Review</button>
        </form>

        <div class="mt-4 flex gap-3">
            <a class="rounded bg-gray-700 px-4 py-2 text-white text-sm hover:bg-gray-800" target="_blank" href="{{ route('case-records.documents.preview', [$caseRecord, $document]) }}">Preview before finalizing</a>
            @can('finalize', $document)
                <form method="POST" action="{{ route('case-records.documents.finalize', [$caseRecord, $document]) }}">
                    @csrf
                    <button class="rounded bg-green-700 px-4 py-2 text-white text-sm hover:bg-green-800">Finalize and Print</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
