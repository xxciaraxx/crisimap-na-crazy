<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-semibold text-slate-900">General Intake Sheet {{ $intake->intake_number }}</h2><p class="text-sm text-slate-500">Applicant information for AICS assistance.</p></div></x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6" x-data="{ rejectOpen: false, rejectAction: '', rejectRole: '' }">
        @if(session('success'))<div class="mb-5 border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>@endif
        <section class="border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-400">Official document</p><h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $intake->intake_number }}</h3><p class="text-sm text-slate-500">{{ $intake->client_name }}</p></div>
                <div class="flex flex-wrap gap-2">
                    @can('update', $intake)<a class="inline-flex items-center bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" href="{{ route('intakes.edit', $intake) }}">Edit Intake Form</a>@endcan
                    @can('view', $intake)<a class="inline-flex items-center border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route('intakes.print', $intake) }}" title="Open print preview">Print</a>@endcan
                    @can('submit', $intake)<a class="inline-flex items-center bg-green-700 px-3 py-2 text-sm font-medium text-white hover:bg-green-800" href="{{ route('intakes.review', $intake) }}">Review &amp; submit</a>@endcan
                </div>
            </div>
            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</dt><dd class="mt-1 font-medium text-slate-900">{{ $intake->statusLabel() }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Program / client type</dt><dd class="mt-1 text-slate-700">{{ $intake->program }} / {{ $intake->client_type }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Purpose</dt><dd class="mt-1 text-slate-700">{{ $intake->purpose_of_assistance ?: 'Not specified' }}@if($intake->assistance_specification) — {{ $intake->assistance_specification }}@endif</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Requested amount</dt><dd class="mt-1 text-slate-700">PHP {{ number_format((float) $intake->requested_amount, 2) }}</dd></div>
            </div>
            @if($intake->attachments->isNotEmpty())
                <div class="border-t border-slate-200 px-6 py-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Attached documents</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($intake->attachments as $attachment)
                            <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <span class="max-w-xs truncate text-slate-700" title="{{ $attachment->name }}">{{ $attachment->name }}</span>
                                <a href="{{ route('attachments.view', $attachment) }}" target="_blank" rel="noopener" class="rounded-md bg-blue-700 px-2.5 py-1 text-xs font-semibold text-white hover:bg-blue-800">View attachment</a>
                                <a href="{{ route('attachments.download', $attachment) }}" class="rounded-md bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-300">Download</a>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
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
                        <button type="button" class="flex-1 bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 md:max-w-md" @click="rejectAction = '{{ route('intakes.decide', $intake) }}'; rejectRole = 'Focal Person'; rejectOpen = true">Reject Intake</button>
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
                        <button type="button" class="flex-1 bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 md:max-w-md" @click="rejectAction = '{{ route('intakes.head-decide', $intake) }}'; rejectRole = 'MSWDO Head'; rejectOpen = true">Reject Intake</button>
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
        <div x-cloak x-show="rejectOpen" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="intake-reject-title" @keydown.escape.window="rejectOpen = false">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" @click.outside="rejectOpen = false">
                <div class="mb-4 grid h-10 w-10 place-items-center rounded-full bg-red-100 font-bold text-red-700" aria-hidden="true">!</div>
                <p class="text-xs font-bold uppercase tracking-[.16em] text-red-600">Rejection confirmation</p>
                <h3 id="intake-reject-title" class="mt-1 text-lg font-semibold text-slate-900">Reject this application?</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">You are rejecting <strong>{{ $intake->intake_number }}</strong> as <strong x-text="rejectRole"></strong>. A reason is required.</p>
                <form method="POST" :action="rejectAction" class="mt-4">
                    @csrf
                    <input type="hidden" name="decision" value="rejected">
                    <label for="intake-rejection-reason" class="text-sm font-semibold text-slate-700">Reason for rejection</label>
                    <textarea id="intake-rejection-reason" name="rejection_reason" rows="4" maxlength="2000" required placeholder="Explain why this application is being rejected" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" class="inline-flex justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="rejectOpen = false">Cancel</button>
                        <button type="submit" class="inline-flex justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
