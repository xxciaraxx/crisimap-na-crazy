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
}
