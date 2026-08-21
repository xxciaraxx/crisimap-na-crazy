<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Record Verification</h2></x-slot>
    <div class="py-6"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8"><div class="bg-white shadow rounded-lg p-6 space-y-6">
        <div class="space-y-2"><p class="text-sm text-gray-500">Record reference</p><p class="text-2xl font-semibold">{{ $beneficiary->reference_code }}</p><p class="text-sm text-gray-500">This page verifies the internal record reference; no QR code is used.</p></div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm"><div class="rounded border p-3"><p class="text-gray-500">Client</p><p class="font-semibold">{{ $beneficiary->name }}</p></div><div class="rounded border p-3"><p class="text-gray-500">Status</p><p class="font-semibold">{{ $beneficiary->status }}</p></div><div class="rounded border p-3"><p class="text-gray-500">Assistance</p><p class="font-semibold">{{ $beneficiary->assistanceType->name ?? 'N/A' }}</p></div><div class="rounded border p-3"><p class="text-gray-500">Barangay</p><p class="font-semibold">{{ $beneficiary->barangay->name ?? 'N/A' }}</p></div></div>
        <div class="flex gap-3"><a href="{{ route('beneficiaries.show', $beneficiary) }}" class="bg-blue-600 text-white px-4 py-2 rounded">Open Record</a><a href="{{ route('beneficiaries.index') }}" class="bg-gray-200 px-4 py-2 rounded">Back to List</a></div>
    </div></div></div>
</x-app-layout>
