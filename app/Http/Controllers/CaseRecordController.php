<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseRecordRequest;
use App\Http\Requests\UpdateCaseRecordRequest;
use App\Models\Barangay;
use App\Models\CaseRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CaseRecordController extends Controller
{
    public function index(Request $request)
    {
        $records = CaseRecord::query()
            ->with(['barangay', 'encoder'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(fn ($q) => $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%"));
            })
            ->when($request->filled('barangay_id'), fn ($query) => $query->where('barangay_id', $request->integer('barangay_id')))
            ->latest('date_of_entry')->latest()->paginate(20)->withQueryString();

        return view('case-records.index', ['records' => $records, 'barangays' => Barangay::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('case-records.create', ['barangays' => Barangay::orderBy('name')->get()]);
    }

    public function store(StoreCaseRecordRequest $request)
    {
        $record = $this->saveRecord($request->validated(), $request);

        return redirect()->route('case-records.show', $record)->with('success', 'Case record created. You can now add assistance beneficiaries to this case.');
    }

    public function show(CaseRecord $caseRecord)
    {
        $caseRecord->load(['barangay', 'encoder', 'householdMembers', 'beneficiaries.assistanceType']);

        return view('case-records.show', compact('caseRecord'));
    }

    public function edit(CaseRecord $caseRecord)
    {
        $caseRecord->load('householdMembers');

        return view('case-records.edit', ['caseRecord' => $caseRecord, 'barangays' => Barangay::orderBy('name')->get()]);
    }

    public function update(UpdateCaseRecordRequest $request, CaseRecord $caseRecord)
    {
        $this->saveRecord($request->validated(), $request, $caseRecord);

        return redirect()->route('case-records.show', $caseRecord)->with('success', 'Case record updated.');
    }

    private function saveRecord(array $payload, Request $request, ?CaseRecord $caseRecord = null): CaseRecord
    {
        $members = collect($payload['members'] ?? [])->filter(fn ($member) => filled($member['name'] ?? null))->values();
        unset($payload['members'], $payload['client_signature']);

        if ($request->hasFile('client_signature')) {
            if ($caseRecord?->client_signature_path) {
                Storage::disk('public')->delete($caseRecord->client_signature_path);
            }
            $payload['client_signature_path'] = $request->file('client_signature')->store('case-records/signatures', 'public');
        }

        if ($caseRecord) {
            $caseRecord->update($payload);
        } else {
            $payload['encoded_by'] = $request->user()->id;
            $caseRecord = CaseRecord::create($payload);
        }

        $caseRecord->householdMembers()->delete();
        $caseRecord->householdMembers()->createMany($members->all());

        return $caseRecord;
    }
}
