<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseRecordRequest;
use App\Http\Requests\UpdateCaseRecordRequest;
use App\Models\Barangay;
use App\Models\CaseRecord;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Notifications\WorkflowNotification;

class CaseRecordController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CaseRecord::class);
        $records = CaseRecord::query()
            ->with(['barangay', 'encoder'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(fn ($q) => $q->where('case_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%"));
            })
            ->when($request->filled('barangay_id'), fn ($query) => $query->where('barangay_id', $request->integer('barangay_id')))
            ->latest('date_of_entry')->latest()->paginate(20)->withQueryString();

        if ($request->user()->role === 'social_officer') {
            $records = CaseRecord::query()->with(['barangay', 'encoder'])->where('encoded_by', $request->user()->id)->latest('date_of_entry')->latest()->paginate(20)->withQueryString();
        }

        return view('case-records.index', ['records' => $records, 'barangays' => Barangay::orderBy('name')->get()]);
    }

    public function create()
    {
        $this->authorize('create', CaseRecord::class);
        return view('case-records.create', ['barangays' => Barangay::orderBy('name')->get()]);
    }

    public function store(StoreCaseRecordRequest $request)
    {
        $this->authorize('create', CaseRecord::class);
        $record = $this->saveRecord($request->validated(), $request);

        return redirect()->route('case-records.show', $record)->with('success', 'Case record created. You can now add assistance beneficiaries to this case.');
    }

    public function show(CaseRecord $caseRecord)
    {
        $this->authorize('view', $caseRecord);
        $caseRecord->load(['barangay', 'encoder', 'householdMembers', 'beneficiaries.assistanceType']);

        return view('case-records.show', compact('caseRecord'));
    }

    public function print(CaseRecord $caseRecord)
    {
        $this->authorize('view', $caseRecord);
        $caseRecord->load(['barangay', 'encoder', 'householdMembers']);

        return Pdf::loadView('case-records.print', compact('caseRecord'))
            ->download('case-record-'.$caseRecord->case_number.'.pdf');
    }

    public function edit(CaseRecord $caseRecord)
    {
        $this->authorize('update', $caseRecord);
        $caseRecord->load('householdMembers');

        return view('case-records.edit', ['caseRecord' => $caseRecord, 'barangays' => Barangay::orderBy('name')->get()]);
    }

    public function update(UpdateCaseRecordRequest $request, CaseRecord $caseRecord)
    {
        $this->authorize('update', $caseRecord);
        $this->saveRecord($request->validated(), $request, $caseRecord);

        return redirect()->route('case-records.show', $caseRecord)->with('success', 'Case record updated.');
    }

    public function advance(Request $request, CaseRecord $caseRecord)
    {
        $this->authorize('advance', $caseRecord);
        abort_unless($caseRecord->canTransitionTo('under_review'), 422, 'This case record cannot be forwarded from its current status.');
        $caseRecord->update(['status' => 'under_review']);
        $this->logStatusChange($caseRecord, 'under_review');
        User::query()->whereIn('role', ['focal_person', 'admin'])->get()->each->notify(new WorkflowNotification('Case record awaiting review', "Case {$caseRecord->case_number} has been forwarded for review.", route('case-records.show', $caseRecord)));
        return back()->with('success', 'Case record forwarded for admin review.');
    }

    public function decide(Request $request, CaseRecord $caseRecord)
    {
        $this->authorize('approve', $caseRecord);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000']]);
        abort_unless($caseRecord->canTransitionTo($data['decision']), 422, 'This case record decision is not valid for its current status.');
        $caseRecord->update(['status' => $data['decision'], 'rejection_reason' => $data['decision'] === 'rejected' ? $data['rejection_reason'] : null]);
        $this->logStatusChange($caseRecord, $data['decision'], $data['rejection_reason'] ?? null);
        $caseRecord->encoder?->notify(new WorkflowNotification('Case record decision recorded', "Case {$caseRecord->case_number} was {$data['decision']}.", route('case-records.show', $caseRecord)));
        return back()->with('success', 'Case record decision saved.');
    }

    private function logStatusChange(CaseRecord $record, string $status, ?string $reason = null): void
    {
        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'case_status_changed', 'description' => "Case {$record->case_number} changed to {$status}".($reason ? ": {$reason}" : ''), 'subject_type' => CaseRecord::class, 'subject_id' => $record->id]);
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
