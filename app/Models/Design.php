<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'sort_order',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active' => 'boolean',
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
}
