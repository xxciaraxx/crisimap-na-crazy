<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'age',
        'sex',
        'contact',
        'barangay_id',
        'assistance_type_id',
        'address',
        'latitude',
        'longitude',
        'case_info',
        'date_of_assistance',
        'status',
        'encoded_by',
    ];

    protected $casts = [
        'date_of_assistance' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class);
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
