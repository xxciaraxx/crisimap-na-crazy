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
use Illuminate\Support\Facades\DB;
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
        $validated = $request->validate([
            'rows' => ['required', 'array', 'max:500'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.age' => ['required', 'integer', 'min:1', 'max:120'],
            'rows.*.sex' => ['nullable', 'in:Male,Female,Other'],
            'rows.*.contact' => ['nullable', 'string', 'max:50'],
            'rows.*.address' => ['nullable', 'string', 'max:255'],
            'rows.*.barangay' => ['required', 'string', 'max:255'],
            'rows.*.assistance_type' => ['nullable', 'string', 'max:255'],
            'rows.*.amount' => ['nullable', 'numeric', 'min:0'],
            'rows.*.date_of_assistance' => ['nullable', 'date', 'before_or_equal:today'],
            // Imports create applications, never approved or released aid.
            'rows.*.status' => ['nullable', 'in:Pending'],
        ]);
        $rows = collect($validated['rows'])->filter(fn ($row) => filled($row['name'] ?? null));
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

        $created = DB::transaction(function () use ($rows, $barangays, $types, $request) {
            $created = 0;
            foreach ($rows as $index => $row) {
                $barangayId = $barangays->get(mb_strtolower(trim((string) $row['barangay'])));
                $typeId = filled($row['assistance_type'] ?? null) ? $types->get(mb_strtolower(trim((string) $row['assistance_type']))) : null;
                $beneficiary = new Beneficiary([
                    'name' => trim($row['name']), 'age' => (int) $row['age'], 'sex' => $row['sex'] ?? 'Other',
                    'contact' => $row['contact'] ?? null, 'address' => $row['address'] ?? null, 'barangay_id' => $barangayId, 'assistance_type_id' => $typeId,
                    'amount' => $row['amount'] ?? null, 'date_of_assistance' => $row['date_of_assistance'] ?? now()->toDateString(),
                    'status' => 'Pending', 'encoded_by' => $request->user()->id,
                ]);
                $beneficiary->syncIdentityFields();
                $eligibility = app(\App\Services\BeneficiaryEligibilityChecker::class)->for($beneficiary);
                if (! $eligibility['eligible']) {
                    throw ValidationException::withMessages(["rows.$index.name" => 'This row violates the 90-day assistance policy and must be reviewed individually.']);
                }
                $beneficiary->eligibility_status = 'Eligible';
                $beneficiary->eligibility_checked_at = now();
                $beneficiary->save();
                GeocodeBeneficiary::dispatch($beneficiary->id)->afterCommit();
                $created++;
            }

            return $created;
        });
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'beneficiaries_imported', 'description' => "Imported {$created} beneficiary record(s).", 'subject_type' => Beneficiary::class]);
        return redirect()->route('beneficiaries.index')->with('success', "Imported {$created} beneficiary record(s).");
    }
}
