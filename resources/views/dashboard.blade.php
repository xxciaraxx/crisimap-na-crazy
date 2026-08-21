<x-app-layout>
    <div class="dashboard-page">
        <div class="dashboard-container">
            <section class="dashboard-intro" aria-labelledby="dashboard-heading">
                <div>
                    <p class="dashboard-eyebrow">AICS program overview</p>
                    <h1 id="dashboard-heading">Decision support dashboard</h1>
                    <p>Monitor assistance activity, coverage, and release progress in one place.</p>
                </div>
                <div class="dashboard-period">Updated {{ now()->format('M d, Y') }}</div>
            </section>

            <section class="dashboard-metrics" aria-label="Program summary">
                <article class="dashboard-metric dashboard-metric-primary"><p>Total beneficiaries</p><strong>{{ number_format($totalBeneficiaries) }}</strong><span>Active assistance records</span></article>
                <article class="dashboard-metric"><p>This month</p><strong>{{ number_format($thisMonth) }}</strong><span>New assistance records</span></article>
                <article class="dashboard-metric"><p>Released</p><strong>{{ number_format($released) }}</strong><span>Completed assistance</span></article>
                <article class="dashboard-metric"><p>Approved</p><strong>{{ number_format($approved) }}</strong><span>Awaiting release</span></article>
                <article class="dashboard-metric dashboard-metric-amount"><p>Average assistance</p><strong>&#8369;{{ number_format($averageAmount, 2) }}</strong><span>Per active record</span></article>
                <article class="dashboard-metric"><p>Release rate</p><strong>{{ $releaseRate }}%</strong><span>Of active records</span></article>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white shadow rounded-xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-lg">Monthly Trends</h3>
                            <p class="text-xs text-gray-500">{{ $forecast['label'] }}</p>
                        </div>
                        @if(count($growthTrend))<div class="h-72"><canvas id="monthly-trends-chart"></canvas></div>@else<p class="text-sm text-gray-500">No monthly trend data yet.</p>@endif
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
@if(count($growthTrend))
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('monthly-trends-chart'), {
            type: 'line',
            data: { labels: @json(collect($growthTrend)->pluck('month')), datasets: [{ label: 'Applications', data: @json(collect($growthTrend)->pluck('total')), borderColor: '#1565c0', backgroundColor: 'rgba(21, 101, 192, .14)', fill: true, tension: .28, pointRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { tooltip: { callbacks: { afterLabel: context => `Forecast: {{ $forecast['next_month_estimate'] }} next month` } } } }
        });
    </script>
@endif
