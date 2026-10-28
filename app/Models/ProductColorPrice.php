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

    public static function fuelActiveCount(?int $productId = null, ?int $excludePriceId = null): int
    {
        $query = self::query()
            ->where('is_active', true)
            ->when($excludePriceId !== null, fn ($q) => $q->where('id', '!=', $excludePriceId));

        if ($productId !== null) {
            return $query->where(function ($q) use ($productId) {
                $q->where('product_id', $productId);
                $product = Product::query()->find($productId);
                if ($product && (
                    $product->type === \App\Enums\ProductTypeEnum::FUEL
                    || (string) $product->getRawOriginal('customization_workflow') === CustomizationWorkflowEnum::FUEL_CARD->value
                )) {
                    $q->orWhere('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value);
                }
            })->count();
        }

        return $query->where(function ($q) {
            $q->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
              ->orWhereHas('product', fn ($pq) => $pq->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value));
        })->count();
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
                fn ($q) => $q->where(function ($sub) {
                    $workflow = $this->customization_workflow instanceof CustomizationWorkflowEnum
                        ? $this->customization_workflow->value
                        : (string) $this->customization_workflow;
                    $sub->where('customization_workflow', $workflow);
                    if ($this->product_id !== null) {
                        $sub->orWhere('product_id', $this->product_id);
                    }
                }),
                fn ($q) => $q->when(
                    $this->product_id !== null,
                    fn ($sub) => $sub->where('product_id', $this->product_id)
                )
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
