<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\ActivityLog;
use App\Models\CaseRecord;
use App\Models\DocumentRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProtectedFileController extends Controller
{
    public function attachment(Request $request, Attachment $attachment): StreamedResponse
    {
        $attachment->loadMissing(['beneficiary', 'intake']);
        $subject = $attachment->beneficiary ?? $attachment->intake;
        abort_unless($subject, 404);
        $this->authorize('view', $subject);

        $this->log($request, 'attachment_downloaded', $subject, 'Downloaded attachment '.$attachment->name);
        return $this->download($attachment->path, $attachment->name);
    }

    public function viewAttachment(Request $request, Attachment $attachment): Response
    {
        $attachment->loadMissing(['beneficiary', 'intake']);
        $subject = $attachment->beneficiary ?? $attachment->intake;
        abort_unless($subject, 404);
        $this->authorize('view', $subject);
        abort_unless(Storage::disk('sensitive')->exists($attachment->path), 404);

        $this->log($request, 'attachment_viewed', $subject, 'Viewed attachment '.$attachment->name);

        return response()->file(
            Storage::disk('sensitive')->path($attachment->path),
            ['Content-Disposition' => 'inline; filename="'.addslashes($attachment->name).'"']
        );
    }

    public function documentSnapshot(Request $request, CaseRecord $caseRecord, DocumentRecord $document): StreamedResponse
    {
        abort_unless($document->case_record_id === $caseRecord->id && filled($document->snapshot_path), 404);
        $this->authorize('view', $caseRecord);
        $this->log($request, 'document_snapshot_downloaded', $document, 'Downloaded finalized '.$document->template.' for '.$caseRecord->case_number);

        return $this->download($document->snapshot_path, $caseRecord->case_number.'-'.$document->template.'.pdf');
    }

    public function caseSignature(Request $request, CaseRecord $caseRecord): StreamedResponse
    {
        abort_unless(filled($caseRecord->client_signature_path), 404);
        $this->authorize('view', $caseRecord);
        $this->log($request, 'case_signature_downloaded', $caseRecord, 'Downloaded signed case record for '.$caseRecord->case_number);

        return $this->download($caseRecord->client_signature_path, 'signed-case-record-'.$caseRecord->case_number);
    }

    private function download(string $path, string $name): StreamedResponse
    {
        abort_unless(Storage::disk('sensitive')->exists($path), 404);

        return Storage::disk('sensitive')->download($path, $name);
    }

    private function log(Request $request, string $action, object $subject, string $description): void
    {
        ActivityLog::create(['user_id' => $request->user()->id, 'action' => $action, 'description' => $description, 'subject_type' => $subject::class, 'subject_id' => $subject->id]);
    }
}
