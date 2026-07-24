<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangayCentroid extends Model
{
    protected $fillable = [
        'psgc_code', 'barangay', 'latitude', 'longitude', 'centroid_latitude', 'centroid_longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'centroid_latitude' => 'float',
        'centroid_longitude' => 'float',
    ];
}
