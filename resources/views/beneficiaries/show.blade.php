<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Beneficiary Details</h2>
            <div class="space-x-2">
                <a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="bg-blue-600 text-white px-4 py-2 rounded">Edit</a>
                <form action="{{ route('beneficiaries.destroy', $beneficiary) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button class="bg-red-600 text-white px-4 py-2 rounded">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Profile</h3>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">Name</dt><dd class="font-semibold">{{ $beneficiary->name }}</dd></div>
                    <div><dt class="text-gray-500">Age/Sex</dt><dd class="font-semibold">{{ $beneficiary->age }} / {{ $beneficiary->sex }}</dd></div>
                    <div><dt class="text-gray-500">Contact</dt><dd class="font-semibold">{{ $beneficiary->contact ?? 'N/A' }}</dd></div>
                    <div><dt class="text-gray-500">Barangay</dt><dd class="font-semibold">{{ $beneficiary->barangay->name ?? 'N/A' }}</dd></div>
                    <div><dt class="text-gray-500">Assistance Type</dt><dd class="font-semibold">{{ $beneficiary->assistanceType->name ?? 'N/A' }}</dd></div>
                    <div><dt class="text-gray-500">Status</dt><dd class="font-semibold">{{ $beneficiary->status }}</dd></div>
                    <div><dt class="text-gray-500">Address</dt><dd class="font-semibold">{{ $beneficiary->address ?? 'N/A' }}</dd></div>
                    <div><dt class="text-gray-500">Coordinates</dt><dd class="font-semibold">{{ $beneficiary->latitude }}, {{ $beneficiary->longitude }}</dd></div>
                </dl>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Case Details</h3>
                <p class="text-sm text-gray-700">{{ $beneficiary->case_info ?? 'No narrative provided.' }}</p>
                <div class="mt-4">
                    <h4 class="font-semibold mb-2">Attachments</h4>
                    @forelse($beneficiary->attachments as $attachment)
                        <a href="{{ Storage::disk('public')->url($attachment->path) }}" class="text-blue-600 block">{{ $attachment->name }}</a>
                    @empty
                        <p class="text-sm text-gray-500">No attachments.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
