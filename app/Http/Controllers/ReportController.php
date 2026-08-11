<?php

namespace App\Http\Controllers;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
        ]));
    }

    public function pdf(Request $request)
    {
        $payload = $this->buildReportPayload($request);
        $pdf = Pdf::loadView('reports.pdf', $payload);

        return $pdf->download('crisimap-report.pdf');
    }

    public function excel(Request $request)
    {
        $payload = $this->buildReportPayload($request);
        $records = $payload['records'];

        return Excel::download(new class($records) implements FromCollection, WithHeadings {
            public function __construct(protected Collection $records) {}

            public function collection()
            {
                return $this->records->map(fn ($beneficiary) => [
                    $beneficiary->reference_code ?? 'N/A',
                    $beneficiary->name,
                    $beneficiary->barangay->name ?? 'N/A',
                    $beneficiary->assistanceType->name ?? 'N/A',
                    $beneficiary->birth_date?->format('Y-m-d') ?? '',
                    $beneficiary->date_of_assistance?->format('Y-m-d') ?? '',
                    (float) ($beneficiary->amount ?? 0),
                    $beneficiary->status,
                    $beneficiary->eligibility_status ?? 'Eligible',
                ]);
            }

            public function headings(): array
            {
                return [
                    'Reference Code',
                    'Name',
                    'Barangay',
                    'Assistance Type',
                    'Birth Date',
                    'Date of Assistance',
                    'Amount',
                    'Status',
                    'Eligibility Status',
                ];
            }
        }, 'crisimap-report.xlsx');
    }

    private function buildReportPayload(Request $request): array
    {
        $query = $this->filteredQuery($request)
            ->with(['barangay', 'assistanceType', 'encoder', 'overrideUser'])
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at');

        $records = (clone $query)->get();
        $beneficiaries = (clone $query)->paginate(25)->withQueryString();
        $summary = $this->summaryFor($records);

        return [
            'reportType' => $request->input('report_type', 'summary'),
            'records' => $records,
            'beneficiaries' => $beneficiaries,
            'summary' => $summary,
        ];
    }

    private function filteredQuery(Request $request)
    {
        $query = Beneficiary::query()->active();

        if ($request->user()->role === 'social_officer') {
            $query->where('encoded_by', $request->user()->id);
        }

        $this->applyDatePreset($query, $request->input('report_type', 'summary'));

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->integer('assistance_type_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('eligibility_status')) {
            $query->where('eligibility_status', $request->string('eligibility_status'));
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
        } elseif ($reportType === 'annual') {
            $query->whereBetween('date_of_assistance', [$today->copy()->startOfYear(), $today->copy()->endOfYear()]);
        } elseif ($reportType === 'eligibility') {
            $query->whereNotNull('eligibility_status');
        }
    }

    private function summaryFor(Collection $records): array
    {
        $byBarangay = $records->groupBy(fn ($item) => $item->barangay?->name ?? 'Unassigned')->map->count()->sortDesc();
        $byType = $records->groupBy(fn ($item) => $item->assistanceType?->name ?? 'Unassigned')->map->count()->sortDesc();
        $byMonth = $records
            ->filter(fn ($item) => $item->date_of_assistance)
            ->groupBy(fn ($item) => $item->date_of_assistance->format('Y-m'))
            ->map->count()
            ->sortKeys();

        $averageAmount = $records->count() > 0 ? round((float) $records->sum('amount') / $records->count(), 2) : 0;
        $eligibilityIssues = $records->where('eligibility_status', 'Ineligible')->count();
        $overridden = $records->where('eligibility_status', 'Overridden')->count();

        return [
            'total' => $records->count(),
            'total_amount' => (float) $records->sum('amount'),
            'average_amount' => $averageAmount,
            'approval_rate' => $records->count() > 0 ? round(($records->where('status', 'Approved')->count() / $records->count()) * 100, 1) : 0,
            'release_rate' => $records->count() > 0 ? round(($records->whereIn('status', ['Released', 'Completed'])->count() / $records->count()) * 100, 1) : 0,
            'most_requested_type' => $byType->keys()->first(),
            'high_demand_barangay' => $byBarangay->keys()->first(),
            'low_coverage_barangays' => $byBarangay->sort()->take(3)->keys()->values(),
            'monthly_trends' => $byMonth,
            'eligibility_issues' => $eligibilityIssues,
            'overridden' => $overridden,
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
