<?php

namespace App\Models;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'type',
        'customization_workflow',
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
        'customization_workflow' => CustomizationWorkflowEnum::class,
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

    /**
     * The single storefront purchasing gate. A product is purchasable only when
     * the checkout can actually charge it: a commerce product needs a base
     * price or an active color price path, and a card product (active, launched
     * workflow) additionally needs a purchasable design path. Fuel products
     * keep their exactly-one active price invariant. Mirrors
     * ProductPurchaseabilityService::isPurchasable so every storefront list and
     * the product detail gate stay on the same authority.
     */
    public function scopePurchasable(Builder $query): Builder
    {
        $activeColorPrice = 'EXISTS (
            SELECT 1 FROM product_color_prices AS pcp
            WHERE pcp.product_id = products.id
              AND pcp.is_active = 1
              AND EXISTS (
                  SELECT 1 FROM colors AS c
                  WHERE c.id = pcp.color_id AND c.is_active = 1
              )
        )';

        $purchasableDesign = 'EXISTS (
            SELECT 1 FROM designs AS d
            WHERE d.is_active = 1
              AND EXISTS (
                  SELECT 1 FROM cate_designs AS cd
                  WHERE cd.id = d.cate_design_id AND cd.is_active = 1
              )
              AND EXISTS (
                  SELECT 1
                  FROM design_images AS di
                  INNER JOIN design_color_compatibilities AS dcc
                      ON dcc.design_image_id = di.id AND dcc.is_allowed = 1
                  INNER JOIN product_color_prices AS pcp2
                      ON pcp2.product_id = products.id AND pcp2.is_active = 1
                  INNER JOIN colors AS c2
                      ON c2.id = pcp2.color_id AND c2.is_active = 1
                  WHERE di.design_id = d.id
                    AND di.is_active = 1
                    AND c2.id = dcc.card_color_id
              )
        )';

        return $query
            ->where(function (Builder $q) use ($activeColorPrice, $purchasableDesign) {
                $q->whereNull('customization_workflow')
                    ->where(function (Builder $commerce) use ($activeColorPrice) {
                        $commerce->whereNotNull('base_price')
                            ->orWhereRaw($activeColorPrice);
                    });

                $q->orWhere(function (Builder $card) use ($activeColorPrice, $purchasableDesign) {
                    $card->whereIn(
                        'customization_workflow',
                        array_map(
                            fn (CustomizationWorkflowEnum $workflow) => $workflow->value,
                            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
                        )
                    )
                        ->whereRaw($activeColorPrice)
                        ->whereRaw($purchasableDesign);
                });
            })
            ->where(function (Builder $fuel) {
                $fuel->where('type', '!=', ProductTypeEnum::FUEL->value)
                    ->orWhereRaw('(
                        SELECT COUNT(*) FROM product_color_prices AS pcp3
                        WHERE pcp3.product_id = products.id
                          AND pcp3.is_active = 1
                          AND EXISTS (
                              SELECT 1 FROM colors AS c3
                              WHERE c3.id = pcp3.color_id AND c3.is_active = 1
                          )
                    ) = 1');
            });
    }
}
