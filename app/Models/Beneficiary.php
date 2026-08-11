<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'case_record_id',
        'age',
        'sex',
        'birth_date',
        'contact',
        'barangay_id',
        'assistance_type_id',
        'address',
        'latitude',
        'longitude',
        'geocode_precision',
        'geocoded_at',
        'case_info',
        'amount',
        'date_of_assistance',
        'status',
        'reference_code',
        'history_key',
        'eligibility_status',
        'eligibility_checked_at',
        'override_by',
        'override_justification',
        'override_at',
        'approved_at',
        'released_at',
        'completed_at',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'archived_at',
        'encoded_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'date_of_assistance' => 'date',
        'eligibility_checked_at' => 'datetime',
        'override_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'archived_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'geocoded_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Beneficiary $beneficiary) {
            $beneficiary->syncIdentityFields();
        });
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    public function caseRecord()
    {
        return $this->belongsTo(CaseRecord::class);
    }

    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class);
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function overrideUser()
    {
        return $this->belongsTo(User::class, 'override_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function historyEntries()
    {
        return $this->hasMany(self::class, 'history_key', 'history_key')
            ->orderByDesc('date_of_assistance')
            ->orderByDesc('created_at');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeForIdentity($query, array $identity)
    {
        return $query->where(function ($builder) use ($identity) {
            $builder->whereRaw('LOWER(name) = ?', [Str::lower(trim($identity['name'] ?? ''))]);

            if (! empty($identity['birth_date'])) {
                $builder->whereDate('birth_date', $identity['birth_date']);
            }

            if (! empty($identity['contact'])) {
                $builder->whereRaw("REPLACE(REPLACE(REPLACE(contact, '-', ''), ' ', ''), '.', '') = ?", [$this->normalizeContact($identity['contact'])]);
            }
        });
    }

    public function syncIdentityFields(): void
    {
        if (blank($this->birth_date) && filled($this->age)) {
            $this->birth_date = Carbon::now()->subYears((int) $this->age)->toDateString();
        }

        if (blank($this->reference_code)) {
            $this->reference_code = 'AICS-'.Str::ulid()->toBase32();
        }

        $this->history_key = $this->makeHistoryKey();
    }

    public function makeHistoryKey(): string
    {
        return hash('sha1', implode('|', [
            Str::lower(trim((string) $this->name)),
            optional($this->birth_date)->format('Y-m-d') ?? '',
            $this->normalizeContact($this->contact),
            Str::lower(trim((string) $this->address)),
            (string) $this->barangay_id,
        ]));
    }

    public function normalizeContact(?string $contact): string
    {
        return preg_replace('/\D+/', '', (string) $contact) ?: '';
    }

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return true;
        }

        return in_array($status, [
            'Pending' => ['Approved', 'Rejected'],
            'Approved' => ['Released'],
            'Released' => ['Completed'],
            'Completed' => [],
            'Rejected' => [],
        ][$this->status] ?? [], true);
    }
}
