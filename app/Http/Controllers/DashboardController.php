<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $beneficiaries = Beneficiary::with(['barangay', 'assistanceType', 'encoder'])->latest()->take(8)->get();
        $total = Beneficiary::count();
        $thisMonth = Beneficiary::whereMonth('date_of_assistance', Carbon::now()->month)->count();
        $released = Beneficiary::where('status', 'Released')->orWhere('status', 'Completed')->count();
        $topBarangays = Beneficiary::selectRaw('barangays.name as name, count(*) as total')
            ->leftJoin('barangays', 'barangays.id', '=', 'beneficiaries.barangay_id')
            ->groupBy('barangays.name')
            ->orderByDesc('total')
            ->take(5)
            ->get();
        $byType = Beneficiary::selectRaw('assistance_types.name as name, count(*) as total')
            ->leftJoin('assistance_types', 'assistance_types.id', '=', 'beneficiaries.assistance_type_id')
            ->groupBy('assistance_types.name')
            ->orderByDesc('total')
            ->get();
        $monthly = Beneficiary::selectRaw('MONTH(date_of_assistance) as month, COUNT(*) as total')
            ->whereNotNull('date_of_assistance')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard', compact('beneficiaries', 'total', 'thisMonth', 'released', 'topBarangays', 'byType', 'monthly'));
    }
}
