<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_public',
        'updated_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Invalidate the public settings cache after any persist/delete
     * so site_setting() never serves stale values.
     */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.public'));
        static::deleted(fn () => Cache::forget('settings.public'));
    }
}
