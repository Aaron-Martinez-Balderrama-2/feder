<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $primaryKey = 'setting_key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function getVal(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::find($key);
            return $setting ? $setting->setting_value : $default;
        });
    }

    public static function setVal(string $key, ?string $value): void
    {
        Cache::forget("setting_{$key}");

        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value ?? '']
        );
    }
}
