<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralIntakeSheet extends Model
{
    use HasFactory;

    protected $fillable = ['intake_number', 'intake_date', 'program', 'client_type', 'client_name', 'beneficiary_name', 'age', 'sex', 'civil_status', 'occupation', 'monthly_income', 'contact', 'barangay_id', 'address', 'purpose_of_assistance', 'requested_amount', 'diagnosis_or_cause_of_death', 'client_sectors', 'income_resources', 'monthly_expenses', 'supporting_documents', 'social_worker_assessment', 'applicant_signature_path', 'interviewer_name', 'interviewer_position', 'encoded_by', 'status'];
    protected $casts = ['intake_date' => 'date', 'client_sectors' => 'array', 'income_resources' => 'array', 'supporting_documents' => 'array', 'monthly_income' => 'decimal:2', 'requested_amount' => 'decimal:2', 'monthly_expenses' => 'decimal:2'];

    protected static function booted(): void
    {
        static::creating(function (self $intake) {
            if (! $intake->intake_number) $intake->intake_number = 'GIS-'.now()->format('Y').'-'.str_pad((string) ((static::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
        });
    }

    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function encoder() { return $this->belongsTo(User::class, 'encoded_by'); }
    public function caseRecord() { return $this->hasOne(CaseRecord::class); }
    public function documents() { return $this->hasMany(DocumentRecord::class); }
}
