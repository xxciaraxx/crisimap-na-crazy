<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBeneficiaryRequest;
use App\Models\ActivityLog;
use App\Models\AssistanceType;
use App\Models\Attachment;
use App\Models\Barangay;
use App\Models\BarangayCentroid;
use App\Models\Beneficiary;
use App\Models\CaseRecord;
use App\Models\GeneralIntakeSheet;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BeneficiaryController extends Controller
{
    private const TAGUDIN_MAP_FALLBACK = [
        'latitude' => 16.9355,
        'longitude' => 120.4442,
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Beneficiary::class);
        $statusScope = null;
        if ($request->routeIs('applications.index')) {
            $request->merge(['status' => null]);
            $statusScope = ['Pending', 'Approved'];
        } elseif ($request->routeIs('beneficiary-records.index')) {
            $request->merge(['status' => null]);
            $statusScope = ['Released', 'Rejected'];
        }

        $query = $this->filteredQuery($request, $statusScope)
            ->with(['barangay', 'assistanceType', 'encoder', 'overrideUser', 'rejectedBy'])
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at');

        $beneficiaries = $query->paginate(20)->withQueryString();
        $pendingIntakes = collect();
        if ($request->routeIs('applications.index') && in_array($request->user()->role, ['focal_person', 'admin', 'super_admin'], true)) {
            $pendingIntakes = GeneralIntakeSheet::query()
                ->with('barangay')
                ->when($request->user()->role === 'focal_person', fn ($query) => $query->where('workflow_stage', 'eligibility_review'))
                ->when(in_array($request->user()->role, ['admin', 'super_admin'], true), fn ($query) => $query->where('workflow_stage', 'head_approval'))
                ->latest('intake_date')
                ->latest()
                ->get();
        }
        return view('beneficiaries.index', [
            'beneficiaries' => $beneficiaries,
            'barangays' => Barangay::orderBy('name')->get(),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
            'pageTitle' => $request->routeIs('applications.index') ? 'Applications' : 'Beneficiary Records',
            'pageDescription' => $request->routeIs('applications.index')
                ? 'Applications awaiting approval or fund release.'
                : 'Released and rejected assistance records for new and returning applicants.',
            'pendingIntakes' => $pendingIntakes,
        ]);
    }

    public function show(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);
        $beneficiary->load(['barangay', 'assistanceType', 'attachments.uploader', 'encoder', 'overrideUser', 'rejectedBy', 'intake']);

        return view('beneficiaries.show', array_merge(
            compact('beneficiary'),
            $this->historyPayload($beneficiary)
        ));
    }

    public function edit(Beneficiary $beneficiary)
    {
        $this->authorize('update', $beneficiary);
        $barangays = Barangay::orderBy('name')->get();

        return view('beneficiaries.edit', [
            'beneficiary' => $beneficiary,
            'barangays' => $barangays,
            'mapCentroids' => $this->mapCentroids($barangays),
            'assistanceTypes' => AssistanceType::orderBy('name')->get(),
            'canOverrideEligibility' => $this->canOverrideEligibility(),
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
        $beneficiary->update([
            'archived_at' => now(),
            'archived_by' => request()->user()->id,
            'archive_reason' => request()->validate(['archive_reason' => ['required', 'string', 'max:1000']])['archive_reason'],
        ]);
        $this->logActivity('archived', 'Archived beneficiary record for '.$beneficiary->name, $beneficiary);

        return redirect()->route('beneficiaries.index')->with('success', 'Beneficiary record archived.');
    }

    public function decide(Request $request, Beneficiary $beneficiary)
    {
        $this->authorize('decide', $beneficiary);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:2000']]);

        if ($data['decision'] === 'rejected') {
            $beneficiary->update(['status' => 'Rejected', 'rejection_reason' => $data['rejection_reason'], 'rejected_at' => now(), 'rejected_by' => $request->user()->id]);
            $beneficiary->intake?->update([
                'status' => 'rejected',
                'workflow_stage' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
            ]);
            $this->logActivity('rejected', 'MSWDO Head rejected application for '.$beneficiary->name.': '.$data['rejection_reason'], $beneficiary);
            return back()->with('success', 'Application rejected.');
        }

        // Check again at the point of approval. A record may have been edited,
        // or another assistance release may have been recorded after intake.
        $eligibility = $this->eligibilityFor($beneficiary, $beneficiary);
        if (! $eligibility['eligible'] && $beneficiary->eligibility_status !== 'Overridden') {
            $beneficiary->update([
                'eligibility_status' => 'Ineligible',
                'eligibility_checked_at' => now(),
            ]);

            return back()->with('error', 'This application is no longer eligible under the 90-day rule. A supervisor override is required before approval.');
        }

        $beneficiary->update(['status' => 'Approved', 'approved_at' => now(), 'approved_by' => $request->user()->id, 'eligibility_checked_at' => now()]);
        $beneficiary->intake?->update([
            'status' => 'approved',
            'workflow_stage' => 'approved',
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
        $this->logActivity('approved', 'MSWDO Head approved application for '.$beneficiary->name, $beneficiary);
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
        $beneficiary->update([
            'status' => 'Released',
            'released_at' => now(),
            'released_by' => $request->user()->id,
            // A missing assistance date is finalized when assistance is actually released.
            'date_of_assistance' => $beneficiary->date_of_assistance ?? now()->toDateString(),
        ]);
        $beneficiary->intake?->update([
            'status' => 'released',
            'workflow_stage' => 'released',
            'rejection_reason' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
        if (blank($beneficiary->latitude)) {
            $fallback = app(BeneficiaryGeocoder::class)->jitteredCentroidFor($beneficiary->barangay_id, $beneficiary->id);
            $fallback ??= self::TAGUDIN_MAP_FALLBACK;
            if ($fallback) {
                $beneficiary->forceFill([
                    'latitude' => $fallback['latitude'],
                    'longitude' => $fallback['longitude'],
                    'geocode_precision' => $beneficiary->barangay_id ? 'barangay_centroid' : 'municipal_centroid',
                    'geocoded_at' => now(),
                ])->saveQuietly();
                $this->notifyGeotaggedRelease($beneficiary);
            } else {
                $this->notifyGeotagFailure($beneficiary);
                GeocodeBeneficiary::dispatch($beneficiary->id);
            }
        }
        User::query()->where('role', 'admin')->where('status', 'approved')->get()->each(fn (User $user) => $user->notify(new WorkflowNotification('Beneficiary released', "{$beneficiary->name} has been marked released and is now an active AICS beneficiary.", route('beneficiaries.show', $beneficiary), 'success')));
        $this->logActivity('released', 'Marked as released for '.$beneficiary->name, $beneficiary);
        return back()->with('success', $beneficiary->name.' is now an active AICS beneficiary and will appear on the map.');
    }

    private function notifyGeotaggedRelease(Beneficiary $beneficiary): void
    {
        if ($beneficiary->released_by !== auth()->id() || auth()->user()->role !== 'focal_person') {
            return;
        }

        auth()->user()->notify(new WorkflowNotification(
            'Record successfully geotagged',
            "{$beneficiary->name} was released and successfully added to the map.",
            route('beneficiaries.show', $beneficiary),
            'geotag_success'
        ));
    }

    private function notifyGeotagFailure(Beneficiary $beneficiary): void
    {
        if ($beneficiary->released_by !== auth()->id() || auth()->user()->role !== 'focal_person') {
            return;
        }

        auth()->user()->notify(new WorkflowNotification(
            'Record unsuccessfully geotagged',
            "{$beneficiary->name} was released, but its location could not be added to the map.",
            route('beneficiaries.show', $beneficiary),
            'geotag_failure'
        ));
    }

    public function applicationForm(Beneficiary $beneficiary)
    {
        $this->authorize('view', $beneficiary);
        if (! in_array($beneficiary->status, ['Approved', 'Released', 'Completed'], true)
            || ! in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden', null], true)) {
            return back()->with('error', 'Only eligible applications can be printed.');
        }

        $beneficiary->load([
            'barangay',
            'assistanceType',
            'encoder',
            'overrideUser',
            'caseRecord.intake.barangay',
            'caseRecord.barangay',
            'caseRecord.householdMembers',
        ]);

        $finalAicsPayload = $this->finalAicsPayloadFromIntake($beneficiary);

        return Pdf::loadView('beneficiaries.application-form', compact('beneficiary', 'finalAicsPayload'))
            ->setPaper('a4', 'portrait')
            ->download('final-aics-form-'.$beneficiary->reference_code.'.pdf');
    }

    public function editApplicationForm(Beneficiary $beneficiary)
    {
        $this->authorize('updateFinalAics', $beneficiary);
        if (! in_array($beneficiary->status, ['Approved', 'Released', 'Completed'], true)
            || ! in_array($beneficiary->eligibility_status, ['Eligible', 'Overridden', null], true)) {
            return back()->with('error', 'Only eligible applications can use the Final AICS form.');
        }

        $beneficiary->load(['barangay', 'assistanceType', 'intake']);

        return view('beneficiaries.application-form-edit', [
            'beneficiary' => $beneficiary,
            'payload' => $this->finalAicsPayloadFromIntake($beneficiary),
        ]);
    }

    public function updateApplicationForm(Request $request, Beneficiary $beneficiary)
    {
        $this->authorize('updateFinalAics', $beneficiary);
        $data = $request->validate([
            'resettled' => ['nullable', 'boolean'],
            'disaster_victim' => ['nullable', 'in:ND,MD'],
            'date_of_occurrence' => ['nullable', 'date'],
            'estimated_damage' => ['nullable', 'string', 'max:255'],
            'casualty_name' => ['nullable', 'string', 'max:255'],
            'client_signature_name' => ['nullable', 'string', 'max:255'],
            'receipt_date' => ['nullable', 'date'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'receipt_amount' => ['nullable', 'numeric', 'min:0'],
            'receipt_purpose' => ['nullable', 'string', 'max:255'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'payee_address' => ['nullable', 'string', 'max:255'],
            'payee_tax_certificate' => ['nullable', 'string', 'max:100'],
            'payee_date_of_issue' => ['nullable', 'date'],
            'payee_place_of_issue' => ['nullable', 'string', 'max:255'],
            'witness_name' => ['nullable', 'string', 'max:255'],
        ]);

        $data = array_merge($beneficiary->final_aics_payload ?? [], $data);
        $data = array_merge($data, $this->finalAicsPayloadFromIntake($beneficiary));
        $beneficiary->forceFill(['final_aics_payload' => $data])->save();

        return redirect()->route('beneficiaries.application-form.edit', $beneficiary)
            ->with('success', 'Final AICS form draft saved. Review it, then print when ready.');
    }

    private function finalAicsPayloadFromIntake(Beneficiary $beneficiary): array
    {
        $intake = $beneficiary->intake;
        if (! $intake && $beneficiary->relationLoaded('caseRecord')) {
            $intake = $beneficiary->caseRecord?->intake;
        }
        if (! $intake) {
            return $beneficiary->final_aics_payload ?? [];
        }

        $address = $intake->address ?: trim(implode(', ', array_filter([
            $intake->barangay?->name,
            'Tagudin',
            'Ilocos Sur',
        ])));
        $clientName = $intake->client_name ?: $beneficiary->name;
        $beneficiaryName = $intake->beneficiary_name ?: $clientName;
        $assistance = $intake->purpose_of_assistance ?: $beneficiary->assistanceType?->name;
        $amount = $intake->requested_amount ?? $beneficiary->amount;

        return array_merge($beneficiary->final_aics_payload ?? [], [
            'resettled' => false,
            'disaster_victim' => 'ND',
            'date_of_occurrence' => $intake->intake_date?->format('Y-m-d'),
            'estimated_damage' => $intake->assistance_specification ?: $intake->diagnosis_or_cause_of_death,
            'casualty_name' => $intake->beneficiary_name,
            'client_signature_name' => $clientName,
            'receipt_date' => $intake->intake_date?->format('Y-m-d'),
            'receipt_amount' => $amount,
            'receipt_purpose' => $assistance,
            'payee_name' => $beneficiaryName,
            'payee_address' => $address,
            'witness_name' => $intake->interviewer_name,
        ]);
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

        $latest = (clone $duplicateQuery)->whereIn('status', ['Released', 'Completed'])->whereNotNull('date_of_assistance')
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
        if (! empty($payload['case_record_id'])) {
            $caseRecord = CaseRecord::findOrFail($payload['case_record_id']);
            $this->authorize('view', $caseRecord);
        }

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

        $manualLocation = $request->boolean('save_manual_location')
            && $request->filled('latitude')
            && $request->filled('longitude');
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
        [$beneficiary, $message, $action, $description] = DB::transaction(function () use ($payload, $beneficiary) {
            if ($beneficiary) {
                if (! $beneficiary->canTransitionTo($payload['status'])) {
                    throw ValidationException::withMessages(['status' => 'This status change is not valid for the current application state.']);
                }
                $beneficiary->fill($payload);
                $beneficiary->save();

                return [$beneficiary, 'Beneficiary record updated.', 'updated', 'Updated beneficiary record for '.$beneficiary->name];
            }

            if ($payload['status'] !== 'Pending') {
                throw ValidationException::withMessages(['status' => 'New applications must begin in Pending status.']);
            }
            $created = Beneficiary::create($payload);

            return [$created, 'Application submitted for review.', 'created', 'Submitted application for '.$created->name];
        });

        $this->syncStatusTimestamps($beneficiary);
        if (! $manualLocation && filled($beneficiary->address) && $beneficiary->geocode_precision !== 'manual') {
            GeocodeBeneficiary::dispatch($beneficiary->id)->delay(now()->addSecond());
        }
        $this->storeAttachments($request, $beneficiary);
        $this->logActivity($action, $description, $beneficiary);
        if ($manualLocation) {
            $this->logActivity('location_corrected', 'Manually corrected location for '.$beneficiary->name, $beneficiary);
        }

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

            $path = $file->store('attachments', 'sensitive');
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

    private function filteredQuery(Request $request, ?array $statusScope = null): Builder
    {
        $query = Beneficiary::query()->active();

        if ($statusScope !== null) {
            $query->whereIn('status', $statusScope);
        }

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }
        if ($request->filled('assistance_type_id')) {
            $query->where('assistance_type_id', $request->integer('assistance_type_id'));
        }
        if ($statusScope === null && $request->filled('status')) {
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

    private function mapCentroids($barangays): array
    {
        $centroids = BarangayCentroid::query()
            ->whereIn('psgc_code', $barangays->pluck('code')->filter())
            ->get(['psgc_code', 'latitude', 'longitude'])
            ->keyBy('psgc_code');

        return $barangays->mapWithKeys(function (Barangay $barangay) use ($centroids) {
            $centroid = $centroids->get($barangay->code);

            return [$barangay->id => $centroid ? [
                'latitude' => (float) $centroid->latitude,
                'longitude' => (float) $centroid->longitude,
            ] : null];
        })->all();
    }

    private function canOverrideEligibility(): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole('super_admin');
    }
}
