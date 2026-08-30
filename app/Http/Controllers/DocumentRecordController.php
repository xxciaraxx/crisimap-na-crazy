<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CaseRecord;
use App\Models\DocumentRecord;
use App\Models\OfficialSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentRecordController extends Controller
{
    public function index(CaseRecord $caseRecord)
    {
        $this->authorize('view', $caseRecord);
        $caseRecord->load(['intake', 'beneficiaries.assistanceType', 'documents']);
        foreach (DocumentRecord::TEMPLATES as $template => $label) {
            $this->documentFor($caseRecord, $template);
        }
        return view('documents.index', ['caseRecord' => $caseRecord->fresh(['intake', 'beneficiaries.assistanceType', 'documents'])]);
    }

    public function edit(CaseRecord $caseRecord, DocumentRecord $document)
    {
        abort_unless($document->case_record_id === $caseRecord->id, 404);
        $this->authorize('view', $caseRecord);
        return view('documents.edit', ['caseRecord' => $caseRecord->load(['intake', 'beneficiaries.assistanceType']), 'document' => $document, 'settings' => OfficialSetting::values()]);
    }

    public function update(Request $request, CaseRecord $caseRecord, DocumentRecord $document)
    {
        abort_unless($document->case_record_id === $caseRecord->id, 404);
        $this->authorize('update', $document);
        $data = $request->validate(['fields' => ['nullable', 'array'], 'notes' => ['nullable', 'string', 'max:5000']]);
        $document->update(['payload' => $data['fields'] ?? [], 'notes' => $data['notes'] ?? null, 'prepared_by' => $request->user()->id, 'status' => 'ready_for_review']);
        return redirect()->route('case-records.documents.edit', [$caseRecord, $document])->with('success', 'Document draft saved for review.');
    }

    public function preview(CaseRecord $caseRecord, DocumentRecord $document)
    {
        abort_unless($document->case_record_id === $caseRecord->id, 404);
        $this->authorize('view', $document);
        return view('documents.preview', $this->viewData($caseRecord, $document));
    }

    public function finalize(Request $request, CaseRecord $caseRecord, DocumentRecord $document)
    {
        abort_unless($document->case_record_id === $caseRecord->id, 404);
        $this->authorize('finalize', $document);
        $data = $this->viewData($caseRecord, $document);
        $orientation = in_array($document->template, ['case_record', 'application_form'], true) ? 'landscape' : 'portrait';
        $pdf = Pdf::loadView('documents.print', $data)->setPaper('a4', $orientation)->output();
        $path = 'document-snapshots/'.$caseRecord->id.'/'.$document->template.'-'.$document->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::disk('sensitive')->put($path, $pdf);
        $document->update(['status' => 'finalized', 'template_version' => OfficialSetting::values()['template_version'] ?? '1.0', 'finalized_by' => $request->user()->id, 'finalized_at' => now(), 'snapshot_path' => $path]);
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => 'document_finalized', 'description' => "Finalized {$document->template} for {$caseRecord->case_number}", 'subject_type' => DocumentRecord::class, 'subject_id' => $document->id]);
        return redirect()->route('case-records.documents.index', $caseRecord)->with('success', 'Immutable PDF snapshot finalized and ready to print.');
    }

    private function documentFor(CaseRecord $case, string $template): DocumentRecord
    {
        return DocumentRecord::firstOrCreate(['case_record_id' => $case->id, 'template' => $template], ['general_intake_sheet_id' => $case->general_intake_sheet_id, 'beneficiary_id' => $case->beneficiaries()->first()?->id, 'prepared_by' => auth()->id(), 'template_version' => OfficialSetting::values()['template_version'] ?? '1.0']);
    }

    private function viewData(CaseRecord $case, DocumentRecord $document): array
    {
        $case->loadMissing(['intake.barangay', 'barangay', 'householdMembers', 'beneficiaries.assistanceType']);
        return ['caseRecord' => $case, 'intake' => $case->intake, 'beneficiary' => $case->beneficiaries->first(), 'document' => $document, 'settings' => OfficialSetting::values(), 'fields' => $document->payload ?? []];
    }
}
