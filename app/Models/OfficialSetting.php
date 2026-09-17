<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public const KEYS = [
        'municipality',
        'office_address',
        'mayor_name',
        'mayor_title',
        'mswdo_head_name',
        'mswdo_head_title',
        'social_worker_name',
        'social_worker_title',
        'template_version',
    ];

    public static function values(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }
}
