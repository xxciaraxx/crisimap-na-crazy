<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <div>
                <h2 class="text-xl font-semibold">General Intake Sheet {{ $intake->intake_number }}</h2>
                <p class="text-sm">Applicant information for AICS assistance.</p>
            </div>
            @can('update', $intake)
                <a class="rounded bg-blue-600 px-4 py-2 text-white" href="{{ route('intakes.edit', $intake) }}">Edit</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6 mx-auto max-w-4xl">
        <section class="rounded bg-white p-6 shadow">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div><dt>Client</dt><dd class="font-semibold">{{ $intake->client_name }}</dd></div>
                <div><dt>Program</dt><dd>{{ $intake->program }} / {{ $intake->client_type }}</dd></div>
                <div><dt>Purpose</dt><dd>{{ $intake->purpose_of_assistance ?: '—' }}</dd></div>
                <div><dt>Requested amount</dt><dd>PHP {{ number_format((float) $intake->requested_amount, 2) }}</dd></div>
            </dl>
        </section>
    </div>
</x-app-layout>
