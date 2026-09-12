<?php

namespace App\Models;

use App\Enums\ProductTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'type',
        'name',
        'slug',
        'description',
        'main_image',
        'base_price',
        'supports_chip_selection',
        'design_config',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots_index',
        'og_image',
        'seo_content',
        'is_active',
    ];

    protected $casts = [
        'type' => ProductTypeEnum::class,
        'design_config' => 'array',
        'supports_chip_selection' => 'boolean',
        'robots_index' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function colorPrices()
    {
        return $this->hasMany(ProductColorPrice::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, ProductTypeEnum|string $type): Builder
    {
        return $query->where('type', $type instanceof ProductTypeEnum ? $type->value : $type);
    }

    public function scopeWithCatalog(Builder $query): Builder
    {
        return $query->with([
            'colorPrices' => fn ($q) => $q
                ->where('is_active', true)
                ->with(['color' => fn ($cq) => $cq->active()->orderBy('sort_order')])
                ->orderBy('price'),
        ]);
    }
}
