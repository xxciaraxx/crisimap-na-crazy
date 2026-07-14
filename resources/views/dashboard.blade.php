<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Decision Support Dashboard</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Total Beneficiaries</p><p class="mt-2 text-3xl font-semibold">{{ number_format($totalBeneficiaries) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">This Month</p><p class="mt-2 text-3xl font-semibold">{{ number_format($thisMonth) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Released</p><p class="mt-2 text-3xl font-semibold">{{ number_format($released) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Approved</p><p class="mt-2 text-3xl font-semibold">{{ number_format($approved) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Average Amount</p><p class="mt-2 text-3xl font-semibold">PHP {{ number_format($averageAmount, 2) }}</p></div>
                <div class="rounded-xl bg-white p-5 shadow"><p class="text-sm text-gray-500">Release Rate</p><p class="mt-2 text-3xl font-semibold">{{ $releaseRate }}%</p></div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white shadow rounded-xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-lg">Monthly Trends</h3>
                            <p class="text-xs text-gray-500">{{ $forecast['label'] }}</p>
                        </div>
                        <div class="space-y-3">
                            @forelse($growthTrend as $trend)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>{{ $trend['month'] }}</span>
                                        <span>{{ $trend['total'] }} @if(!is_null($trend['growth']))({{ $trend['growth'] }}%)@endif</span>
                                    </div>
                                    <div class="h-3 rounded bg-gray-100 overflow-hidden">
                                        <div class="h-3 bg-blue-600 rounded" style="width: {{ min(100, max(5, $trend['total'] * 5)) }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No monthly trend data yet.</p>
                            @endforelse
                        </div>
                        <div class="mt-4 rounded-lg border border-dashed border-blue-300 bg-blue-50 p-4">
                            <p class="text-sm text-blue-900">Estimated next month demand: <strong>{{ number_format($forecast['next_month_estimate'], 1) }}</strong> applications.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white shadow rounded-xl p-5">
                            <h3 class="font-semibold text-lg mb-4">Beneficiaries by Barangay</h3>
                            <div class="space-y-3">
                                @forelse($byBarangay as $row)
                                    <div class="flex items-center justify-between text-sm">
                                        <span>{{ $row->name ?? 'Unassigned' }}</span>
                                        <span class="font-semibold">{{ $row->total }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No barangay breakdown yet.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white shadow rounded-xl p-5">
                            <h3 class="font-semibold text-lg mb-4">Assistance by Category</h3>
                            <div class="space-y-3">
                                @forelse($byType as $row)
                                    <div class="flex items-center justify-between text-sm">
                                        <span>{{ $row->name ?? 'Unassigned' }}</span>
                                        <span class="font-semibold">{{ $row->total }}</span>
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">No assistance breakdown yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow rounded-xl p-5">
                        <h3 class="font-semibold text-lg mb-4">Automated Insights</h3>
                        <ul class="space-y-3 text-sm text-gray-700">
                            @foreach($insights as $insight)
                                <li class="rounded-lg bg-gray-50 p-3">{{ $insight }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="bg-white shadow rounded-xl p-5">
                        <h3 class="font-semibold text-lg mb-4">Low Coverage Barangays</h3>
                        <div class="space-y-2 text-sm">
                            @forelse($lowCoverageBarangays as $row)
                                <div class="flex items-center justify-between">
                                    <span>{{ $row->name ?? 'Unassigned' }}</span>
                                    <span class="font-semibold">{{ $row->total }}</span>
                                </div>
                            @empty
                                <p class="text-gray-500">No coverage gaps identified yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded-xl p-5">
                    <h3 class="font-semibold text-lg mb-4">Recurring Beneficiaries</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Name</th>
                                    <th class="px-3 py-2 text-left">Applications</th>
                                    <th class="px-3 py-2 text-left">Last Assistance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($frequentBeneficiaries as $row)
                                    <tr class="border-t">
                                        <td class="px-3 py-2">{{ $row->name }}</td>
                                        <td class="px-3 py-2">{{ $row->applications }}</td>
                                        <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($row->last_assistance_date)->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">No recurring beneficiaries yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow rounded-xl p-5">
                    <h3 class="font-semibold text-lg mb-4">Recent Beneficiaries</h3>
                    <div class="space-y-3">
                        @forelse($recentBeneficiaries as $beneficiary)
                            <div class="rounded-lg border p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold">{{ $beneficiary->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $beneficiary->barangay->name ?? 'Unassigned' }} - {{ $beneficiary->assistanceType->name ?? 'Unassigned' }}</p>
                                    </div>
                                    <a class="text-sm text-blue-600" href="{{ route('beneficiaries.show', $beneficiary) }}">Open</a>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">{{ $beneficiary->date_of_assistance?->format('M d, Y') ?? $beneficiary->created_at->format('M d, Y') }} - {{ $beneficiary->status }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No recent beneficiaries yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
