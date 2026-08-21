<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function values(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }
}
