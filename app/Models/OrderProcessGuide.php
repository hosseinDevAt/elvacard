<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class OrderProcessGuide extends Model
{
    use HasFactory;

    public const WORKFLOW_BANK = 'bank_card';
    public const WORKFLOW_FUEL = 'fuel_card';

    public const WORKFLOWS = [
        self::WORKFLOW_BANK,
        self::WORKFLOW_FUEL,
    ];

    public const WORKFLOW_LABELS = [
        self::WORKFLOW_BANK => 'کارت بانکی فلزی',
        self::WORKFLOW_FUEL => 'کارت سوخت',
    ];

    /**
     * Optional override used exclusively in automated tests to simulate
     * un-migrated database environments.
     */
    public static ?bool $tablesExistOverride = null;

    protected $fillable = [
        'workflow',
        'title',
        'description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(OrderProcessStage::class, 'guide_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeStages(): HasMany
    {
        return $this->hasMany(OrderProcessStage::class, 'guide_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeForWorkflow(Builder $query, string $workflow): Builder
    {
        return $query->where('workflow', $workflow);
    }

    /**
     * Determine whether the underlying tables exist in the database.
     * Prevents 500 errors across storefront and admin if migrations have not run yet.
     */
    public static function tablesExist(): bool
    {
        if (static::$tablesExistOverride !== null) {
            return static::$tablesExistOverride;
        }

        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::hasTable('order_process_guides')
                && Schema::hasTable('order_process_stages');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }

    /**
     * Reset table existence state (useful during testing or cache clearance).
     */
    public static function resetTableExistenceCache(): void
    {
        static::$tablesExistOverride = null;
    }

    public static function findOrCreateForWorkflow(string $workflow): ?self
    {
        if (! in_array($workflow, self::WORKFLOWS, true)) {
            throw new \InvalidArgumentException("Invalid workflow: {$workflow}");
        }

        if (! static::tablesExist()) {
            return null;
        }

        return static::firstOrCreate(
            ['workflow' => $workflow],
            [
                'title' => $workflow === self::WORKFLOW_BANK ? 'راهنمای مراحل سفارش کارت فلزی' : 'راهنمای مراحل سفارش کارت سوخت',
                'description' => null,
                'is_published' => false,
            ]
        );
    }
}
