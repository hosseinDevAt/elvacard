<?php

namespace App\Models;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\DesignWorkspaceVisibilityEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CateDesign extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image_path',
        'meta_title',
        'meta_description',
        'top_description',
        'bottom_description',
        'robots_index',
        'is_active',
        'workspace_visibility',
        'sort_order',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active' => 'boolean',
        'workspace_visibility' => DesignWorkspaceVisibilityEnum::class,
    ];

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForWorkflow(Builder $query, ?CustomizationWorkflowEnum $workflow): Builder
    {
        if ($workflow === CustomizationWorkflowEnum::BANK_CARD) {
            return $query->where(function ($q) {
                $q->whereIn('workspace_visibility', [
                    DesignWorkspaceVisibilityEnum::BANK->value,
                    DesignWorkspaceVisibilityEnum::BOTH->value,
                ])->orWhereNull('workspace_visibility');
            });
        }

        if ($workflow === CustomizationWorkflowEnum::FUEL_CARD) {
            return $query->where(function ($q) {
                $q->whereIn('workspace_visibility', [
                    DesignWorkspaceVisibilityEnum::FUEL->value,
                    DesignWorkspaceVisibilityEnum::BOTH->value,
                ])->orWhereNull('workspace_visibility');
            });
        }

        return $query;
    }

    public function isAvailableForWorkflow(?CustomizationWorkflowEnum $workflow): bool
    {
        $val = $this->workspace_visibility instanceof DesignWorkspaceVisibilityEnum
            ? $this->workspace_visibility->value
            : (string) ($this->workspace_visibility ?? 'both');

        if ($val === 'both' || empty($val)) {
            return true;
        }

        if ($workflow === CustomizationWorkflowEnum::BANK_CARD && $val === 'bank') {
            return true;
        }

        if ($workflow === CustomizationWorkflowEnum::FUEL_CARD && $val === 'fuel') {
            return true;
        }

        return false;
    }
}
