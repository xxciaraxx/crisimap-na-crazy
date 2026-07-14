<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = ['beneficiary_id', 'path', 'name', 'document_type', 'uploaded_by'];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
