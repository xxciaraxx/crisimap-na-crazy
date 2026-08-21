<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Barangay;
use App\Models\GeneralIntakeSheet;
use Illuminate\Http\Request;

class GeneralIntakeSheetController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', GeneralIntakeSheet::class);
        $intakes = GeneralIntakeSheet::query()->with(['barangay', 'encoder', 'caseRecord'])
            ->when($request->user()->role === 'social_officer', fn ($query) => $query->where('encoded_by', $request->user()->id))
            ->latest('intake_date')->latest()->paginate(20);
        return view('intakes.index', compact('intakes'));
    }

    public function create()
    {
        $this->authorize('create', GeneralIntakeSheet::class);
        return view('intakes.create', ['barangays' => Barangay::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', GeneralIntakeSheet::class);
        $intake = GeneralIntakeSheet::create($this->payload($request) + ['encoded_by' => $request->user()->id, 'status' => 'draft']);
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'intake_created', 'description' => "Created General Intake Sheet {$intake->intake_number}", 'subject_type' => GeneralIntakeSheet::class, 'subject_id' => $intake->id]);
        return redirect()->route('intakes.show', $intake)->with('success', 'General Intake Sheet saved. Create its supporting Case Record when ready.');
    }

    public function show(GeneralIntakeSheet $intake)
    {
        $this->authorize('view', $intake);
        $intake->load(['barangay', 'encoder', 'caseRecord.beneficiaries.assistanceType']);
        return view('intakes.show', compact('intake'));
    }

    public function edit(GeneralIntakeSheet $intake)
    {
        $this->authorize('update', $intake);
        return view('intakes.edit', ['intake' => $intake, 'barangays' => Barangay::orderBy('name')->get()]);
    }

    public function update(Request $request, GeneralIntakeSheet $intake)
    {
        $this->authorize('update', $intake);
        $intake->update($this->payload($request));
        return redirect()->route('intakes.show', $intake)->with('success', 'General Intake Sheet updated.');
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'intake_date' => ['required', 'date'], 'program' => ['required', 'in:AICS,ESA,Others'], 'client_type' => ['required', 'in:new,returning'],
            'client_name' => ['required', 'string', 'max:255'], 'beneficiary_name' => ['nullable', 'string', 'max:255'], 'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'sex' => ['nullable', 'in:Male,Female,Other'], 'civil_status' => ['nullable', 'string', 'max:100'], 'occupation' => ['nullable', 'string', 'max:255'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'], 'contact' => ['nullable', 'string', 'max:100'], 'barangay_id' => ['nullable', 'exists:barangays,id'], 'address' => ['nullable', 'string'],
            'purpose_of_assistance' => ['nullable', 'string'], 'requested_amount' => ['nullable', 'numeric', 'min:0'], 'diagnosis_or_cause_of_death' => ['nullable', 'string'],
            'client_sectors' => ['nullable', 'array'], 'income_resources' => ['nullable', 'array'], 'monthly_expenses' => ['nullable', 'numeric', 'min:0'],
            'supporting_documents' => ['nullable', 'array'], 'social_worker_assessment' => ['nullable', 'string'], 'interviewer_name' => ['nullable', 'string', 'max:255'], 'interviewer_position' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
