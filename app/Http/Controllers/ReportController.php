<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelExcel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Beneficiary::query()->with(['barangay', 'assistanceType']);

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->barangay_id);
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->assistance_type_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('date_of_assistance', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date_of_assistance', '<=', $request->to);
        }

        $beneficiaries = $query->latest()->get();
        $barangays = \App\Models\Barangay::orderBy('name')->get();
        $assistanceTypes = \App\Models\AssistanceType::orderBy('name')->get();

        return view('reports.index', compact('beneficiaries', 'barangays', 'assistanceTypes'));
    }

    public function pdf(Request $request)
    {
        $query = Beneficiary::query()->with(['barangay', 'assistanceType']);
        $this->applyFilters($query, $request);
        $beneficiaries = $query->latest()->get();

        $pdf = Pdf::loadView('reports.pdf', compact('beneficiaries'));

        return $pdf->download('crisimap-report.pdf');
    }

    public function excel(Request $request)
    {
        $query = Beneficiary::query()->with(['barangay', 'assistanceType']);
        $this->applyFilters($query, $request);

        return Excel::download(new class($query) implements FromQuery {
            public function __construct(protected $query) {}

            public function query()
            {
                return $this->query;
            }
        }, 'crisimap-report.xlsx');
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->barangay_id);
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->assistance_type_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('date_of_assistance', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('date_of_assistance', '<=', $request->to);
        }
    }
}
