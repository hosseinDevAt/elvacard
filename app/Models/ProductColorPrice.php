<?php

namespace App\Models;

use App\Enums\CustomizationWorkflowEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductColorPrice extends Model
{
    protected $fillable = [
        'product_id',
        'customization_workflow',
        'color_id',
        'price',
        'is_active',
    ];

    protected $casts = [
        'customization_workflow' => CustomizationWorkflowEnum::class,
        'is_active' => 'boolean',
    ];

    public static function fuelActiveCount(?int $excludeId = null, ?int $productId = null): int
    {
        return self::query()
            ->where(function ($q) use ($productId) {
                $q->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value);
                if ($productId !== null) {
                    $q->orWhere('product_id', $productId);
                }
            })
            ->where('is_active', true)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->count();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'color_id', 'color_id')
            ->when(
                $this->customization_workflow !== null,
                fn ($q) => $q->where('customization_workflow', $this->customization_workflow instanceof CustomizationWorkflowEnum ? $this->customization_workflow->value : (string) $this->customization_workflow)
            )
            ->when(
                $this->product_id !== null,
                fn ($q) => $q->where('product_id', $this->product_id)
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeForWorkflow($query, CustomizationWorkflowEnum|string $workflow)
    {
        $val = $workflow instanceof CustomizationWorkflowEnum ? $workflow->value : $workflow;

        return $query->where('customization_workflow', $val);
    }
}
