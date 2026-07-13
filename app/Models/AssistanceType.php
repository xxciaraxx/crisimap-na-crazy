<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'description'];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }
}
