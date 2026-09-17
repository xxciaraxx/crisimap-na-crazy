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
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 via-white to-blue-50/60 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-blue-100 p-2 text-blue-700">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M6 12h12m-8 7h4" /></svg>
                                </span>
                                <h3 class="text-base font-semibold text-slate-900">Filter records</h3>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Narrow the records by applicant, location, assistance, status, or date.</p>
                        </div>
                        @if(request()->hasAny(['search', 'barangay_id', 'assistance_type_id', 'status', 'from', 'to']))
                            <a href="{{ url()->current() }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">Clear all</a>
                        @endif
                    </div>
                </div>
                <form method="GET" class="space-y-5 p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="xl:col-span-1">
                            <label for="beneficiary_search" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Search</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path stroke-linecap="round" d="m20 20-4-4" /></svg>
                                <input id="beneficiary_search" name="search" value="{{ request('search') }}" placeholder="Name or reference code" class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                        <div>
                            <label for="beneficiary_barangay" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Barangay</label>
                            <select id="beneficiary_barangay" name="barangay_id" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All barangays</option>
                                @foreach($barangays as $barangay)
                                    <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="beneficiary_assistance" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Assistance type</label>
                            <select id="beneficiary_assistance" name="assistance_type_id" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">All assistance types</option>
                                @foreach($assistanceTypes as $type)
                                    <option value="{{ $type->id }}" @selected(request('assistance_type_id') == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(request()->routeIs('applications.index'))
                            <div>
                                <label for="beneficiary_status" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                                <select id="beneficiary_status" name="status" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All statuses</option>
                                    <option value="Pending" @selected(request('status') == 'Pending')>Pending</option>
                                    <option value="Approved" @selected(request('status') == 'Approved')>Approved</option>
                                </select>
                            </div>
                        @endif
                    </div>
                    <fieldset class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <legend class="px-2 text-xs font-bold uppercase tracking-wide text-slate-500">Date range</legend>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="beneficiary_from" class="mb-1.5 block text-sm font-medium text-slate-600">From date</label>
                                <input id="beneficiary_from" type="date" name="from" value="{{ request('from') }}" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="beneficiary_to" class="mb-1.5 block text-sm font-medium text-slate-600">To date</label>
                                <input id="beneficiary_to" type="date" name="to" value="{{ request('to') }}" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                    </fieldset>
                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                        <button class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Apply filters</button>
                        @if(request()->hasAny(['search', 'barangay_id', 'assistance_type_id', 'status', 'from', 'to']))
                            <a href="{{ url()->current() }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            @unless(request()->routeIs('applications.index'))
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beneficiary Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Records</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Availed Assistance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barangay</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Release Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($beneficiaries as $beneficiary)
                                @php($isRejected = strtolower((string) $beneficiary->status) === 'rejected')
                                @php($clientName = $beneficiary->intake?->client_name ?: $beneficiary->name)
                                @php($beneficiaryName = $beneficiary->intake?->beneficiary_name ?: $beneficiary->name)
                                @php($beneficiaryLabel = strcasecmp(trim((string) $clientName), trim((string) $beneficiaryName)) === 0 ? 'Self' : $beneficiaryName)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->reference_code ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $clientName }}</td>
                                    <td class="px-4 py-3">{{ $beneficiaryLabel }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->historyEntries->whereNull('archived_at')->count() }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->historyEntries->whereNull('archived_at')->pluck('assistanceType.name')->filter()->unique()->implode(', ') ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->barangay->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->date_of_assistance?->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->released_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$beneficiary->status" title="{{ $isRejected ? ($beneficiary->rejection_reason ?? '') : '' }}" /></td>
                                    <td class="px-4 py-3">
                                        <div class="flex min-w-[190px] flex-wrap gap-2 text-xs font-semibold">
                                        <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="rounded-md bg-blue-50 px-2.5 py-1.5 text-blue-700 hover:bg-blue-100">View</a>
                                        @if(!auth()->user()->hasRole('social_officer'))
                                            @can('update', $beneficiary)
                                                <a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="rounded-md bg-emerald-50 px-2.5 py-1.5 text-emerald-700 hover:bg-emerald-100">Edit</a>
                                            @endcan
                                        @endif
                                        @can('release', $beneficiary)
                                            <form method="POST" action="{{ route('beneficiaries.release', $beneficiary) }}" class="inline" onsubmit="return confirm('Confirm that the applicant received the assistance and release this record?');">
                                                @csrf
                                                <button type="submit" class="rounded-md bg-emerald-50 px-2.5 py-1.5 text-emerald-700 hover:bg-emerald-100">Release</button>
                                            </form>
                                        @endcan
                                        @if($beneficiary->reference_code && !auth()->user()->hasRole('social_officer', 'focal_person'))
                                            <a href="{{ route('beneficiaries.verify', $beneficiary->reference_code) }}" class="rounded-md bg-slate-100 px-2.5 py-1.5 text-slate-600 hover:bg-slate-200">Verify</a>
                                        @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="px-4 py-6 text-center text-gray-500">No beneficiaries found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-4 p-4 md:hidden">
                    @forelse($beneficiaries as $beneficiary)
                        @php($isRejected = strtolower((string) $beneficiary->status) === 'rejected')
                        @php($clientName = $beneficiary->intake?->client_name ?: $beneficiary->name)
                        @php($beneficiaryName = $beneficiary->intake?->beneficiary_name ?: $beneficiary->name)
                        @php($beneficiaryLabel = strcasecmp(trim((string) $clientName), trim((string) $beneficiaryName)) === 0 ? 'Self' : $beneficiaryName)
                        <article class="rounded-xl border border-slate-200 p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $beneficiary->reference_code ?? 'No reference' }}</p><h4 class="mt-1 font-semibold text-slate-900">{{ $clientName }}</h4><p class="text-sm text-slate-500">Client · {{ $beneficiaryLabel === 'Self' ? 'Beneficiary: Self' : 'Beneficiary: '.$beneficiaryLabel }}</p><p class="text-sm text-slate-500">{{ $beneficiary->assistanceType->name ?? 'Assistance not specified' }} · {{ $beneficiary->barangay->name ?? 'Barangay not specified' }}</p></div>
                                <x-status-badge :status="$beneficiary->status" />
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-slate-400">Records</dt><dd class="font-semibold">{{ $beneficiary->historyEntries->whereNull('archived_at')->count() }}</dd></div><div><dt class="text-xs text-slate-400">Availed assistance</dt><dd class="font-semibold">{{ $beneficiary->historyEntries->whereNull('archived_at')->pluck('assistanceType.name')->filter()->unique()->implode(', ') ?: '—' }}</dd></div><div><dt class="text-xs text-slate-400">Amount</dt><dd class="font-semibold">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</dd></div><div><dt class="text-xs text-slate-400">Assistance date</dt><dd class="font-semibold">{{ $beneficiary->date_of_assistance?->format('M d, Y') ?? '—' }}</dd></div><div><dt class="text-xs text-slate-400">Release date</dt><dd class="font-semibold">{{ $beneficiary->released_at?->format('M d, Y') ?? '—' }}</dd></div></dl>
                            @if($isRejected)<p class="mt-3 rounded-lg bg-red-50 p-2 text-xs text-red-700">{{ $beneficiary->rejection_reason ?? 'No remarks provided.' }}</p>@endif
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="rounded-md bg-blue-50 px-3 py-2 text-blue-700">View</a>
                                @if(!auth()->user()->hasRole('social_officer'))
                                    @can('update', $beneficiary)
                                        <a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="rounded-md bg-emerald-50 px-3 py-2 text-emerald-700">Edit</a>
                                    @endcan
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">No beneficiaries found.</p>
                    @endforelse
                </div>
                <div class="border-t border-slate-200 p-4">{{ $beneficiaries->onEachSide(1)->links() }}</div>
            </div>
            @endunless
        </div>
    </div>
</x-app-layout>
