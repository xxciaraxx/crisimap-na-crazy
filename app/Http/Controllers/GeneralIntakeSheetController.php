<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\GeneralIntakeSheet;
use App\Models\Beneficiary;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\BeneficiaryFromIntakeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class GeneralIntakeSheetController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', GeneralIntakeSheet::class);
        $tab = $request->string('tab')->toString() === 'submitted' ? 'submitted' : 'drafts';
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort')->toString();
        $sort = in_array($sort, ['date', 'name', 'status'], true) ? $sort : 'date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $baseQuery = GeneralIntakeSheet::query()
            ->when($request->user()->role === 'social_officer', fn ($query) => $query->where('encoded_by', $request->user()->id));

        $draftCount = (clone $baseQuery)->whereIn('status', ['draft', 'rejected'])->count();
        $submittedCount = (clone $baseQuery)->whereNotIn('status', ['draft', 'rejected'])->count();
        $intakes = $baseQuery->with(['barangay', 'encoder'])
            ->when($tab === 'drafts', fn ($query) => $query->whereIn('status', ['draft', 'rejected']))
            ->when($tab === 'submitted', fn ($query) => $query->whereNotIn('status', ['draft', 'rejected']))
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(client_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(intake_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(program) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(status) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(workflow_stage) LIKE ?', [$term]);
                });
            })
            ->when($sort === 'date', fn ($query) => $query->orderBy('intake_date', $direction))
            ->when($sort === 'name', fn ($query) => $query->orderByRaw("LOWER(client_name) {$direction}"))
            ->when($sort === 'status', fn ($query) => $query
                ->orderByRaw("LOWER(COALESCE(workflow_stage, status)) {$direction}")
                ->orderByRaw("LOWER(status) {$direction}"))
            ->orderBy('id', 'desc')
            ->paginate(10)->withQueryString();

        return view('intakes.index', compact('intakes', 'tab', 'draftCount', 'submittedCount', 'search', 'sort', 'direction'));
    }

    public function create()
    {
        $this->authorize('create', GeneralIntakeSheet::class);
        return view('intakes.create', ['barangays' => Barangay::orderBy('name')->get(), 'assistanceTypes' => AssistanceType::included()->orderBy('name')->get()]);
    }

    public function applicantHistory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:255']]);
        $name = trim($data['name']);

        $records = Beneficiary::query()
            ->active()
            ->with('assistanceType:id,name')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderBy('date_of_assistance')
            ->orderBy('created_at')
            ->get(['id', 'name', 'reference_code', 'assistance_type_id', 'date_of_assistance', 'status', 'created_at']);

        $previousDate = null;
        $applications = $records->map(function (Beneficiary $record) use (&$previousDate) {
            $date = $record->date_of_assistance ?? $record->created_at;
            $elapsed = $previousDate ? $this->elapsedLabel($previousDate, $date) : null;
            $previousDate = $date;

            return [
                'reference_code' => $record->reference_code,
                'date' => $date?->format('M d, Y'),
                'assistance_type' => $record->assistanceType?->name ?? 'Not specified',
                'status' => $record->status,
                'elapsed_since_previous' => $elapsed,
            ];
        })->values();

        return response()->json([
            'found' => $applications->isNotEmpty(),
            'name' => $name,
            'application_count' => $applications->count(),
            'applications' => $applications,
            'message' => $applications->isNotEmpty()
                ? 'Returning applicant: previous application records found.'
                : 'New applicant: no previous records found.',
        ]);
    }

    private function elapsedLabel($from, $to): string
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);
        $months = $from->diffInMonths($to);

        if ($months >= 12) {
            $years = intdiv($months, 12);
            $remainingMonths = $months % 12;
            return $years.' year'.($years === 1 ? '' : 's').($remainingMonths ? ' '.$remainingMonths.' month'.($remainingMonths === 1 ? '' : 's') : '').' later';
        }

        return $months.' month'.($months === 1 ? '' : 's').' later';
    }

    public function store(Request $request)
    {
        $this->authorize('create', GeneralIntakeSheet::class);
        $validatedPayload = $this->payload($request);
        $existingIntake = $this->existingIntakeForClient($validatedPayload['client_name']);

        if ($existingIntake) {
            throw ValidationException::withMessages([
                'client_name' => "This applicant already has intake code {$existingIntake->intake_number}. Open the existing intake instead of creating another one.",
            ]);
        }

        $action = $request->input('action', 'save');
        $isSubmitting = $action === 'submit';
        $isFocalPerson = $request->user()->role === 'focal_person';
        $intake = GeneralIntakeSheet::create($validatedPayload + [
            'encoded_by' => $request->user()->id,
            'status' => $isSubmitting
                ? ($isFocalPerson ? 'approved' : 'submitted')
                : 'draft',
            'workflow_stage' => $isSubmitting
                ? ($isFocalPerson ? 'head_approval' : 'eligibility_review')
                : 'draft',
        ]);
        $this->storeAttachments($request, $intake);

        if ($action === 'preview') {
            ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'intake_created', 'description' => "Created General Intake Sheet {$intake->intake_number}", 'subject_type' => GeneralIntakeSheet::class, 'subject_id' => $intake->id]);
            return redirect()->route('intakes.print', $intake);
        }

        if ($action === 'review') {
            ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'intake_created', 'description' => "Created General Intake Sheet {$intake->intake_number}", 'subject_type' => GeneralIntakeSheet::class, 'subject_id' => $intake->id]);
            return redirect()->route('intakes.review', $intake);
        }

        if ($isSubmitting) {
            $this->recordSubmission($intake, $isFocalPerson);
            return redirect()->route('intakes.show', $intake)->with('success', $isFocalPerson
                ? 'General Intake Sheet submitted to the MSWDO Head for approval.'
                : 'General Intake Sheet submitted to the Focal Person for eligibility review.');
        }

        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'intake_created', 'description' => "Created General Intake Sheet {$intake->intake_number}", 'subject_type' => GeneralIntakeSheet::class, 'subject_id' => $intake->id]);
        return redirect()->route('intakes.show', $intake)->with('success', 'General Intake Sheet saved as a draft.');
    }

    public function submit(GeneralIntakeSheet $intake)
    {
        $this->authorize('submit', $intake);
        $isFocalPerson = auth()->user()->role === 'focal_person';
        $isFocalEligibilitySubmission = $isFocalPerson
            && $intake->workflow_stage === 'eligibility_review';
        $intake->update([
            'status' => $isFocalEligibilitySubmission ? 'submitted' : ($isFocalPerson ? 'approved' : 'submitted'),
            'workflow_stage' => $isFocalEligibilitySubmission ? 'eligibility_review' : ($isFocalPerson ? 'head_approval' : 'eligibility_review'),
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
        if (! $isFocalEligibilitySubmission) {
            $this->recordSubmission($intake, $isFocalPerson);
        }

        return redirect()->route('intakes.show', $intake)->with('success', $isFocalEligibilitySubmission
            ? 'General Intake Sheet submitted to Applications for your eligibility approval.'
            : ($isFocalPerson
            ? 'General Intake Sheet submitted to the MSWDO Head for approval.'
            : 'General Intake Sheet submitted to the Focal Person for eligibility review.'));
    }

    private function recordSubmission(GeneralIntakeSheet $intake, bool $isFocalPerson): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'intake_submitted',
            'description' => "Submitted General Intake Sheet {$intake->intake_number} for ".($isFocalPerson ? 'MSWDO Head approval' : 'eligibility review'),
            'subject_type' => GeneralIntakeSheet::class,
            'subject_id' => $intake->id,
        ]);
        $recipients = $isFocalPerson
            ? User::query()->where('role', 'admin')->where('status', 'approved')->get()
            : User::query()->where('role', 'focal_person')->where('status', 'approved')->get();
        $recipients->each(fn (User $user) => $user->notify(new WorkflowNotification(
            $isFocalPerson ? 'Intake sheet awaiting MSWDO Head approval' : 'Intake sheet awaiting eligibility review',
            "General Intake Sheet {$intake->intake_number} is ready for ".($isFocalPerson ? 'MSWDO Head approval.' : 'eligibility review.'),
            route('applications.intakes.review', $intake),
            'warning'
        )));
    }

    public function decide(Request $request, GeneralIntakeSheet $intake)
    {
        $this->authorize('decide', $intake);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000'],
        ]);

        if ($data['decision'] === 'rejected') {
            $intake->update([
                'status' => 'rejected',
                'workflow_stage' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'rejected_by' => auth()->id(),
                'rejected_at' => now(),
            ]);
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'intake_rejected',
                'description' => "Rejected General Intake Sheet {$intake->intake_number}: {$data['rejection_reason']}",
                'subject_type' => GeneralIntakeSheet::class,
                'subject_id' => $intake->id,
            ]);
            User::query()->whereKey($intake->encoded_by)->where('role', 'social_officer')->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification(
                'Intake sheet rejected',
                "General Intake Sheet {$intake->intake_number} was rejected by the Focal Person. {$data['rejection_reason']}",
                route('intakes.show', $intake),
                'danger'
            )));
            return redirect()->route('intakes.show', $intake)->with('success', 'Intake sheet rejected.');
        }

        $intake->update([
            'status' => 'approved',
            'workflow_stage' => 'head_approval',
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'intake_approved',
            'description' => "Approved General Intake Sheet {$intake->intake_number} for eligibility review",
            'subject_type' => GeneralIntakeSheet::class,
            'subject_id' => $intake->id,
        ]);
        User::query()->where('role', 'admin')->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification(
            'Intake sheet awaiting MSWDO Head approval',
            "General Intake Sheet {$intake->intake_number} was marked eligible by the Focal Person.",
            route('applications.intakes.review', $intake),
            'warning'
        )));

        return redirect()->route('intakes.show', $intake)->with('success', 'Intake sheet marked eligible and sent to the MSWDO Head for approval.');
    }

    public function headDecide(Request $request, GeneralIntakeSheet $intake)
    {
        $this->authorize('headDecide', $intake);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000'],
        ]);

        $rejected = $data['decision'] === 'rejected';
        $intake->update([
            'status' => $rejected ? 'rejected' : 'approved',
            'workflow_stage' => $rejected ? 'rejected' : 'approved',
            'rejection_reason' => $rejected ? $data['rejection_reason'] : null,
            'rejected_by' => $rejected ? auth()->id() : null,
            'rejected_at' => $rejected ? now() : null,
        ]);
        if (! $rejected && ! $intake->beneficiaries()->exists()) {
            $assistanceType = AssistanceType::query()->where('name', $intake->purpose_of_assistance)->first();
            if ($assistanceType) {
                $beneficiary = app(BeneficiaryFromIntakeService::class)->create($intake, $assistanceType->id);
                $beneficiary->update([
                    'status' => 'Approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
            }
        }
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $rejected ? 'intake_rejected' : 'intake_approved',
            'description' => ($rejected ? 'Rejected' : 'Approved')." General Intake Sheet {$intake->intake_number} as MSWDO Head".($rejected ? ": {$data['rejection_reason']}" : ''),
            'subject_type' => GeneralIntakeSheet::class,
            'subject_id' => $intake->id,
        ]);
        User::query()->whereKey($intake->encoded_by)->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification(
            $rejected ? 'Intake sheet rejected' : 'Intake sheet approved',
            "General Intake Sheet {$intake->intake_number} was ".($rejected ? 'rejected by the MSWDO Head. '.$data['rejection_reason'] : 'approved by the MSWDO Head and is waiting for fund release.'),
            route('intakes.show', $intake),
            $rejected ? 'danger' : 'success'
        )));

        return redirect()->route('intakes.show', $intake)->with('success', $rejected
            ? 'Intake sheet rejected.'
            : 'Intake sheet approved. It is now waiting for the Focal Person to release the funds.');
    }

    public function show(GeneralIntakeSheet $intake)
    {
        $this->authorize('view', $intake);
        $intake->load(['barangay', 'encoder', 'rejectedBy', 'beneficiaries.assistanceType', 'attachments']);
        return view('intakes.show', compact('intake'));
    }

    public function edit(GeneralIntakeSheet $intake)
    {
        $this->authorize('update', $intake);
        $intake->load('attachments');
        return view('intakes.edit', ['intake' => $intake, 'barangays' => Barangay::orderBy('name')->get(), 'assistanceTypes' => AssistanceType::included()->orderBy('name')->get()]);
    }

    public function review(GeneralIntakeSheet $intake)
    {
        $this->authorize('view', $intake);

        return view('intakes.review', [
            'intake' => $intake,
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::included()->orderBy('name')->get(),
        ]);
    }

    public function submissionReview(GeneralIntakeSheet $intake)
    {
        $this->authorize('submit', $intake);
        $intake->load('attachments');

        return view('intakes.submit-review', [
            'intake' => $intake,
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::included()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, GeneralIntakeSheet $intake)
    {
        $this->authorize('update', $intake);
        $payload = $this->payload($request);
        $existingIntake = $this->existingIntakeForClient($payload['client_name'], $intake);

        if ($existingIntake) {
            throw ValidationException::withMessages([
                'client_name' => "This applicant already has intake code {$existingIntake->intake_number}. Open the existing intake instead of creating another one.",
            ]);
        }

        $intake->update($payload);
        $this->storeAttachments($request, $intake);

        if ($request->input('action') === 'review') {
            return redirect()->route('intakes.review', $intake);
        }

        return redirect()->route('intakes.show', $intake)->with('success', 'General Intake Sheet updated.');
    }

    public function print(GeneralIntakeSheet $intake)
    {
        $this->authorize('view', $intake);
        $intake->load(['barangay', 'encoder', 'beneficiaries.assistanceType']);

        $document = new \App\Models\DocumentRecord([
            'template' => 'general_intake_sheet',
            'general_intake_sheet_id' => $intake->id,
        ]);

        $settings = \App\Models\OfficialSetting::values();
        $beneficiary = $intake->beneficiaries->first();
        $fields = [];

        return view('intakes.print', compact('document', 'intake', 'beneficiary', 'settings', 'fields'));
    }

    private function payload(Request $request): array
    {
        $payload = $request->validate([
            'intake_date' => ['required', 'date'], 'program' => ['required', 'in:AICS,ESA,Others'], 'client_type' => ['required', 'in:new,returning'],
            'client_name' => ['required', 'string', 'max:255'], 'beneficiary_name' => ['nullable', 'string', 'max:255'], 'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sex' => ['nullable', 'in:Male,Female,Other'], 'civil_status' => ['nullable', 'in:Single,Married,Widowed'], 'occupation' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'], 'contact' => ['nullable', 'string', 'max:100'], 'barangay_id' => ['nullable', 'exists:barangays,id'], 'address' => ['nullable', 'string'],
            'purpose_of_assistance' => ['nullable', 'string'], 'assistance_specification' => ['nullable', 'required_if:purpose_of_assistance,Other / Miscellaneous', 'string', 'max:255'], 'requested_amount' => ['nullable', 'numeric', 'min:0'], 'diagnosis_or_cause_of_death' => ['nullable', 'string'],
            'family_composition' => ['nullable', 'array'],
            'client_sectors' => ['nullable', 'array'], 'income_resources' => ['nullable', 'array'],
            'combined_family_income' => ['nullable', 'numeric', 'min:0'],
            'employed_members_count' => ['nullable', 'integer', 'min:0'],
            'seasonal_members_count' => ['nullable', 'integer', 'min:0'],
            'monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'supporting_documents' => ['nullable', 'array'], 'social_worker_assessment' => ['nullable', 'string'], 'interviewer_name' => ['nullable', 'string', 'max:255'], 'interviewer_position' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ]);

        $payload['client_name'] = $this->normalizeClientName($payload['client_name']);

        return $payload;
    }

    private function storeAttachments(Request $request, GeneralIntakeSheet $intake): void
    {
        foreach ((array) $request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('intake-attachments/'.$intake->id, 'sensitive');
            $intake->attachments()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'document_type' => $file->getClientOriginalExtension(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function existingIntakeForClient(string $clientName, ?GeneralIntakeSheet $except = null): ?GeneralIntakeSheet
    {
        $query = GeneralIntakeSheet::query()
            ->whereRaw('LOWER(TRIM(client_name)) = ?', [mb_strtolower($this->normalizeClientName($clientName))])
            ->oldest('id');

        if ($except) {
            $query->whereKeyNot($except->getKey());
        }

        return $query->first();
    }

    private function normalizeClientName(string $clientName): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $clientName));
    }
}
