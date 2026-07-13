<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports & Exports</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow rounded-lg p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3">
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
                    <div class="space-x-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
                        <a href="{{ route('reports.pdf', request()->query()) }}" class="bg-red-600 text-white px-4 py-2 rounded inline-block">PDF</a>
                        <a href="{{ route('reports.excel', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded inline-block">Excel</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Barangay</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($beneficiaries as $beneficiary)
                            <tr>
                                <td class="px-4 py-3">{{ $beneficiary->name }}</td>
                                <td class="px-4 py-3">{{ $beneficiary->barangay->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $beneficiary->assistanceType->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $beneficiary->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-3 text-center text-gray-500">No records for the selected criteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
