<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class MaintenanceModeManager
{
    protected string $filePath;

    protected ?bool $memoizedStatus = null;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? storage_path('framework/maintenance_mode.json');
    }

    /**
     * Check whether maintenance mode is currently active.
     * Checks the local flag file first (resilient, zero DB queries),
     * and falls back to SiteSetting in the database if the file is absent.
     */
    public function isActive(): bool
    {
        if ($this->memoizedStatus !== null) {
            return $this->memoizedStatus;
        }

        if (file_exists($this->filePath)) {
            $content = @file_get_contents($this->filePath);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data) && isset($data['active'])) {
                    return $this->memoizedStatus = (bool) $data['active'];
                }
            }
        }

        try {
            $setting = SiteSetting::query()->where('key', 'maintenance_mode')->first();
            if ($setting !== null) {
                $active = in_array((string) $setting->value, ['1', 'true'], true);
                $this->writeFlagFile($active, $setting->updated_at?->toIso8601String());

                return $this->memoizedStatus = $active;
            }
        } catch (\Throwable) {
            // If the database is temporarily unreachable, default safely to false
        }

        return $this->memoizedStatus = false;
    }

    /**
     * Enable maintenance mode.
     */
    public function enable(?string $actor = null): void
    {
        $now = now();

        try {
            SiteSetting::query()->updateOrCreate(
                ['key' => 'maintenance_mode'],
                [
                    'value' => '1',
                    'type' => 'boolean',
                    'group' => 'system',
                    'is_public' => true,
                    'updated_at' => $now,
                ]
            );
        } catch (\Throwable $e) {
            // Record failure to avoid silent failures
            throw new \RuntimeException('خطا در ذخیره‌سازی وضعیت حالت تعمیر در پایگاه داده: '.$e->getMessage(), 0, $e);
        }

        $this->writeFlagFile(true, $now->toIso8601String(), $actor);
        $this->clearCache();
        $this->memoizedStatus = true;
    }

    /**
     * Disable maintenance mode.
     */
    public function disable(): void
    {
        $now = now();

        try {
            SiteSetting::query()->updateOrCreate(
                ['key' => 'maintenance_mode'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'system',
                    'is_public' => true,
                    'updated_at' => $now,
                ]
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException('خطا در غیرفعال‌سازی حالت تعمیر در پایگاه داده: '.$e->getMessage(), 0, $e);
        }

        $this->writeFlagFile(false, $now->toIso8601String());
        $this->clearCache();
        $this->memoizedStatus = false;
    }

    /**
     * Retrieve status details (e.g. human-readable Jalali timestamp of the last change).
     */
    public function getInfo(): ?string
    {
        if (file_exists($this->filePath)) {
            $content = @file_get_contents($this->filePath);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data) && ! empty($data['timestamp'])) {
                    try {
                        $carbon = Carbon::parse($data['timestamp']);

                        return function_exists('jalali_date')
                            ? jalali_date($carbon, 'datetime')
                            : $carbon->toDateTimeString();
                    } catch (\Throwable) {
                        return null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Synchronize the flag file when a SiteSetting instance is updated directly.
     */
    public function syncFromSetting(SiteSetting $setting): void
    {
        if ($setting->key !== 'maintenance_mode') {
            return;
        }

        $active = in_array((string) $setting->value, ['1', 'true'], true);
        $this->writeFlagFile($active, $setting->updated_at?->toIso8601String() ?? now()->toIso8601String());
        $this->clearCache();
        $this->memoizedStatus = $active;
    }

    /**
     * Reset memoized status (useful in tests).
     */
    public function resetMemoizedStatus(): void
    {
        $this->memoizedStatus = null;
    }

    protected function writeFlagFile(bool $active, ?string $timestamp = null, ?string $actor = null): void
    {
        $dir = dirname($this->filePath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'active' => $active,
            'timestamp' => $timestamp ?? now()->toIso8601String(),
            'actor' => $actor,
        ];

        @file_put_contents($this->filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    protected function clearCache(): void
    {
        SiteSetting::clearRuntimeCache();
        Cache::forget('settings.public');
    }
}
