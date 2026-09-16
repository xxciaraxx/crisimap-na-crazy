<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reports & Exports</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php($exportQuery = request()->except('page'))
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="group relative overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-600 to-blue-800 p-5 text-white shadow-lg shadow-blue-900/10 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl">
                    <div class="absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/10"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-100">Released records</p>
                            <p class="mt-3 text-4xl font-bold tracking-tight">{{ number_format($summary['total']) }}</p>
                        </div>
                        <div class="rounded-xl bg-white/15 p-3 ring-1 ring-white/20">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m8-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-3a3 3 0 1 1 0-6m4 15v-2a4 4 0 0 0-3-3.87" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-600 to-teal-700 p-5 text-white shadow-lg shadow-emerald-900/10 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl">
                    <div class="absolute -bottom-10 -right-5 h-32 w-32 rounded-full bg-white/10"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-100">Total distributed</p>
                            <p class="mt-3 text-3xl font-bold tracking-tight">PHP {{ number_format($summary['total_amount'], 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-white/15 p-3 ring-1 ring-white/20">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m4-9.5c0-1.38-1.79-2.5-4-2.5S8 7.12 8 8.5 9.79 11 12 11s4 1.12 4 2.5-1.79 2.5-4 2.5-4-1.12-4-2.5M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-blue-50 to-white px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Filter reports</h3>
                            <p class="mt-1 text-sm text-slate-500">Choose a report view, narrow the records, or select a reporting period.</p>
                        </div>
                        @if(request()->hasAny(['report_type', 'search', 'barangay_id', 'assistance_type_id', 'from', 'to', 'period']))
                            <a href="{{ route('reports.index') }}" class="text-sm font-semibold text-slate-600 underline decoration-slate-300 underline-offset-4 hover:text-blue-700">Clear filters</a>
                        @endif
                    </div>
                </div>
                <form method="GET" class="space-y-5 p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label for="report_type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Report view</label>
                            <select id="report_type" name="report_type" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach([
                            'summary' => 'Summary',
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                            'annual' => 'Annual',
                            'barangay' => 'Barangay',
                            'beneficiary_history' => 'Beneficiary History',
                            'assistance_trends' => 'Assistance Trends',
                            'gis_analytics_summary' => 'GIS Analytics Summary',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(request('report_type', 'summary') === $value)>{{ $label }}</option>
                        @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="report_search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                            <input id="report_search" name="search" value="{{ request('search') }}" placeholder="Name, reference, contact..." class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label for="report_barangay" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Barangay</label>
                            <select id="report_barangay" name="barangay_id" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Barangays</option>
                        @foreach($barangays as $barangay)
                            <option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>
                        @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="report_assistance" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Assistance type</label>
                            <select id="report_assistance" name="assistance_type_id" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Assistance Types</option>
                        @foreach($assistanceTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('assistance_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                            </select>
                        </div>
                    </div>
                    <fieldset class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <legend class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Time period</legend>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label for="report_period" class="mb-1.5 block text-xs font-medium text-slate-600">Preset period</label>
                                <select id="report_period" name="period" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All periods</option>
                        <option value="{{ $periodOptions['current_month']['value'] }}" @selected(request('period') === $periodOptions['current_month']['value'])>{{ $periodOptions['current_month']['label'] }}</option>
                        <option value="{{ $periodOptions['current_quarter']['value'] }}" @selected(request('period') === $periodOptions['current_quarter']['value'])>{{ $periodOptions['current_quarter']['label'] }}</option>
                        @foreach($periodOptions['years'] as $periodYear)
                            <option value="{{ $periodYear['year'] }}" @selected((string) request('period') === (string) $periodYear['year'])>{{ $periodYear['year'] }}</option>
                            @foreach($periodYear['quarters'] as $quarter)
                                <option value="{{ $quarter['value'] }}" @selected(request('period') === $quarter['value'])>{{ $quarter['label'] }}</option>
                            @endforeach
                            @foreach($periodYear['months'] as $month)
                                <option value="{{ $month['value'] }}" @selected(request('period') === $month['value'])>{{ $month['label'] }}</option>
                            @endforeach
                        @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="report_from" class="mb-1.5 block text-xs font-medium text-slate-600">From date <span class="font-normal text-slate-400">(optional)</span></label>
                                <input id="report_from" type="date" name="from" value="{{ request('from') }}" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="report_to" class="mb-1.5 block text-xs font-medium text-slate-600">To date <span class="font-normal text-slate-400">(optional)</span></label>
                                <input id="report_to" type="date" name="to" value="{{ request('to') }}" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Custom dates take priority over the preset period.</p>
                    </fieldset>
                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                        <button class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Apply filters</button>
                        <a href="{{ route('reports.pdf', $exportQuery) }}" class="inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Download PDF</a>
                        <a href="{{ route('reports.excel', $exportQuery) }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Download Excel</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex flex-wrap gap-3 text-sm">
                    <span class="rounded-full bg-gray-100 px-3 py-1">High demand: {{ $summary['high_demand_barangay'] ?? 'N/A' }}</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1">Top request: {{ $summary['most_requested_type'] ?? 'N/A' }}</span>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl border border-blue-100 bg-blue-50/60 shadow-sm" aria-labelledby="report-interpretation-heading">
                <div class="border-b border-blue-100 bg-white/70 px-5 py-4">
                    <h3 id="report-interpretation-heading" class="font-semibold text-slate-900">Report interpretation</h3>
                    <p class="mt-1 text-sm text-slate-500">A plain-language summary of the filtered analytics.</p>
                </div>
                <ul class="grid gap-3 p-5 text-sm text-slate-700 md:grid-cols-2">
                    @foreach($summary['interpretation'] as $statement)
                        <li class="flex gap-3 rounded-xl bg-white p-4 shadow-sm">
                            <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700" aria-hidden="true">i</span>
                            <span>{{ $statement }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-2 gap-6" aria-label="Report analytics">
                <div class="bg-white shadow rounded-lg p-5">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h3 class="font-semibold text-lg text-slate-900">AICS Beneficiaries by Barangay</h3>
                            <p class="text-sm text-gray-500">Number of released beneficiaries in each barangay for the selected period</p>
                        </div>
                        <div class="text-right">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ number_format($summary['total']) }} beneficiaries</span>
                            <p class="mt-2 text-xs font-medium text-slate-500">{{ $periodLabel }}</p>
                        </div>
                    </div>
                    @if($summary['barangay_analytics']->isNotEmpty())
                        <div class="h-80"><canvas id="assistance-volume-chart" aria-label="Bar graph showing AICS beneficiaries by barangay"></canvas></div>
                    @else
                        <p class="text-sm text-gray-500">No beneficiary data for the selected criteria.</p>
                    @endif
                </div>
                <div class="bg-white shadow rounded-lg p-5">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h3 class="font-semibold text-lg">Assistance Distribution</h3>
                            <p class="text-sm text-gray-500">Percentage of records by assistance type</p>
                        </div>
                        <span class="text-xs text-gray-500">Based on record count</span>
                    </div>
                    @if($summary['assistance_analytics']->isNotEmpty())
                        <div class="h-72"><canvas id="assistance-distribution-chart" aria-label="Pie chart of assistance distribution"></canvas></div>
                    @else
                        <p class="text-sm text-gray-500">No assistance data for the selected criteria.</p>
                    @endif
                </div>
            </section>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-lg text-slate-900">Assistance analytics detail</h3>
                    <p class="mt-1 text-sm text-slate-500">Ranked by number of released beneficiaries in the selected period.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Assistance type</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Beneficiaries</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total amount</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['assistance_analytics'] as $rank => $analytics)
                                <tr>
                                    <td class="px-5 py-3 text-slate-500">#{{ $rank + 1 }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $analytics['name'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-900">{{ number_format($analytics['count']) }}</td>
                                    <td class="px-5 py-3 text-right">PHP {{ number_format($analytics['amount'], 2) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-blue-700">{{ number_format($analytics['share'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No assistance data for the selected criteria.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-lg text-slate-900">Barangay analytics detail</h3>
                    <p class="mt-1 text-sm text-slate-500">Ranked by number of released beneficiaries in the selected period.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Rank</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Barangay</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Beneficiaries</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total amount</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['barangay_analytics'] as $rank => $analytics)
                                <tr>
                                    <td class="px-5 py-3 text-slate-500">#{{ $rank + 1 }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $analytics['name'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-slate-900">{{ number_format($analytics['count']) }}</td>
                                    <td class="px-5 py-3 text-right">PHP {{ number_format($analytics['amount'], 2) }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-blue-700">{{ number_format($analytics['share'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No barangay data for the selected criteria.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">No records for the selected criteria.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $beneficiaries->links() }}</div>
            </div>
        </div>
    </div>
    @if($summary['assistance_analytics']->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                const analytics = @json($summary['assistance_analytics']->values());
                const barangays = @json($summary['barangay_analytics']->values());
                const labels = analytics.map(item => item.name);
                const counts = analytics.map(item => item.count);
                const fallbackColors = ['#1768aa', '#2f8f83', '#d28a16', '#8e5bb7', '#d05a67', '#4b78b8', '#5c9a63', '#c46b32'];
                const colors = analytics.map((item, index) => item.color || fallbackColors[index % fallbackColors.length]);
                const chartOptions = { responsive: true, maintainAspectRatio: false };
                const percentageLabels = {
                    id: 'percentageLabels',
                    afterDatasetsDraw(chart) {
                        const dataset = chart.data.datasets[0];
                        const total = dataset.data.reduce((sum, value) => sum + Number(value), 0);
                        const { ctx } = chart;

                        chart.getDatasetMeta(0).data.forEach((arc, index) => {
                            const value = Number(dataset.data[index]);
                            if (!value || !total) return;

                            const position = arc.tooltipPosition();
                            ctx.save();
                            ctx.fillStyle = '#ffffff';
                            ctx.font = '600 12px Arial';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.shadowColor = 'rgba(0, 0, 0, 0.35)';
                            ctx.shadowBlur = 3;
                            ctx.fillText(`${((value / total) * 100).toFixed(1)}%`, position.x, position.y);
                            ctx.restore();
                        });
                    },
                };

                new Chart(document.getElementById('assistance-volume-chart'), {
                    type: 'bar',
                    data: {
                        labels: barangays.map(item => item.name),
                        datasets: [{
                            label: 'AICS beneficiaries',
                            data: barangays.map(item => item.count),
                            backgroundColor: '#2563eb',
                            hoverBackgroundColor: '#1d4ed8',
                            borderRadius: 6,
                            barThickness: 22,
                        }],
                    },
                    options: {
                        ...chartOptions,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: context => ` ${context.parsed.x} beneficiaries` } },
                        },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 }, grid: { color: '#e2e8f0' } },
                            y: { grid: { display: false } },
                        },
                    },
                });
                new Chart(document.getElementById('assistance-distribution-chart'), {
                    type: 'pie',
                    data: { labels, datasets: [{ data: counts, backgroundColor: colors }] },
                    options: chartOptions,
                    plugins: [percentageLabels],
                });
            })();
        </script>
    @endif
</x-app-layout>
