<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Beneficiary Records</h2>
            <a href="{{ route('beneficiaries.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Add Beneficiary</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg p-4 mb-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <input name="search" value="{{ request('search') }}" placeholder="Search name" class="border rounded px-3 py-2" />
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
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}" class="border rounded px-3 py-2" />
                    <input type="date" name="to" value="{{ request('to') }}" class="border rounded px-3 py-2" />
                    <button class="bg-green-600 text-white rounded px-4 py-2">Filter</button>
                </form>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barangay</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assistance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($beneficiaries as $beneficiary)
                            <tr>
                                <td class="px-4 py-3">{{ $beneficiary->name }}</td>
                                <td class="px-4 py-3">{{ $beneficiary->barangay->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $beneficiary->assistanceType->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $beneficiary->date_of_assistance?->format('M d, Y') }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 rounded text-xs {{ $beneficiary->status === 'Completed' ? 'bg-green-100 text-green-700' : ($beneficiary->status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">{{ $beneficiary->status }}</span></td>
                                <td class="px-4 py-3 space-x-2">
                                    <a href="{{ route('beneficiaries.show', $beneficiary) }}" class="text-blue-600">View</a>
                                    <a href="{{ route('beneficiaries.edit', $beneficiary) }}" class="text-green-600">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No beneficiaries found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $beneficiaries->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
