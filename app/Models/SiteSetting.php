<?php

namespace App\Models;

use App\Services\MaintenanceModeManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * In-memory request-level cache of public settings.
     *
     * @var array<string, array{value: mixed, type: string}>|null
     */
    protected static ?array $runtimeCache = null;

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

    public static function getRuntimeCache(): ?array
    {
        return static::$runtimeCache;
    }

    public static function setRuntimeCache(array $settings): void
    {
        static::$runtimeCache = $settings;
    }

    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = null;
    }

    /**
     * Invalidate the public settings cache after any persist/delete
     * so site_setting() never serves stale values.
     */
    protected static function booted(): void
    {
        static::saved(function (SiteSetting $setting) {
            static::clearRuntimeCache();
            Cache::forget('settings.public');

            if ($setting->key === 'maintenance_mode') {
                app(MaintenanceModeManager::class)->syncFromSetting($setting);
            }
        });

        static::deleted(function (SiteSetting $setting) {
            static::clearRuntimeCache();
            Cache::forget('settings.public');

            if ($setting->key === 'maintenance_mode') {
                app(MaintenanceModeManager::class)->disable();
            }
        });
    }
}
