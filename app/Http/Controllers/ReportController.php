<?php

namespace App\Http\Controllers;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\CarbonImmutable;
use App\Support\DateRangeResolver;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $payload = $this->buildReportPayload($request);

        return view('reports.index', array_merge($payload, [
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::included()->orderBy('name')->get(),
            'periodOptions' => $this->periodOptions(),
        ]));
    }

    public function pdf(Request $request)
    {
        $payload = $this->buildReportPayload($request);
        $payload['beneficiaries'] = $payload['records'];
        $pdf = Pdf::loadView('reports.pdf', $payload);

        return $pdf->download('crisimap-report.pdf');
    }

    public function excel(Request $request)
    {
        $payload = $this->buildReportPayload($request);
        $records = $payload['records'];

        return Excel::download(new class($records) implements FromCollection, WithHeadings {
            public function __construct(protected Collection $records) {}

            public function collection(): Collection
            {
                return $this->records->map(function ($beneficiary): array {
                    $clientName = $beneficiary->intake?->client_name
                        ?: $beneficiary->caseRecord?->client_name
                        ?: $beneficiary->name;
                    $beneficiaryName = $beneficiary->intake?->beneficiary_name ?: $beneficiary->name;
                    $beneficiaryLabel = mb_strtolower(trim((string) $clientName)) === mb_strtolower(trim((string) $beneficiaryName))
                        ? 'Self'
                        : $beneficiaryName;

                    return [
                        $beneficiary->reference_code ?? 'N/A',
                        $clientName,
                        $beneficiaryLabel,
                        $beneficiary->barangay->name ?? 'N/A',
                        $beneficiary->assistanceType->name ?? 'N/A',
                        $beneficiary->date_of_assistance?->format('Y-m-d') ?? '',
                        (float) ($beneficiary->amount ?? 0),
                        $beneficiary->status,
                    ];
                });
            }

            public function headings(): array
            {
                return [
                    'Reference Code',
                    'Client Name',
                    'Beneficiary Name',
                    'Barangay',
                    'Assistance Type',
                    'Applied',
                    'Amount',
                    'Status',
                ];
            }
        }, 'crisimap-report.xlsx');
    }

    private function buildReportPayload(Request $request): array
    {
        $query = $this->filteredQuery($request)
            ->with(['barangay', 'assistanceType', 'encoder', 'overrideUser', 'intake', 'caseRecord'])
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at');

        $records = (clone $query)->get();
        $beneficiaries = (clone $query)->paginate(10)->withQueryString();
        $summary = $this->summaryFor($records);

        return [
            'reportType' => $request->input('report_type', 'summary'),
            'records' => $records,
            'beneficiaries' => $beneficiaries,
            'summary' => $summary,
            'periodLabel' => $this->periodLabel($request),
        ];
    }

    private function periodLabel(Request $request): string
    {
        $period = trim((string) $request->input('period', ''));
        if ($period === '') {
            $today = now(config('app.timezone', 'Asia/Manila'));
            if ($request->input('report_type') === 'quarterly') {
                return 'Current quarter ('.$today->year.' Q'.$today->quarter.')';
            }
            if ($request->input('report_type') === 'annual') {
                return 'Current year ('.$today->year.')';
            }
            return 'All periods';
        }
        if (preg_match('/^(\d{4})-Q([1-4])$/', $period, $matches)) {
            return $matches[1].' Q'.$matches[2];
        }
        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $period)) {
            return CarbonImmutable::createFromFormat('!Y-m-d', $period.'-01')->format('F Y');
        }
        if (preg_match('/^\d{4}$/', $period)) {
            return $period;
        }

        return ucfirst(str_replace('_', ' ', (string) $request->input('report_type', 'summary')));
    }

    private function periodOptions(): array
    {
        $today = now(config('app.timezone', 'Asia/Manila'));
        $firstRecordDate = Beneficiary::query()
            ->whereNotNull('date_of_assistance')
            ->min('date_of_assistance');
        $firstYear = $firstRecordDate
            ? CarbonImmutable::parse($firstRecordDate, $today->timezone)->year
            : $today->year;
        $lastYear = max($today->year, $firstYear);
        $years = [];

        for ($year = $lastYear; $year >= $firstYear; $year--) {
            $years[] = [
                'year' => $year,
                'quarters' => collect(range(1, 4))->map(fn (int $quarter): array => [
                    'value' => "{$year}-Q{$quarter}",
                    'label' => "{$year} Q{$quarter}",
                ])->all(),
                'months' => collect(range(1, 12))->map(fn (int $month): array => [
                    'value' => sprintf('%d-%02d', $year, $month),
                    'label' => CarbonImmutable::create($year, $month, 1)->format('F Y'),
                ])->all(),
            ];
        }

        return [
            'current_month' => [
                'value' => $today->format('Y-m'),
                'label' => 'Current month ('.$today->format('F Y').')',
            ],
            'current_quarter' => [
                'value' => $today->format('Y').'-Q'.$today->quarter,
                'label' => 'Current quarter ('.$today->format('Y').' Q'.$today->quarter.')',
            ],
            'years' => $years,
        ];
    }

    private function filteredQuery(Request $request)
    {
        $query = Beneficiary::query()
            ->active()
            ->withIncludedAssistanceType()
            ->where('status', 'Released');

        $hasExplicitDateRange = $request->filled('from') || $request->filled('to');
        if (! $hasExplicitDateRange) {
            $this->applyDatePreset($query, $request->input('report_type', 'summary'));
        }

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->integer('assistance_type_id'));
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('reference_code', 'like', '%'.$search.'%')
                    ->orWhere('contact', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%');
            });
        }
        [$from, $to] = $request->filled('from') || $request->filled('to')
            ? [$request->date('from'), $request->date('to')]
            : (DateRangeResolver::resolve($request->input('period')) ?? [null, null]);
        if ($from) {
            $query->whereDate('date_of_assistance', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date_of_assistance', '<=', $to);
        }

        return $query;
    }

    private function applyDatePreset($query, string $reportType): void
    {
        $today = now();

        if ($reportType === 'daily') {
            $query->whereDate('date_of_assistance', $today->toDateString());
        } elseif ($reportType === 'weekly') {
            $query->whereBetween('date_of_assistance', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()]);
        } elseif ($reportType === 'monthly') {
            $query->whereBetween('date_of_assistance', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()]);
        } elseif ($reportType === 'quarterly') {
            $query->whereBetween('date_of_assistance', [$today->copy()->startOfQuarter(), $today->copy()->endOfQuarter()]);
        } elseif ($reportType === 'annual') {
            $query->whereBetween('date_of_assistance', [$today->copy()->startOfYear(), $today->copy()->endOfYear()]);
        }
    }

    private function summaryFor(Collection $records): array
    {
        $totalRecords = $records->count();
        $byBarangay = $records->groupBy(fn ($item) => $item->barangay?->name ?? 'Unassigned')->map->count()->sortDesc();
        $barangayAnalytics = $byBarangay
            ->map(function (int $count, string $name) use ($records, $totalRecords): array {
                $group = $records->filter(fn ($item) => ($item->barangay?->name ?? 'Unassigned') === $name);

                return [
                    'name' => $name,
                    'count' => $count,
                    'amount' => (float) $group->sum('amount'),
                    'share' => $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0,
                ];
            })
            ->values();
        $byType = $records->groupBy(fn ($item) => $item->assistanceType?->name ?? 'Unassigned')->map->count()->sortDesc();
        $assistanceAnalytics = $records
            ->groupBy(fn ($item) => $item->assistanceType?->name ?? 'Unassigned')
            ->map(function (Collection $group, string $name) use ($totalRecords): array {
                $count = $group->count();

                return [
                    'name' => $name,
                    'count' => $count,
                    'amount' => (float) $group->sum('amount'),
                    'share' => $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0,
                    'color' => $group->first()->assistanceType?->color,
                ];
            })
            ->sortByDesc('count')
            ->values();
        $byMonth = $records
            ->filter(fn ($item) => $item->date_of_assistance)
            ->groupBy(fn ($item) => $item->date_of_assistance->format('Y-m'))
            ->map->count()
            ->sortKeys();

        $averageAmount = $records->count() > 0 ? round((float) $records->sum('amount') / $records->count(), 2) : 0;
        $topAssistance = $assistanceAnalytics->first();
        $topBarangay = $barangayAnalytics->first();
        $interpretation = $totalRecords === 0
            ? ['No released beneficiary records match the selected filters.']
            : [
                'The report contains '.number_format($totalRecords).' released beneficiary '.($totalRecords === 1 ? 'record' : 'records').' for '.$this->periodLabel(request()).'.',
                $topBarangay
                    ? $topBarangay['name'].' has the highest beneficiary count with '.number_format($topBarangay['count']).' '.($topBarangay['count'] === 1 ? 'beneficiary' : 'beneficiaries').'.'
                    : 'No barangay distribution is available for this selection.',
                $topAssistance
                    ? $topAssistance['name'].' is the most availed assistance, representing '.number_format($topAssistance['share'], 1).'% of records.'
                    : 'No assistance distribution is available for this selection.',
                'The total assistance amount is PHP '.number_format((float) $records->sum('amount'), 2).', with an average of PHP '.number_format($averageAmount, 2).' per beneficiary.',
            ];
        return [
            'total' => $records->count(),
            'total_amount' => (float) $records->sum('amount'),
            'average_amount' => $averageAmount,
            'assistance_analytics' => $assistanceAnalytics,
            'barangay_analytics' => $barangayAnalytics,
            'approval_rate' => $records->count() > 0 ? round(($records->where('status', 'Approved')->count() / $records->count()) * 100, 1) : 0,
            'release_rate' => $records->count() > 0 ? round(($records->whereIn('status', ['Released', 'Completed'])->count() / $records->count()) * 100, 1) : 0,
            'most_requested_type' => $byType->keys()->first(),
            'high_demand_barangay' => $byBarangay->keys()->first(),
            'low_coverage_barangays' => $byBarangay->sort()->take(3)->keys()->values(),
            'monthly_trends' => $byMonth,
            'interpretation' => $interpretation,

            'frequent_beneficiaries' => $records
                ->groupBy('history_key')
                ->filter(fn ($group) => $group->count() > 1)
                ->map(fn ($group) => [
                    'name' => $group->first()->name,
                    'applications' => $group->count(),
                    'last_assistance_date' => $group->first()->date_of_assistance?->toDateString(),
                ])
                ->values(),
        ];
    }
}
