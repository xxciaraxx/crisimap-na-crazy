<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_number', 'date_of_entry', 'client_name', 'barangay_id', 'problem_presented',
        'house_structure', 'water_source', 'electricity', 'client_signature_path', 'encoded_by',
    ];

    protected $casts = ['date_of_entry' => 'date'];

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
}
