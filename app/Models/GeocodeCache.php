<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeocodeCache extends Model
{
    protected $table = 'geocode_cache';

    public $timestamps = false;

    protected $fillable = [
        'query',
        'lat',
        'lon',
        'display_name',
        'raw_response',
        'created_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'raw_response' => 'array',
        'created_at' => 'datetime',
    ];
}
