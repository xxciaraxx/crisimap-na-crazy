<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">General Intake Sheet {{ $intake->intake_number }}</h2><p class="text-sm text-slate-500">Applicant information for AICS assistance.</p></div></x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        @if(session('success'))<div class="mb-5 border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
        <section class="border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Official document</p><h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $intake->intake_number }}</h3><p class="text-sm text-slate-500">{{ $intake->client_name }}</p></div>
                <div class="flex flex-wrap gap-2">
                    @can('update', $intake)<a class="inline-flex items-center bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" href="{{ route('intakes.edit', $intake) }}">Edit Intake Form</a>@endcan
                    @can('view', $intake)<a class="inline-flex items-center border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route('intakes.print', $intake) }}" title="Open print preview">Print</a>@endcan
                    @can('submit', $intake)<form method="POST" action="{{ route('intakes.submit', $intake) }}">@csrf<button class="inline-flex items-center bg-green-700 px-3 py-2 text-sm font-medium text-white hover:bg-green-800" type="submit">Submit for Review</button></form>@endcan
                </div>
            </div>
            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</dt><dd class="mt-1 font-medium text-slate-900">{{ $intake->statusLabel() }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Program / client type</dt><dd class="mt-1 text-slate-700">{{ $intake->program }} / {{ $intake->client_type }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Purpose</dt><dd class="mt-1 text-slate-700">{{ $intake->purpose_of_assistance ?: 'Not specified' }}@if($intake->assistance_specification) — {{ $intake->assistance_specification }}@endif</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Requested amount</dt><dd class="mt-1 text-slate-700">PHP {{ number_format((float) $intake->requested_amount, 2) }}</dd></div>
            </div>
            @if($intake->beneficiaries->isNotEmpty())
                <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 px-6 py-4">
                    @foreach($intake->beneficiaries as $beneficiary)
                        @can('release', $beneficiary)
                            <form method="POST" action="{{ route('beneficiaries.release', $beneficiary) }}" onsubmit="return confirm('Confirm that the applicant received the assistance and release this record?');">
                                @csrf
                                <button type="submit" class="inline-flex bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">Release Assistance</button>
                            </form>
                        @endcan
                    @endforeach
                </div>
            @endif

            @can('decide', $intake)
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Focal person review</h3>
                    <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-start">
                        <form method="POST" action="{{ route('intakes.decide', $intake) }}" class="flex-1 md:max-w-md">
                            @csrf
                            <input type="hidden" name="decision" value="approved">
                            <button type="submit" class="inline-flex w-full items-center justify-center bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">Approve Intake</button>
                        </form>
                        <form method="POST" action="{{ route('intakes.decide', $intake) }}" class="flex-1 md:max-w-md">
                            @csrf
                            <input type="hidden" name="decision" value="rejected">
                            <textarea name="rejection_reason" rows="2" maxlength="2000" required placeholder="Reason for rejection" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                            <button type="submit" class="mt-2 inline-flex w-full items-center justify-center bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Reject Intake</button>
                        </form>
                    </div>
                </div>
            @endcan

            @can('headDecide', $intake)
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">MSWDO Head approval</h3>
                    <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-start">
                        <form method="POST" action="{{ route('intakes.head-decide', $intake) }}" class="flex-1 md:max-w-md">
                            @csrf
                            <input type="hidden" name="decision" value="approved">
                            <button type="submit" class="inline-flex w-full items-center justify-center bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">Approve Intake</button>
                        </form>
                        <form method="POST" action="{{ route('intakes.head-decide', $intake) }}" class="flex-1 md:max-w-md">
                            @csrf
                            <input type="hidden" name="decision" value="rejected">
                            <textarea name="rejection_reason" rows="2" maxlength="2000" required placeholder="Reason for rejection" class="w-full rounded border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                            <button type="submit" class="mt-2 inline-flex w-full items-center justify-center bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Reject Intake</button>
                        </form>
                    </div>
                </div>
            @endcan

            @if($intake->status === 'rejected' && $intake->rejection_reason)
                <div class="border-t border-slate-200 bg-red-50 px-6 py-4 text-sm text-red-700">
                    <span class="font-semibold">Rejection reason:</span> {{ $intake->rejection_reason }}
                    <span class="ml-2 text-xs text-red-600">Rejected by {{ $intake->rejectedBy?->name ?? 'Unknown' }}{{ $intake->rejected_at ? ' on '.$intake->rejected_at->format('M d, Y h:i A') : '' }}</span>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
