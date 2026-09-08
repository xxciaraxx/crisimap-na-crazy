<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $pageTitle ?? 'Beneficiary Records' }}</h2>
                <p class="text-sm text-gray-500">{{ $pageDescription ?? 'Search, verify, and track assistance history.' }}</p>
            </div>
            @if(auth()->user()->role !== 'social_officer')
                <a href="{{ route('beneficiaries.import.create') }}" class="rounded bg-slate-600 px-4 py-2 text-white">Import</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(isset($pendingIntakes) && $pendingIntakes->isNotEmpty())
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h3 class="font-semibold text-gray-900">General Intake Sheets awaiting your review</h3>
                        <p class="text-sm text-gray-500">Open the full form to review and process each application.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Intake</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Client</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Program</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th><th></th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($pendingIntakes as $intake)
                                    <tr>
                                        <td class="px-4 py-3 text-sm">{{ $intake->intake_number }}</td>
                                        <td class="px-4 py-3">{{ $intake->client_name }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $intake->program }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $intake->statusLabel() }}</td>
                                        <td class="px-4 py-3 text-sm"><a href="{{ route('applications.intakes.review', $intake) }}" class="font-semibold text-blue-700">Review full form</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            <div class="bg-white shadow overflow-hidden sm:rounded-lg p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
                    <input name="search" value="{{ request('search') }}" placeholder="Search name or code" class="border rounded px-3 py-2" />
                    <select name="barangay_id" class="border rounded px-3 py-2">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
                        @endforeach
                    </select>
                    <select name="assistance_type_id" class="border rounded px-3 py-2">
                        <option value="">All Assistance Types</option>
                        @foreach($assistanceTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('assistance_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">All Status</option>
                        <option value="Pending" @selected(request('status') == 'Pending')>Pending</option>
                        <option value="Approved" @selected(request('status') == 'Approved')>Approved</option>
                        <option value="Released" @selected(request('status') == 'Released')>Released</option>
                        <option value="Completed" @selected(request('status') == 'Completed')>Completed</option>
                        <option value="Rejected" @selected(request('status') == 'Rejected')>Rejected</option>
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}" class="border rounded px-3 py-2" />
                    <input type="date" name="to" value="{{ request('to') }}" class="border rounded px-3 py-2" />
                    <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white shadow-sm hover:bg-blue-800">Apply filters</button>
                    @if(request()->hasAny(['search', 'barangay_id', 'assistance_type_id', 'status', 'from', 'to']))
                        <a href="{{ url()->current() }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-slate-600 hover:bg-slate-50">Clear</a>
                    @endif
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">Assistance records</h3>
                        <p class="text-xs text-slate-500">Showing {{ $beneficiaries->firstItem() ?? 0 }}–{{ $beneficiaries->lastItem() ?? 0 }} of {{ $beneficiaries->total() }} records</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $beneficiaries->total() }} total</span>
                </div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Birth Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barangay</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assistance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rejection Remarks</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($beneficiaries as $beneficiary)
                                @php($isRejected = strtolower((string) $beneficiary->status) === 'rejected')
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->reference_code ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $beneficiary->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->birth_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->barangay->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->assistanceType->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->date_of_assistance?->format('M d, Y') }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$beneficiary->status" title="{{ $isRejected ? ($beneficiary->rejection_reason ?? '') : '' }}" /></td>
                                    <td class="px-4 py-3 text-sm text-red-700">{{ $isRejected ? ($beneficiary->rejection_reason ?? 'No remarks provided.') : '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex min-w-[190px] flex-wrap gap-2 text-xs font-semibold">
                                        <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="rounded-md bg-blue-50 px-2.5 py-1.5 text-blue-700 hover:bg-blue-100">View</a>
                                        @can('update', $beneficiary)<a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="rounded-md bg-emerald-50 px-2.5 py-1.5 text-emerald-700 hover:bg-emerald-100">Edit</a>@endcan
                                        @if(in_array($beneficiary->status, ['Approved', 'Released', 'Completed'], true) && in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden', null], true))
                                            @can('updateFinalAics', $beneficiary)<a href="{{ route('beneficiaries.application-form.edit', $beneficiary) }}" class="rounded-md bg-violet-50 px-2.5 py-1.5 text-violet-700 hover:bg-violet-100">Review AICS</a>@endcan
                                            <a target="_blank" href="{{ route('beneficiaries.application-form', $beneficiary) }}" class="rounded-md bg-slate-100 px-2.5 py-1.5 text-slate-700 hover:bg-slate-200">Print</a>
                                        @endif
                                        @can('release', $beneficiary)
                                            <form method="POST" action="{{ route('beneficiaries.release', $beneficiary) }}" class="inline" onsubmit="return confirm('Confirm that the applicant received the assistance and release this record?');">
                                                @csrf
                                                <button type="submit" class="rounded-md bg-emerald-50 px-2.5 py-1.5 text-emerald-700 hover:bg-emerald-100">Release</button>
                                            </form>
                                        @endcan
                                        @if($beneficiary->reference_code)
                                            <a href="{{ route('beneficiaries.verify', $beneficiary->reference_code) }}" class="rounded-md bg-slate-100 px-2.5 py-1.5 text-slate-600 hover:bg-slate-200">Verify</a>
                                        @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-6 text-center text-gray-500">No beneficiaries found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-4 p-4 md:hidden">
                    @forelse($beneficiaries as $beneficiary)
                        @php($isRejected = strtolower((string) $beneficiary->status) === 'rejected')
                        <article class="rounded-xl border border-slate-200 p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $beneficiary->reference_code ?? 'No reference' }}</p><h4 class="mt-1 font-semibold text-slate-900">{{ $beneficiary->name }}</h4><p class="text-sm text-slate-500">{{ $beneficiary->assistanceType->name ?? 'Assistance not specified' }} · {{ $beneficiary->barangay->name ?? 'Barangay not specified' }}</p></div>
                                <x-status-badge :status="$beneficiary->status" />
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-400">Amount</dt><dd class="font-semibold">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</dd></div><div><dt class="text-xs text-slate-400">Assistance date</dt><dd class="font-semibold">{{ $beneficiary->date_of_assistance?->format('M d, Y') ?? '—' }}</dd></div></dl>
                            @if($isRejected)<p class="mt-3 rounded-lg bg-red-50 p-2 text-xs text-red-700">{{ $beneficiary->rejection_reason ?? 'No remarks provided.' }}</p>@endif
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold"><a href="{{ route('beneficiaries.show', $beneficiary) }}" class="rounded-md bg-blue-50 px-3 py-2 text-blue-700">View</a>@can('update', $beneficiary)<a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="rounded-md bg-emerald-50 px-3 py-2 text-emerald-700">Edit</a>@endcan
                                @if(in_array($beneficiary->status, ['Approved', 'Released', 'Completed'], true) && in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden', null], true))@can('updateFinalAics', $beneficiary)<a href="{{ route('beneficiaries.application-form.edit', $beneficiary) }}" class="rounded-md bg-violet-50 px-3 py-2 text-violet-700">Review AICS</a>@endcan<a target="_blank" href="{{ route('beneficiaries.application-form', $beneficiary) }}" class="rounded-md bg-slate-100 px-3 py-2 text-slate-700">Print</a>@endif
                            </div>
                        </article>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">No beneficiaries found.</p>
                    @endforelse
                </div>
                <div class="border-t border-slate-200 p-4">{{ $beneficiaries->onEachSide(1)->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
