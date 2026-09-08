<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'super_admin') {
            return $this->superAdminDashboard();
        }

        $pendingApplications = auth()->user()->role === 'admin'
            ? Beneficiary::query()
                ->active()
                ->where('status', 'Pending')
                ->with(['barangay', 'assistanceType', 'encoder'])
                ->latest('created_at')
                ->limit(10)
                ->get()
            : collect();

        $recentBeneficiaries = Beneficiary::query()
            ->active()
            ->with(['barangay', 'assistanceType', 'encoder'])
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $baseQuery = Beneficiary::query()->active();
        // Count every active assistance record so returning applicants are included.
        $totalBeneficiaries = (clone $baseQuery)->count();
        $recordCountsByPerson = (clone $baseQuery)->pluck('history_key')->countBy();
        $singleRecordBeneficiaries = $recordCountsByPerson->filter(fn (int $count) => $count === 1)->count();
        $returningBeneficiaries = $recordCountsByPerson->filter(fn (int $count) => $count > 1)->count();
        $returningRecords = $recordCountsByPerson->filter(fn (int $count) => $count > 1)->sum(fn (int $count) => $count - 1);
        $thisMonth = (clone $baseQuery)
            ->whereBetween('date_of_assistance', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $released = (clone $baseQuery)->whereIn('status', ['Released', 'Completed'])->count();
        $approved = (clone $baseQuery)->where('status', 'Approved')->count();
        $pending = (clone $baseQuery)->where('status', 'Pending')->count();
        $totalAmount = (float) (clone $baseQuery)->sum('amount');
        $averageAmount = $totalBeneficiaries > 0 ? round($totalAmount / $totalBeneficiaries, 2) : 0.0;

        $byBarangay = Beneficiary::query()
            ->active()
            ->selectRaw('barangays.name as name, COUNT(*) as total, COALESCE(SUM(beneficiaries.amount), 0) as amount_total')
            ->leftJoin('barangays', 'barangays.id', '=', 'beneficiaries.barangay_id')
            ->groupBy('barangays.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $byType = Beneficiary::query()
            ->active()
            ->selectRaw('assistance_types.name as name, COUNT(*) as total, COALESCE(SUM(beneficiaries.amount), 0) as amount_total')
            ->leftJoin('assistance_types', 'assistance_types.id', '=', 'beneficiaries.assistance_type_id')
            ->groupBy('assistance_types.name')
            ->orderByDesc('total')
            ->get();

        $trendRecords = Beneficiary::query()
            ->active()
            ->select(['date_of_assistance', 'amount'])
            ->whereNotNull('date_of_assistance')
            ->where('date_of_assistance', '>=', now()->subYear())
            ->get();

        $monthlyTrends = $trendRecords
            ->groupBy(fn ($item) => $item->date_of_assistance->format('Y-m'))
            ->map(fn ($group, $monthKey) => (object) [
                'month_key' => $monthKey,
                'total' => $group->count(),
                'amount_total' => $group->sum('amount'),
            ])
            ->sortBy('month_key')
            ->values();

        $yearlyTrends = $trendRecords
            ->groupBy(fn ($item) => $item->date_of_assistance->format('Y'))
            ->map(fn ($group, $yearKey) => (object) [
                'year_key' => $yearKey,
                'total' => $group->count(),
                'amount_total' => $group->sum('amount'),
            ])
            ->sortBy('year_key')
            ->values();

        $approvalRate = $totalBeneficiaries > 0 ? round(($approved / $totalBeneficiaries) * 100, 1) : 0;
        $releaseRate = $totalBeneficiaries > 0 ? round(($released / $totalBeneficiaries) * 100, 1) : 0;

        $frequentBeneficiaries = Beneficiary::query()
            ->active()
            ->selectRaw('name, birth_date, contact, address, history_key, COUNT(*) as applications, COALESCE(SUM(amount), 0) as total_amount, MAX(date_of_assistance) as last_assistance_date')
            ->groupBy('history_key', 'name', 'birth_date', 'contact', 'address')
            ->having('applications', '>', 1)
            ->orderByDesc('applications')
            ->limit(8)
            ->get();

        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', date_of_assistance) AS INTEGER)"
            : 'MONTH(date_of_assistance)';

        $seasonalDemand = Beneficiary::query()
            ->active()
            ->selectRaw($monthExpression.' as month_number, COUNT(*) as total')
            ->whereNotNull('date_of_assistance')
            ->groupBy('month_number')
            ->orderBy('month_number')
            ->get()
            ->mapWithKeys(fn ($item) => [(int) $item->month_number => (int) $item->total]);

        $topBarangay = $byBarangay->first();
        $lowCoverageBarangays = $byBarangay->sortBy('total')->take(3)->values();
        $growthTrend = $this->calculateGrowthTrend($monthlyTrends);
        $forecast = $this->buildForecast($monthlyTrends);

        $insights = $this->buildInsights([
            'totalBeneficiaries' => $totalBeneficiaries,
            'singleRecordBeneficiaries' => $singleRecordBeneficiaries,
            'returningBeneficiaries' => $returningBeneficiaries,
            'returningRecords' => $returningRecords,
            'totalAmount' => $totalAmount,
            'averageAmount' => $averageAmount,
            'approvalRate' => $approvalRate,
            'releaseRate' => $releaseRate,
            'topBarangay' => $topBarangay?->name,
            'topBarangayTotal' => $topBarangay?->total,
            'monthlyTrends' => $monthlyTrends,
            'frequentBeneficiaries' => $frequentBeneficiaries,
        ]);

        return view('dashboard', compact(
            'recentBeneficiaries',
            'totalBeneficiaries',
            'singleRecordBeneficiaries',
            'returningBeneficiaries',
            'returningRecords',
            'thisMonth',
            'released',
            'approved',
            'pending',
            'totalAmount',
            'averageAmount',
            'approvalRate',
            'releaseRate',
            'byBarangay',
            'byType',
            'monthlyTrends',
            'yearlyTrends',
            'frequentBeneficiaries',
            'seasonalDemand',
            'lowCoverageBarangays',
            'growthTrend',
            'forecast',
            'insights',
            'pendingApplications'
        ));
    }

    private function superAdminDashboard()
    {
        $pendingApprovals = User::query()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        $roleRoster = [
            'admin' => User::query()->where('role', 'admin')->where('status', 'approved')->first(['id', 'name', 'email']),
            'focal_person' => User::query()->where('role', 'focal_person')->where('status', 'approved')->first(['id', 'name', 'email']),
            'social_officer_count' => User::query()->where('role', 'social_officer')->where('status', 'approved')->count(),
        ];

        $failedLoginsToday = ActivityLog::query()
            ->where('action', 'login_failed')
            ->whereDate('created_at', today())
            ->count();

        $incompleteOnboarding = User::query()
            ->where('status', 'approved')
            ->where(fn ($query) => $query->where('must_complete_setup', true)->orWhereNull('email_verified_at'))
            ->count();

        $recentActivity = ActivityLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(8)
            ->get(['id', 'user_id', 'action', 'description', 'created_at']);

        $referenceData = [
            'barangays' => Barangay::count(),
            'assistance_types' => AssistanceType::count(),
        ];

        $geocodingHealth = [
            'unresolved' => Beneficiary::query()->whereNull('geocode_precision')->count(),
            'failed' => Beneficiary::query()->where('geocode_precision', 'failed')->count(),
        ];

        $recordCountsByPerson = Beneficiary::query()
            ->active()
            ->pluck('history_key')
            ->countBy();
        $beneficiarySummary = [
            'total_records' => $recordCountsByPerson->sum(),
            'single_record_beneficiaries' => $recordCountsByPerson->filter(fn (int $count) => $count === 1)->count(),
            'returning_beneficiaries' => $recordCountsByPerson->filter(fn (int $count) => $count > 1)->count(),
            'returning_records' => $recordCountsByPerson->filter(fn (int $count) => $count > 1)->sum(fn (int $count) => $count - 1),
        ];

        return view('dashboard.super-admin', compact(
            'pendingApprovals',
            'roleRoster',
            'failedLoginsToday',
            'incompleteOnboarding',
            'recentActivity',
            'referenceData',
            'geocodingHealth',
            'beneficiarySummary'
        ));
    }

    private function calculateGrowthTrend($monthlyTrends): array
    {
        $previous = null;

        return $monthlyTrends->map(function ($item) use (&$previous) {
            $current = (int) $item->total;
            $growth = $previous && $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : null;
            $previous = $current;

            return [
                'month' => $item->month_key,
                'total' => $current,
                'growth' => $growth,
            ];
        })->values()->all();
    }

    private function buildForecast($monthlyTrends): array
    {
        $recent = $monthlyTrends->take(-3)->pluck('total')->map(fn ($value) => (int) $value);
        $estimate = $recent->count() > 0 ? round($recent->avg(), 1) : 0;

        return [
            'next_month_estimate' => $estimate,
            'label' => 'Estimate based on the last '.max(1, $recent->count()).' month(s) of history.',
        ];
    }

    private function buildInsights(array $metrics): array
    {
        $insights = [];

        $insights[] = $metrics['topBarangay']
            ? $metrics['topBarangay'].' leads assistance demand with '.number_format((int) $metrics['topBarangayTotal']).' record(s).'
            : 'No barangay trend is available yet.';

        $insights[] = 'Average assistance amount is '.number_format($metrics['averageAmount'], 2).'.';
        $insights[] = 'Approval rate stands at '.$metrics['approvalRate'].'% and release rate at '.$metrics['releaseRate'].'%.';

        if ($metrics['frequentBeneficiaries']->isNotEmpty()) {
            $insights[] = 'Recurring beneficiaries are present, so the 90-day policy remains important for fairness.';
        }

        if ($metrics['monthlyTrends']->isNotEmpty()) {
            $latest = $metrics['monthlyTrends']->last();
            $insights[] = 'Most recent month recorded '.number_format((int) $latest->total).' application(s).';
        }

        return $insights;
    }
}
