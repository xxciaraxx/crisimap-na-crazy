<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRecord extends Model
{
    use HasFactory;

    public const TEMPLATES = ['general_intake_sheet' => 'General Intake Sheet', 'case_record' => 'Case Record', 'application_form' => 'Application Form', 'certificate_of_eligibility' => 'Certificate of Eligibility'];
    protected $fillable = ['general_intake_sheet_id', 'case_record_id', 'beneficiary_id', 'template', 'template_version', 'status', 'payload', 'notes', 'prepared_by', 'finalized_by', 'finalized_at', 'snapshot_path'];
    protected $casts = ['payload' => 'array', 'finalized_at' => 'datetime'];
    public function intake() { return $this->belongsTo(GeneralIntakeSheet::class, 'general_intake_sheet_id'); }
    public function caseRecord() { return $this->belongsTo(CaseRecord::class); }
    public function beneficiary() { return $this->belongsTo(Beneficiary::class); }
    public function preparedBy() { return $this->belongsTo(User::class, 'prepared_by'); }
    public function finalizedBy() { return $this->belongsTo(User::class, 'finalized_by'); }
}
