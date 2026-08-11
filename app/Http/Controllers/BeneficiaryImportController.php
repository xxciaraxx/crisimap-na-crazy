<?php

namespace App\Http\Controllers;

use App\Imports\BeneficiaryPreviewImport;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BeneficiaryImportController extends Controller
{
    public function create()
    {
        $this->authorize('create', Beneficiary::class);
        return view('beneficiaries.import');
    }

    public function preview(Request $request)
    {
        $this->authorize('create', Beneficiary::class);
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']]);
        $import = new BeneficiaryPreviewImport;
        Excel::import($import, $request->file('file'));
        $rows = ($import->rows ?? collect())->take(500)->map(fn ($row) => [
            'name' => trim((string) ($row['name'] ?? '')),
            'age' => (int) ($row['age'] ?? 0),
            'sex' => $row['sex'] ?? '',
            'contact' => $row['contact'] ?? null,
            'address' => $row['address'] ?? null,
            'barangay' => $row['barangay'] ?? null,
            'assistance_type' => $row['assistance_type'] ?? null,
            'amount' => $row['amount'] ?? null,
            'date_of_assistance' => $row['date_of_assistance'] ?? null,
            'status' => $row['status'] ?? 'Pending',
        ])->filter(fn ($row) => $row['name'] !== '')->values();

        return view('beneficiaries.import-preview', compact('rows'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Beneficiary::class);
        $rows = collect($request->input('rows', []))->filter(fn ($row) => filled($row['name'] ?? null));
        abort_if($rows->isEmpty(), 422, 'No valid import rows were provided.');

        $created = 0;
        foreach ($rows as $row) {
            $barangayId = Barangay::firstOrCreate(['name' => trim((string) ($row['barangay'] ?? 'Unassigned'))])->id;
            $typeId = filled($row['assistance_type'] ?? null) ? AssistanceType::firstOrCreate(['name' => trim((string) $row['assistance_type'])])->id : null;
            Beneficiary::create([
                'name' => trim($row['name']), 'age' => max(1, (int) ($row['age'] ?? 1)), 'sex' => in_array($row['sex'] ?? '', ['Male', 'Female', 'Other'], true) ? $row['sex'] : 'Other',
                'contact' => $row['contact'] ?? null, 'address' => $row['address'] ?? null, 'barangay_id' => $barangayId, 'assistance_type_id' => $typeId,
                'amount' => is_numeric($row['amount'] ?? null) ? $row['amount'] : null, 'date_of_assistance' => $row['date_of_assistance'] ?: now()->toDateString(),
                'status' => in_array($row['status'] ?? '', ['Pending', 'Approved', 'Released', 'Completed'], true) ? $row['status'] : 'Pending', 'encoded_by' => $request->user()->id,
            ]);
            $created++;
        }
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'beneficiaries_imported', 'description' => "Imported {$created} beneficiary record(s).", 'subject_type' => Beneficiary::class]);
        return redirect()->route('beneficiaries.index')->with('success', "Imported {$created} beneficiary record(s).");
    }
}
