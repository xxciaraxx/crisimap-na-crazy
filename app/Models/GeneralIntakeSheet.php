<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralIntakeSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'intake_date',
        'program',
        'client_type',
        'client_name',
        'beneficiary_name',
        'age',
        'sex',
        'civil_status',
        'occupation',
        'monthly_income',
        'contact',
        'barangay_id',
        'address',
        'purpose_of_assistance',
        'assistance_specification',
        'requested_amount',
        'diagnosis_or_cause_of_death',
        'family_composition',
        'client_sectors',
        'income_resources',
        'combined_family_income',
        'employed_members_count',
        'seasonal_members_count',
        'monthly_expenses',
        'supporting_documents',
        'social_worker_assessment',
        'applicant_signature_path',
        'interviewer_name',
        'interviewer_position',
        'encoded_by',
        'status',
        'workflow_stage',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];
    protected $casts = [
        'intake_date' => 'date',
        'family_composition' => 'array',
        'client_sectors' => 'array',
        'income_resources' => 'array',
        'supporting_documents' => 'array',
        'monthly_income' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'combined_family_income' => 'decimal:2',
        'employed_members_count' => 'integer',
        'seasonal_members_count' => 'integer',
        'monthly_expenses' => 'decimal:2',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $intake) {
            if (! $intake->intake_number) {
                $year = $intake->intake_date
                    ? date('Y', strtotime((string) $intake->intake_date))
                    : now()->format('Y');
                $prefix = 'TAG-AICS-'.$year.'-';
                $lastSequence = static::query()
                    ->where('intake_number', 'like', 'TAG-AICS-%')
                    ->pluck('intake_number')
                    ->map(fn (string $number) => (int) substr($number, -5))
                    ->max() ?? 0;

                $intake->intake_number = $prefix.str_pad((string) ($lastSequence + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function (self $intake): void {
            if ($intake->isDirty('intake_number')) {
                $intake->intake_number = $intake->getOriginal('intake_number');
            }
        });
    }

    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function encoder() { return $this->belongsTo(User::class, 'encoded_by'); }
    public function rejectedBy() { return $this->belongsTo(User::class, 'rejected_by'); }
    public function beneficiaries() { return $this->hasMany(Beneficiary::class); }
    public function documents() { return $this->hasMany(DocumentRecord::class); }

    public function statusLabel(): string
    {
        return match ($this->workflow_stage) {
            'eligibility_review' => 'Waiting for the Focal Person to check eligibility',
            'head_approval' => 'Waiting for the MSWDO Head\'s approval',
            'approved' => 'Approved, waiting for the funds to be released',
            'released' => 'Released',
            'rejected' => 'Rejected',
            default => match ($this->status) {
                'submitted' => 'Waiting for the Focal Person to check eligibility',
                'approved' => 'Approved, waiting for the funds to be released',
                'released' => 'Released',
                'rejected' => 'Rejected',
                default => ucfirst(str_replace('_', ' ', (string) $this->status)),
            },
        };
    }
}
