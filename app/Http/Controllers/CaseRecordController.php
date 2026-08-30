<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseRecordRequest;
use App\Http\Requests\UpdateCaseRecordRequest;
use App\Models\Barangay;
use App\Models\CaseRecord;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\GeneralIntakeSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\BeneficiaryFromCaseRecordService;

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
        $intake = request('intake') ? GeneralIntakeSheet::findOrFail(request('intake')) : null;
        if ($intake) $this->authorize('view', $intake);
        return view('case-records.create', ['barangays' => Barangay::orderBy('name')->get(), 'intake' => $intake]);
    }

    public function store(StoreCaseRecordRequest $request)
    {
        $this->authorize('create', CaseRecord::class);
        $record = $this->saveRecord($request->validated(), $request);

        User::query()->where('role', 'focal_person')->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification('Case record awaiting eligibility review', "New case record awaiting eligibility review: {$record->case_number}", route('case-records.show', $record), 'warning')));
        $this->logStatusChange($record, 'submitted');

        return redirect()->route('case-records.show', $record)->with('success', 'Case record created. You can now add assistance beneficiaries to this case.');
    }

    public function show(CaseRecord $caseRecord)
    {
        $this->authorize('view', $caseRecord);
        $caseRecord->load(['intake', 'barangay', 'encoder', 'householdMembers', 'beneficiaries.assistanceType']);

        return view('case-records.show', [
            'caseRecord' => $caseRecord,
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
        ]);
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

        return view('case-records.edit', ['caseRecord' => $caseRecord, 'intake' => $caseRecord->intake, 'barangays' => Barangay::orderBy('name')->get()]);
    }

    public function update(UpdateCaseRecordRequest $request, CaseRecord $caseRecord)
    {
        $this->authorize('update', $caseRecord);
        $this->saveRecord($request->validated(), $request, $caseRecord);

        return redirect()->route('case-records.show', $caseRecord)->with('success', 'Case record updated.');
    }

    public function decideEligibility(Request $request, CaseRecord $caseRecord)
    {
        $this->authorize('decideEligibility', $caseRecord);
        $data = $request->validate([
            'decision' => ['required', 'in:eligible,rejected'],
            'assistance_type_id' => ['nullable', 'required_if:decision,eligible', 'exists:assistance_types,id'],
            'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000'],
        ]);
        if ($data['decision'] === 'rejected') {
            $caseRecord->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
            $this->logStatusChange($caseRecord, 'rejected', $data['rejection_reason']);
            $caseRecord->encoder?->notify(new WorkflowNotification('Case record rejected', "Case {$caseRecord->case_number} was rejected: {$data['rejection_reason']}", route('case-records.show', $caseRecord), 'danger'));
            return back()->with('success', 'Case record marked as rejected.');
        }
        $caseRecord->update(['status' => 'approved', 'rejection_reason' => null]);
        $this->logStatusChange($caseRecord, 'approved');
        $beneficiary = app(BeneficiaryFromCaseRecordService::class)->createFrom($caseRecord, (int) $data['assistance_type_id']);
        User::query()->where('role', 'admin')->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification('Application awaiting approval', "New application awaiting approval: {$beneficiary->reference_code}", route('beneficiaries.show', $beneficiary), 'warning')));
        $caseRecord->encoder?->notify(new WorkflowNotification('Case marked eligible', 'Your case was marked eligible and forwarded for approval.', route('beneficiaries.show', $beneficiary), 'success'));
        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Case marked eligible. Certificate of Eligibility and Application Form are ready to print.');
    }

    private function logStatusChange(CaseRecord $record, string $status, ?string $reason = null): void
    {
        ActivityLog::create(['user_id' => auth()->id(), 'action' => 'case_status_changed', 'description' => "Case {$record->case_number} changed to {$status}".($reason ? ": {$reason}" : ''), 'subject_type' => CaseRecord::class, 'subject_id' => $record->id]);
    }

    private function saveRecord(array $payload, Request $request, ?CaseRecord $caseRecord = null): CaseRecord
    {
        $members = collect($payload['members'] ?? [])->filter(fn ($member) => filled($member['name'] ?? null))->values();
        unset($payload['members'], $payload['client_signature']);

        if (! $payload['is_government_program_member']) {
            $payload['government_programs'] = null;
            $payload['other_government_program'] = null;
        }

        if ($request->hasFile('client_signature')) {
            if ($caseRecord?->client_signature_path) {
                Storage::disk('sensitive')->delete($caseRecord->client_signature_path);
            }
            $payload['client_signature_path'] = $request->file('client_signature')->store('case-records/signatures', 'sensitive');
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
