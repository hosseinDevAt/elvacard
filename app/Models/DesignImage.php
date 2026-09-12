<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignImage extends Model
{
    protected $fillable = [
        'design_id',
        'color_id',
        'image_path',
        'alt_text',
        'image_title',
        'optimized_filename',
        'seo_caption',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function compatibilities(): HasMany
    {
        return $this->hasMany(DesignColorCompatibility::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}