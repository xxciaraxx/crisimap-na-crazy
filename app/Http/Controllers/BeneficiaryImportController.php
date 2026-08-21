<?php

namespace App\Http\Controllers;

use App\Imports\BeneficiaryPreviewImport;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Jobs\GeocodeBeneficiary;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

        $barangays = Barangay::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id]);
        $types = AssistanceType::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id]);
        $errors = [];
        foreach ($rows as $index => $row) {
            $barangay = mb_strtolower(trim((string) ($row['barangay'] ?? '')));
            $type = mb_strtolower(trim((string) ($row['assistance_type'] ?? '')));
            if ($barangay === '' || ! $barangays->has($barangay)) $errors["rows.$index.barangay"] = 'Choose an existing barangay; imports cannot create one from a typo.';
            if ($type !== '' && ! $types->has($type)) $errors["rows.$index.assistance_type"] = 'Choose an existing assistance type; imports cannot create one from a typo.';
        }
        if ($errors) throw ValidationException::withMessages($errors);

        $created = 0;
        foreach ($rows as $row) {
            $barangayId = $barangays->get(mb_strtolower(trim((string) $row['barangay'])));
            $typeId = filled($row['assistance_type'] ?? null) ? $types->get(mb_strtolower(trim((string) $row['assistance_type']))) : null;
            $beneficiary = Beneficiary::create([
                'name' => trim($row['name']), 'age' => max(1, (int) ($row['age'] ?? 1)), 'sex' => in_array($row['sex'] ?? '', ['Male', 'Female', 'Other'], true) ? $row['sex'] : 'Other',
                'contact' => $row['contact'] ?? null, 'address' => $row['address'] ?? null, 'barangay_id' => $barangayId, 'assistance_type_id' => $typeId,
                'amount' => is_numeric($row['amount'] ?? null) ? $row['amount'] : null, 'date_of_assistance' => $row['date_of_assistance'] ?: now()->toDateString(),
                'status' => in_array($row['status'] ?? '', ['Pending', 'Approved', 'Released', 'Completed'], true) ? $row['status'] : 'Pending', 'encoded_by' => $request->user()->id,
            ]);
            GeocodeBeneficiary::dispatch($beneficiary->id);
            $created++;
        }
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'beneficiaries_imported', 'description' => "Imported {$created} beneficiary record(s).", 'subject_type' => Beneficiary::class]);
        return redirect()->route('beneficiaries.index')->with('success', "Imported {$created} beneficiary record(s).");
    }
}
