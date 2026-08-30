<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Beneficiary Details</h2>
                <p class="text-sm text-gray-500">Reference: {{ $beneficiary->reference_code ?? 'Pending generation' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('update', $beneficiary)<a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="bg-blue-600 text-white px-4 py-2 rounded">Edit</a>@endcan
                @if(in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden']))<a target="_blank" href="{{ route('beneficiaries.application-form', $beneficiary) }}" class="bg-gray-700 text-white px-4 py-2 rounded">Print Application Form</a>@endif
                @if(in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden']))<a target="_blank" href="{{ route('beneficiaries.certificate', $beneficiary) }}" class="bg-green-700 text-white px-4 py-2 rounded">Print Certificate of Eligibility</a>@endif
                @if($beneficiary->reference_code)
                    <a href="{{ route('beneficiaries.verify', $beneficiary->reference_code) }}" class="bg-gray-800 text-white px-4 py-2 rounded">View Record Verification</a>
                @endif
                @can('delete', $beneficiary)<form action="{{ route('beneficiaries.destroy', $beneficiary) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <input name="archive_reason" required maxlength="1000" placeholder="Reason for archiving" class="rounded border px-2 py-1 text-sm" aria-label="Reason for archiving this beneficiary">
                    <button class="bg-red-600 text-white px-4 py-2 rounded" onclick="return confirm('Archive this beneficiary record?')">Archive</button>
                </form>@endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-breadcrumbs :items="[['label' => 'Beneficiaries', 'url' => route('beneficiaries.index')], ['label' => $beneficiary->reference_code ?? $beneficiary->name]]" />
            @if(session('warning'))
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900">{{ session('warning') }}</div>
            @endif
            @if(session('error'))<div class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900">{{ session('error') }}</div>@endif
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Profile</h3>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-gray-500">Name</dt><dd class="font-semibold">{{ $beneficiary->name }}</dd></div>
                        <div><dt class="text-gray-500">Reference</dt><dd class="font-semibold">{{ $beneficiary->reference_code ?? 'N/A' }}</dd></div>
                        <div><dt class="text-gray-500">Age / Sex</dt><dd class="font-semibold">{{ $beneficiary->age }} / {{ $beneficiary->sex }}</dd></div>
                        <div><dt class="text-gray-500">Birth Date</dt><dd class="font-semibold">{{ $beneficiary->birth_date?->format('M d, Y') ?? 'N/A' }}</dd></div>
                        <div><dt class="text-gray-500">Contact</dt><dd class="font-semibold">{{ $beneficiary->contact ?? 'N/A' }}</dd></div>
                        <div><dt class="text-gray-500">Barangay</dt><dd class="font-semibold">{{ $beneficiary->barangay->name ?? 'N/A' }}</dd></div>
                        <div><dt class="text-gray-500">Assistance Type</dt><dd class="font-semibold">{{ $beneficiary->assistanceType->name ?? 'N/A' }}</dd></div>
                        <div><dt class="text-gray-500">Status</dt><dd class="font-semibold">{{ $beneficiary->status }}</dd></div>
                        <div><dt class="text-gray-500">Amount</dt><dd class="font-semibold">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</dd></div>
                        <div><dt class="text-gray-500">Eligibility</dt><dd class="font-semibold">{{ $beneficiary->eligibility_status ?? 'Eligible' }}</dd></div>
                        <div><dt class="text-gray-500">Address</dt><dd class="font-semibold">{{ $beneficiary->address ?? 'N/A' }}</dd></div>
                        <div><dt class="text-gray-500">Coordinates</dt><dd class="font-semibold">{{ $beneficiary->latitude }}, {{ $beneficiary->longitude }}</dd></div>
                        @if($beneficiary->status === 'Rejected')<div class="col-span-2"><dt class="text-gray-500">Rejection Remarks</dt><dd class="font-semibold text-red-700">{{ $beneficiary->rejection_reason }}</dd><dd class="text-xs text-gray-500">{{ $beneficiary->rejected_at?->format('M d, Y h:i A') }} by {{ $beneficiary->rejectedBy?->name ?? 'N/A' }}</dd></div>@endif
                    </dl>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">History Summary</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded border p-3"><p class="text-gray-500">Total Assistance</p><p class="text-xl font-semibold">{{ $historySummary['total_assistance'] }}</p></div>
                        <div class="rounded border p-3"><p class="text-gray-500">Total Amount</p><p class="text-xl font-semibold">PHP {{ number_format($historySummary['total_amount'], 2) }}</p></div>
                        <div class="rounded border p-3"><p class="text-gray-500">Last Assistance</p><p class="font-semibold">{{ $historySummary['last_assistance_date']?->format('M d, Y') ?? 'N/A' }}</p></div>
                        <div class="rounded border p-3"><p class="text-gray-500">Released</p><p class="font-semibold">{{ $historySummary['released'] }}</p></div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-500 mb-2">Assistance Categories</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse($historySummary['categories'] as $category)
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs">{{ $category }}</span>
                            @empty
                                <span class="text-sm text-gray-500">No assistance history yet.</span>
                            @endforelse
                        </div>
                    </div>
                    @can('decide', $beneficiary)<div class="mt-6 border-t pt-4"><p class="mb-3 text-sm text-gray-600">MSWDO Head decision</p><div class="flex gap-2"><form action="{{ route('beneficiaries.decide', $beneficiary) }}" method="POST">@csrf<input type="hidden" name="decision" value="approved"><button class="rounded bg-green-700 px-4 py-2 text-white">Approve Application</button></form><button type="button" @click="$dispatch('open-modal', 'reject-application')" class="rounded bg-red-600 px-4 py-2 text-white">Reject Application</button></div><x-modal name="reject-application" focusable><form action="{{ route('beneficiaries.decide', $beneficiary) }}" method="POST" class="p-6">@csrf<input type="hidden" name="decision" value="rejected"><h3 class="text-lg font-semibold text-gray-900">Reject this application?</h3><label class="mt-4 block text-sm font-medium text-gray-700">Reason for rejection</label><textarea required maxlength="2000" name="rejection_reason" class="mt-2 w-full rounded border-gray-300"></textarea><div class="mt-5 flex justify-end gap-3"><button type="button" @click="$dispatch('close')" class="rounded border px-4 py-2">Cancel</button><button class="rounded bg-red-600 px-4 py-2 text-white">Confirm Rejection</button></div></form></x-modal></div>@endcan
                    @can('release', $beneficiary)<div class="mt-6 border-t pt-4"><form action="{{ route('beneficiaries.release', $beneficiary) }}" method="POST">@csrf<button class="rounded bg-blue-700 px-4 py-2 text-white">Mark Funds Released</button></form></div>@endcan
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Case Details</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $beneficiary->case_info ?? 'No narrative provided.' }}</p>
                    <div class="mt-4">
                        <p class="text-sm text-gray-500">Encoded by</p>
                        <p class="font-semibold">{{ $beneficiary->encoder->name ?? 'N/A' }}</p>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-500">Override</p>
                        <p class="font-semibold">{{ $beneficiary->overrideUser->name ?? 'None' }}</p>
                        <p class="text-xs text-gray-500">{{ $beneficiary->override_justification ?? 'No override justification.' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Assistance Timeline</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Date</th>
                                    <th class="px-4 py-2 text-left">Type</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($historyEntries as $entry)
                                    <tr>
                                        <td class="px-4 py-2">{{ $entry->date_of_assistance?->format('M d, Y') ?? $entry->created_at->format('M d, Y') }}</td>
                                        <td class="px-4 py-2">{{ $entry->assistanceType->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2">PHP {{ number_format((float) ($entry->amount ?? 0), 2) }}</td>
                                        <td class="px-4 py-2">{{ $entry->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No timeline entries yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Processing History</h3>
                    <div class="space-y-3">
                        @forelse($processingHistory as $log)
                            <div class="rounded border p-3">
                                <p class="text-sm font-semibold">{{ $log->action }}</p>
                                <p class="text-sm text-gray-700">{{ $log->description }}</p>
                                <p class="text-xs text-gray-500">{{ $log->user->name ?? 'System' }} - {{ $log->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No processing logs recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Attachments</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($beneficiary->attachments as $attachment)
                        <div class="rounded-lg border p-4">
                            <p class="font-semibold">{{ $attachment->name }}</p>
                            <p class="text-xs text-gray-500">{{ $attachment->document_type }}</p>
                            <a href="{{ route('attachments.download', $attachment) }}" class="mt-2 inline-block text-blue-600 text-sm">Download</a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No attachments.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
