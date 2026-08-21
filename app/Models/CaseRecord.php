<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_number', 'date_of_entry', 'client_name', 'barangay_id', 'problem_presented',
        'house_structure', 'water_source', 'electricity', 'is_government_program_member', 'government_programs', 'other_government_program',
        'general_intake_sheet_id', 'client_signature_path', 'encoded_by', 'status', 'rejection_reason',
    ];

    protected $casts = [
        'date_of_entry' => 'date',
        'is_government_program_member' => 'boolean',
        'government_programs' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CaseRecord $record) {
            if ($record->case_number) {
                return;
            }

            $prefix = 'TAG-AICS-'.now()->format('Y').'-';
            $lastNumber = static::query()
                ->where('case_number', 'like', $prefix.'%')
                ->pluck('case_number')
                ->map(fn (string $number) => (int) substr($number, strlen($prefix)))
                ->max() ?? 0;

            $record->case_number = $prefix.str_pad((string) ($lastNumber + 1), 5, '0', STR_PAD_LEFT);
        });
    }

    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function encoder() { return $this->belongsTo(User::class, 'encoded_by'); }
    public function householdMembers() { return $this->hasMany(HouseholdMember::class); }
    public function beneficiaries() { return $this->hasMany(Beneficiary::class); }
    public function intake() { return $this->belongsTo(GeneralIntakeSheet::class, 'general_intake_sheet_id'); }
    public function documents() { return $this->hasMany(DocumentRecord::class); }

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return true;
        }

        return in_array($status, [
            'submitted' => ['approved', 'rejected'],
            'approved' => [],
            'rejected' => [],
        ][$this->status] ?? [], true);
    }
}
