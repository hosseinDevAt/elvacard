<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder;

class Color extends Model
{
    protected $fillable = [
        'name',
        'code_hex',
        'preview_image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function productColorPrices()
    {
        return $this->hasMany(ProductColorPrice::class);
    }

    public function designImages()
    {
        return $this->hasMany(DesignImage::class);
    }

    public function designColorCompatibilities()
    {
        return $this->hasMany(DesignColorCompatibility::class, 'card_color_id');
    }
}