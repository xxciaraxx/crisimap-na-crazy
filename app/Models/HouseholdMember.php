<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseholdMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'relationship_to_client', 'birth_date', 'age', 'sex', 'civil_status', 'religion',
        'educational_attainment', 'occupation', 'income', 'remarks',
    ];

    protected $casts = ['birth_date' => 'date', 'income' => 'decimal:2'];

    public function caseRecord() { return $this->belongsTo(CaseRecord::class); }
}
