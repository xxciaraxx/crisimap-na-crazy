<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">QR Verification</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 space-y-6">
                <div class="flex flex-col md:flex-row gap-6 md:items-start">
                    <img
                        alt="Verification QR"
                        class="border rounded-lg p-2"
                        src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode(route('beneficiaries.verify', $beneficiary->reference_code)) }}"
                    >
                    <div class="space-y-2">
                        <p class="text-sm text-gray-500">Reference Code</p>
                        <p class="text-2xl font-semibold">{{ $beneficiary->reference_code }}</p>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold">{{ $beneficiary->status }}</p>
                        <p class="text-sm text-gray-500">Eligibility</p>
                        <p class="font-semibold">{{ $beneficiary->eligibility_status ?? 'Eligible' }}</p>
                        <p class="text-sm text-gray-500">Verified Record</p>
                        <p class="font-semibold">{{ $beneficiary->name }}</p>
                        <p class="text-sm text-gray-500">{{ $beneficiary->barangay->name ?? 'N/A' }} - {{ $beneficiary->assistanceType->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="rounded border p-3"><p class="text-gray-500">Birth Date</p><p class="font-semibold">{{ $beneficiary->birth_date?->format('M d, Y') ?? 'N/A' }}</p></div>
                    <div class="rounded border p-3"><p class="text-gray-500">Date of Assistance</p><p class="font-semibold">{{ $beneficiary->date_of_assistance?->format('M d, Y') ?? 'N/A' }}</p></div>
                    <div class="rounded border p-3"><p class="text-gray-500">Amount</p><p class="font-semibold">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</p></div>
                    <div class="rounded border p-3"><p class="text-gray-500">Barangay</p><p class="font-semibold">{{ $beneficiary->barangay->name ?? 'N/A' }}</p></div>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-2">History Snapshot</h3>
                    <p class="text-sm text-gray-700">This record belongs to a history set with {{ $historySummary['total_assistance'] }} application(s) and PHP {{ number_format($historySummary['total_amount'], 2) }} total assistance.</p>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="bg-blue-600 text-white px-4 py-2 rounded">Open Record</a>
                    <a href="{{ route('beneficiaries.index') }}" class="bg-gray-200 px-4 py-2 rounded">Back to List</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
