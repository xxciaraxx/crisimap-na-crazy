<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports & Exports</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Records</p><p class="mt-2 text-3xl font-semibold">{{ number_format($summary['total']) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Total Amount</p><p class="mt-2 text-3xl font-semibold">PHP {{ number_format($summary['total_amount'], 2) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Average Amount</p><p class="mt-2 text-3xl font-semibold">PHP {{ number_format($summary['average_amount'], 2) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Eligibility Issues</p><p class="mt-2 text-3xl font-semibold">{{ number_format($summary['eligibility_issues']) }}</p></div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                    <select name="report_type" class="border rounded px-3 py-2">
                        @foreach([
                            'summary' => 'Summary',
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'annual' => 'Annual',
                            'barangay' => 'Barangay',
                            'beneficiary_history' => 'Beneficiary History',
                            'assistance_trends' => 'Assistance Trends',
                            'gis_analytics_summary' => 'GIS Analytics Summary',
                            'eligibility' => 'Eligibility',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(request('report_type', 'summary') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                    <select name="status" class="border rounded px-3 py-2" aria-label="Report status">
                        <option value="Released" selected>Released assistance only</option>
                    </select>
                    <select name="eligibility_status" class="border rounded px-3 py-2">
                        <option value="">All Eligibility</option>
                        <option value="Eligible" @selected(request('eligibility_status') == 'Eligible')>Eligible</option>
                        <option value="Ineligible" @selected(request('eligibility_status') == 'Ineligible')>Ineligible</option>
                        <option value="Overridden" @selected(request('eligibility_status') == 'Overridden')>Overridden</option>
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}" class="border rounded px-3 py-2" />
                    <input type="date" name="to" value="{{ request('to') }}" class="border rounded px-3 py-2" />
                    <select name="period" class="border rounded px-3 py-2"><option value="">All periods</option>@for($year = now()->year + 3; $year >= now()->year - 3; $year--)<option value="{{ $year }}" @selected(request('period') == $year)>{{ $year }}</option>@foreach(range(1, 4) as $quarter)<option value="{{ $year }}-Q{{ $quarter }}" @selected(request('period') === $year.'-Q'.$quarter)>{{ $year }} Q{{ $quarter }}</option>@endforeach@endfor</select>
                    <div class="space-x-2 xl:col-span-6">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
                        <a href="{{ route('reports.pdf', request()->query()) }}" class="bg-red-600 text-white px-4 py-2 rounded inline-block">PDF</a>
                        <a href="{{ route('reports.excel', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded inline-block">Excel</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex flex-wrap gap-3 text-sm">
                    <span class="rounded-full bg-gray-100 px-3 py-1">High demand: {{ $summary['high_demand_barangay'] ?? 'N/A' }}</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1">Top request: {{ $summary['most_requested_type'] ?? 'N/A' }}</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1">Release rate: {{ $summary['release_rate'] }}%</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1">Approval rate: {{ $summary['approval_rate'] }}%</span>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Barangay</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Eligibility</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($beneficiaries as $beneficiary)
                                <tr>
                                    <td class="px-4 py-3 text-sm">{{ $beneficiary->reference_code ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $beneficiary->name }}</td>
                                    <td class="px-4 py-3">{{ $beneficiary->barangay->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $beneficiary->assistanceType->name ?? '—' }}</td>
                                    <td class="px-4 py-3">PHP {{ number_format((float) ($beneficiary->amount ?? 0), 2) }}</td>
                                    <td class="px-4 py-3">{{ $beneficiary->status }}</td>
                                    <td class="px-4 py-3">{{ $beneficiary->eligibility_status ?? 'Eligible' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No records for the selected criteria.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $beneficiaries->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
