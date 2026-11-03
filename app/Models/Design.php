<?php

namespace App\Models;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\DesignWorkspaceVisibilityEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'cate_design_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots_index',
        'seo_content',
        'is_active',
        'workspace_visibility',
        'sort_order',
        'is_popular',
        'popular_sort_order',
        'featured_image_path',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active' => 'boolean',
        'workspace_visibility' => DesignWorkspaceVisibilityEnum::class,
        'is_popular' => 'boolean',
        'popular_sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CateDesign::class, 'cate_design_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(DesignImage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->where('is_popular', true);
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

    /**
     * Resolves the authoritative promotional thumbnail path for featured design cards.
     * Priority:
     * 1. Valid custom featured thumbnail if design is popular and file exists on public disk.
     * 2. Automatic thumbnail from design images (first active image according to sort order).
     * 3. Null (calling views handle placeholder fallback).
     */
    public function resolveFeaturedThumbnailPath(): ?string
    {
        if ($this->is_popular && $this->hasCustomFeaturedThumbnail()) {
            return $this->featured_image_path;
        }

        if (! empty($this->preview_image_path)) {
            return $this->preview_image_path;
        }

        if ($this->relationLoaded('images')) {
            $image = $this->images->firstWhere('is_active', true) ?? $this->images->first();
            if ($image && ! empty($image->image_path)) {
                return $image->image_path;
            }
        }

        return $this->images()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('image_path')
            ?? $this->images()->orderBy('sort_order')->value('image_path');
    }

    public function hasCustomFeaturedThumbnail(): bool
    {
        return ! empty($this->featured_image_path) && Storage::disk('public')->exists($this->featured_image_path);
    }

    public function isUsingCustomFeaturedThumbnail(): bool
    {
        return (bool) $this->is_popular && $this->hasCustomFeaturedThumbnail();
    }
}
