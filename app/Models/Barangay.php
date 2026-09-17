<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'notes'];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function caseRecords()
    {
        return $this->hasMany(CaseRecord::class);
    }

    public function intakes()
    {
        return $this->hasMany(GeneralIntakeSheet::class);
    }
}
