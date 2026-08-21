<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Http\Requests\UpdateBeneficiaryRequest;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Attachment;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\CaseRecord;
use App\Models\User;
use App\Jobs\GeocodeBeneficiary;
use App\Services\BeneficiaryGeocoder;
use App\Services\BeneficiaryEligibilityChecker;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\WorkflowNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BeneficiaryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Beneficiary::class);
        $query = $this->filteredQuery($request)
            ->with(['barangay', 'assistanceType', 'caseRecord', 'encoder', 'overrideUser', 'rejectedBy'])
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at');

        $beneficiaries = $query->paginate(20)->withQueryString();
        $missingLocationCount = (clone $this->filteredQuery($request))
            ->where(fn (Builder $query) => $query->whereNull('latitude')->orWhereNull('longitude'))
            ->count();

        return view('beneficiaries.index', [
            'beneficiaries' => $beneficiaries,
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
            'missingLocationCount' => $missingLocationCount,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Beneficiary::class);
        return view('beneficiaries.create', [
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
            'canOverrideEligibility' => $this->canOverrideEligibility(),
            'caseRecords' => CaseRecord::orderByDesc('date_of_entry')->limit(200)->get(['id', 'case_number', 'client_name']),
        ]);
    }

    public function store(StoreBeneficiaryRequest $request)
    {
        $this->authorize('create', Beneficiary::class);
        return $this->persist($request->validated(), $request);
    }

    public function show(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);
        $beneficiary->load(['barangay', 'assistanceType', 'caseRecord', 'attachments.uploader', 'encoder', 'overrideUser', 'rejectedBy']);

        return view('beneficiaries.show', array_merge(
            compact('beneficiary'),
            $this->historyPayload($beneficiary)
        ));
    }

    public function edit(Beneficiary $beneficiary)
    {
        $this->authorize('update', $beneficiary);
        return view('beneficiaries.edit', [
            'beneficiary' => $beneficiary,
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
            'canOverrideEligibility' => $this->canOverrideEligibility(),
            'caseRecords' => CaseRecord::orderByDesc('date_of_entry')->limit(200)->get(['id', 'case_number', 'client_name']),
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary)
    {
        $this->authorize('update', $beneficiary);
        if ($request->input('status') !== $beneficiary->status) {
            throw ValidationException::withMessages([
                'status' => 'Use the dedicated approval or release action to change an application status.',
            ]);
        }
        return $this->persist($request->validated(), $request, $beneficiary);
    }

    public function destroy(Beneficiary $beneficiary)
    {
        $this->authorize('delete', $beneficiary);
        $beneficiary->delete();
        $this->logActivity('deleted', 'Deleted beneficiary record for '.$beneficiary->name, $beneficiary);

        return redirect()->route('beneficiaries.index')->with('success', 'Beneficiary record deleted.');
    }

    public function decide(Request $request, Beneficiary $beneficiary)
    {
        $this->authorize('decide', $beneficiary);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000']]);

        if ($data['decision'] === 'rejected') {
            $beneficiary->update(['status' => 'Rejected', 'rejection_reason' => $data['rejection_reason'], 'rejected_at' => now(), 'rejected_by' => $request->user()->id]);
            $this->logActivity('rejected', 'MSWDO Head rejected application for '.$beneficiary->name.': '.$data['rejection_reason'], $beneficiary);
            $beneficiary->encoder?->notify(new WorkflowNotification('Application rejected', "Application {$beneficiary->reference_code} rejected: {$data['rejection_reason']}", route('beneficiaries.show', $beneficiary)));
            return back()->with('success', 'Application rejected.');
        }

        $beneficiary->update(['status' => 'Approved', 'approved_at' => now(), 'approved_by' => $request->user()->id]);
        $this->logActivity('approved', 'MSWDO Head approved application for '.$beneficiary->name, $beneficiary);
        $beneficiary->encoder?->notify(new WorkflowNotification('Application approved', "Application {$beneficiary->reference_code} approved — release once billing clears.", route('beneficiaries.show', $beneficiary)));
        return back()->with('success', 'Application approved. Focal person can release funds once billing clears.');
    }

    /**
     * Decision URLs are state-changing POST endpoints. Gracefully recover if a
     * stale bookmark or an old browser page requests one with GET.
     */
    public function decisionRedirect(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);

        return redirect()->route('beneficiaries.show', $beneficiary)
            ->with('error', 'Application decisions must be submitted using the approval controls.');
    }

    public function release(Request $request, Beneficiary $beneficiary)
    {
        $this->authorize('release', $beneficiary);
        $beneficiary->update(['status' => 'Released', 'released_at' => now(), 'released_by' => $request->user()->id]);
        if (blank($beneficiary->latitude)) GeocodeBeneficiary::dispatch($beneficiary->id);
        User::query()->where('role', 'admin')->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification('Beneficiary released', "{$beneficiary->name} has been marked released and is now an active AICS beneficiary.", route('beneficiaries.show', $beneficiary))));
        $this->logActivity('released', 'Marked as released for '.$beneficiary->name, $beneficiary);
        return back()->with('success', $beneficiary->name.' is now an active AICS beneficiary and will appear on the map.');
    }

    public function applicationForm(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);
        if (! in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden'], true)) {
            return back()->with('error', 'Only eligible applications can be printed.');
        }

        $beneficiary->load(['barangay', 'assistanceType', 'caseRecord', 'encoder', 'overrideUser']);
        return Pdf::loadView('beneficiaries.application-form', compact('beneficiary'))
            ->download('application-form-'.$beneficiary->reference_code.'.pdf');
    }

    public function certificate(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);
        if (! in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden'], true)) {
            return back()->with('error', 'Only eligible applications can be printed.');
        }

        $beneficiary->load(['barangay', 'assistanceType', 'encoder']);
        return Pdf::loadView('beneficiaries.certificate', compact('beneficiary'))
            ->download('certificate-of-eligibility-'.$beneficiary->reference_code.'.pdf');
    }

    public function verify(string $referenceCode)
    {
        $beneficiary = Beneficiary::query()
            ->with(['barangay', 'assistanceType', 'attachments.uploader', 'encoder', 'overrideUser'])
            ->where('reference_code', $referenceCode)
            ->firstOrFail();

        return view('beneficiaries.verify', array_merge(
            compact('beneficiary'),
            $this->historyPayload($beneficiary)
        ));
    }

    public function duplicateCheck(Request $request)
    {
        $candidate = $this->makeCandidate($request->only([
            'name',
            'age',
            'birth_date',
            'contact',
            'address',
            'barangay_id',
        ]));
        $duplicateQuery = Beneficiary::query()->where('history_key', $candidate->history_key);

        if ($request->filled('exclude_id')) {
            $duplicateQuery->where('id', '!=', $request->integer('exclude_id'));
        }

        $latest = (clone $duplicateQuery)->whereIn('status', ['Approved', 'Released', 'Completed'])->whereNotNull('date_of_assistance')
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at')
            ->first();

        $daysSince = $latest?->date_of_assistance
            ? $latest->date_of_assistance->diffInDays(now())
            : null;

        $nextEligibleAt = $latest?->date_of_assistance?->copy()->addDays(90);

        return response()->json([
            'duplicate_count' => $duplicateQuery->count(),
            'potential_duplicate' => $latest ? [
                'id' => $latest->id,
                'name' => $latest->name,
                'reference_code' => $latest->reference_code,
                'date_of_assistance' => $latest->date_of_assistance?->toDateString(),
                'status' => $latest->status,
                'barangay' => $latest->barangay?->name,
                'contact' => $latest->contact,
            ] : null,
            'eligible' => $daysSince === null || $daysSince >= 90,
            'days_since_last_assistance' => $daysSince,
            'next_eligible_at' => $nextEligibleAt?->toDateString(),
            'message' => $latest && $daysSince !== null && $daysSince < 90
                ? 'This beneficiary is still within the 90-day restriction window.'
                : ($latest ? 'A matching beneficiary history record exists.' : 'No duplicate found.'),
        ]);
    }

    private function persist(array $payload, Request $request, ?Beneficiary $beneficiary = null)
    {
        $payload = $this->normalizePayload($payload, $request);
        $candidate = $this->makeCandidate($payload);
        $eligibility = $this->eligibilityFor($candidate, $beneficiary);
        $overrideRequested = $request->boolean('override_eligibility');
        $canOverride = $this->canOverrideEligibility();

        if ($overrideRequested && ! $canOverride) {
            throw ValidationException::withMessages([
                'override_eligibility' => 'Only supervisors can override the 90-day rule.',
            ]);
        }

        if (! $eligibility['eligible'] && ! $overrideRequested) {
            throw ValidationException::withMessages([
                'override_eligibility' => 'This beneficiary is not eligible yet. Supervisor override is required.',
                'override_justification' => 'Next eligible date: '.($eligibility['next_eligible_at']?->toDateString() ?? 'unknown'),
            ]);
        }

        $manualLocation = $request->filled('latitude') && $request->filled('longitude');
        if ($manualLocation) {
            $payload['geocode_precision'] = 'manual';
            $payload['geocoded_at'] = now();
        } elseif (! $beneficiary || $beneficiary->geocode_precision !== 'manual') {
            $fallback = app(BeneficiaryGeocoder::class)->jitteredCentroidFor(
                $payload['barangay_id'] ?? null,
                ($beneficiary?->id ?? '').'|'.($payload['name'] ?? '').'|'.($payload['address'] ?? '')
            );
            if ($fallback) {
                $payload += $fallback;
                $payload['geocode_precision'] = 'barangay_centroid';
                $payload['geocoded_at'] = now();
            }
        }

        $payload['eligibility_checked_at'] = now();
        $payload['eligibility_status'] = $overrideRequested && ! $eligibility['eligible'] ? 'Overridden' : ($eligibility['eligible'] ? 'Eligible' : 'Ineligible');
        if ($overrideRequested) {
            $payload['override_by'] = auth()->id();
            $payload['override_at'] = now();
            $payload['override_justification'] = $payload['override_justification'] ?? null;
        } elseif (! $beneficiary) {
            $payload['override_by'] = null;
            $payload['override_at'] = null;
            $payload['override_justification'] = null;
        }
        $payload['date_of_assistance'] = $payload['date_of_assistance'] ?? now()->toDateString();

        if ($beneficiary) {
            if (! $beneficiary->canTransitionTo($payload['status'])) {
                throw ValidationException::withMessages(['status' => 'This status change is not valid for the current application state.']);
            }
            $beneficiary->fill($payload);
            $beneficiary->save();
            $message = 'Beneficiary record updated.';
            $action = 'updated';
            $description = 'Updated beneficiary record for '.$beneficiary->name;
        } else {
            if ($payload['status'] !== 'Pending') {
                throw ValidationException::withMessages(['status' => 'New applications must begin in Pending status.']);
            }
            $beneficiary = Beneficiary::create($payload);
            $message = 'Beneficiary record created.';
            $action = 'created';
            $description = 'Created beneficiary record for '.$beneficiary->name;
        }

        $this->syncStatusTimestamps($beneficiary);
        if ($beneficiary->status === 'Approved' && $beneficiary->wasChanged('status')) {
            ($beneficiary->caseRecord?->encoder ?? $beneficiary->encoder)?->notify(new WorkflowNotification('Application approved', "Application {$beneficiary->reference_code} was approved.", route('beneficiaries.show', $beneficiary)));
        }
        if (! $manualLocation && filled($beneficiary->address) && $beneficiary->geocode_precision !== 'manual') {
            GeocodeBeneficiary::dispatch($beneficiary->id)->delay(now()->addSecond());
        }
        $this->storeAttachments($request, $beneficiary);
        $this->logActivity($action, $description, $beneficiary);

        $flash = ['success' => $message];
        if ($eligibility['duplicate_count'] > 0) {
            $flash['warning'] = $eligibility['eligible']
                ? 'A matching beneficiary history record already exists.'
                : 'Duplicate history found and the 90-day rule was enforced.';
        }

        return $beneficiary->wasRecentlyCreated
            ? redirect()->route('beneficiaries.index')->with($flash)
            : redirect()->route('beneficiaries.show', $beneficiary)->with($flash);
    }

    private function normalizePayload(array $payload, Request $request): array
    {
        $payload['birth_date'] = $payload['birth_date'] ?? null;
        $payload['amount'] = $payload['amount'] ?? null;
        $payload['status'] = $payload['status'] ?? 'Pending';
        $payload['encoded_by'] = auth()->id();
        $payload['date_of_assistance'] = $payload['date_of_assistance'] ?? now()->toDateString();
        $payload['override_justification'] = $request->input('override_justification');
        $payload['name'] = Str::squish((string) ($payload['name'] ?? ''));
        $payload['address'] = filled($payload['address'] ?? null) ? Str::squish((string) $payload['address']) : null;
        $payload['contact'] = filled($payload['contact'] ?? null) ? Str::squish((string) $payload['contact']) : null;

        return $payload;
    }

    private function makeCandidate(array $payload): Beneficiary
    {
        $candidate = new Beneficiary($payload);
        $candidate->syncIdentityFields();

        return $candidate;
    }

    private function eligibilityFor(Beneficiary $candidate, ?Beneficiary $ignore = null): array
    {
        return app(BeneficiaryEligibilityChecker::class)->for($candidate, $ignore);
    }

    private function historyPayload(Beneficiary $beneficiary): array
    {
        $historyKey = $beneficiary->history_key ?: $beneficiary->makeHistoryKey();
        $historyEntries = Beneficiary::query()
            ->with(['barangay', 'assistanceType', 'encoder', 'overrideUser'])
            ->where('history_key', $historyKey)
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at')
            ->get();

        $processingHistory = ActivityLog::query()
            ->with('user')
            ->where('subject_type', Beneficiary::class)
            ->where('subject_id', $beneficiary->id)
            ->orderByDesc('created_at')
            ->get();

        return [
            'historyEntries' => $historyEntries,
            'processingHistory' => $processingHistory,
            'historySummary' => [
                'total_assistance' => $historyEntries->count(),
                'total_amount' => (float) $historyEntries->sum(fn (Beneficiary $item) => $item->amount ?? 0),
                'last_assistance_date' => $historyEntries->first()?->date_of_assistance,
                'categories' => $historyEntries->pluck('assistanceType.name')->filter()->unique()->values(),
                'approved' => $historyEntries->where('status', 'Approved')->count(),
                'released' => $historyEntries->whereIn('status', ['Released', 'Completed'])->count(),
            ],
        ];
    }

    private function storeAttachments(Request $request, Beneficiary $beneficiary): void
    {
        $files = [];

        if ($request->hasFile('attachment')) {
            $files[] = $request->file('attachment');
        }

        foreach ((array) $request->file('attachments', []) as $file) {
            $files[] = $file;
        }

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('attachments', 'public');
            $beneficiary->attachments()->create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'document_type' => $this->guessDocumentType($file->getClientOriginalName()),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function guessDocumentType(string $filename): string
    {
        return Str::contains(Str::lower($filename), ['id', 'proof']) ? 'supporting_document' : 'attachment';
    }

    private function syncStatusTimestamps(Beneficiary $beneficiary): void
    {
        $dirty = [];

        if ($beneficiary->status === 'Approved' && blank($beneficiary->approved_at)) {
            $dirty['approved_at'] = now();
        }
        if ($beneficiary->status === 'Released' && blank($beneficiary->released_at)) {
            $dirty['released_at'] = now();
        }
        if ($beneficiary->status === 'Completed' && blank($beneficiary->completed_at)) {
            $dirty['completed_at'] = now();
        }

        if ($dirty !== []) {
            $beneficiary->forceFill($dirty)->saveQuietly();
        }
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Beneficiary::query()->active();

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->integer('assistance_type_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('reference_code', 'like', '%'.$search.'%')
                    ->orWhere('contact', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%');
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('date_of_assistance', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date_of_assistance', '<=', $request->date('to'));
        }
        if ($request->filled('birth_date')) {
            $query->whereDate('birth_date', $request->date('birth_date'));
        }
        if ($request->filled('reference_code')) {
            $query->where('reference_code', $request->string('reference_code'));
        }

        return $query;
    }

    private function logActivity(string $action, string $description, Beneficiary $beneficiary): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => Beneficiary::class,
            'subject_id' => $beneficiary->id,
        ]);
    }

    private function canOverrideEligibility(): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole('super_admin');
    }
}
