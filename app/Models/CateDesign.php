<?php

namespace App\Models;

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
        'sort_order',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}