<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['beneficiary_id', 'general_intake_sheet_id', 'path', 'name', 'document_type', 'uploaded_by'];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function intake()
    {
        return $this->belongsTo(GeneralIntakeSheet::class, 'general_intake_sheet_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
