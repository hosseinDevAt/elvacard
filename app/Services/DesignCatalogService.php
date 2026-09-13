<?php

namespace App\Services;

use App\Enums\CustomizationWorkflowEnum;
use App\Models\Design;
use App\Models\DesignImage;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DesignCatalogService
{
    /**
     * Returns one page of the active design catalog for the given category,
     * filtered to images allowed for a card color. The relationship collections
     * are never hydrated: each returned item is a flat scalar array.
     */
    public function paginate(
        int $categoryId,
        ?int $colorId,
        ?Collection $allowedImageIds,
        ?CustomizationWorkflowEnum $workflow = null,
        int $perPage = 12,
    ): LengthAwarePaginator {
        if (! CustomizationWorkflowRegistry::isActive($workflow)) {
            return new LengthAwarePaginator([], 0, $perPage, 1);
        }

        $preview = DesignImage::query()
            ->select('image_path')
            ->whereColumn('design_id', 'designs.id')
            ->where('is_active', true)
            ->when(
                $allowedImageIds !== null,
                fn ($query) => $query->whereIn('id', $allowedImageIds)
            )
            ->when(
                $colorId !== null,
                fn ($query) => $query->orderByRaw('(color_id = ?) DESC, sort_order ASC', [$colorId]),
                fn ($query) => $query->orderBy('sort_order')
            )
            ->limit(1);

        return Design::query()
            ->select(['id', 'cate_design_id', 'name'])
            ->addSelect(['preview_image_path' => $preview])
            ->where('cate_design_id', $categoryId)
            ->where('is_active', true)
            ->whereExists(fn ($query) => $this->scopeAllowedImages($query, $allowedImageIds))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->through(fn (Design $design) => [
                'id' => $design->id,
                'category_id' => (int) $design->cate_design_id,
                'name' => $design->name,
                'preview_image_path' => $design->preview_image_path,
            ]);
    }

    /**
     * Whether a design can be selected from the server-side catalog: active,
     * in the selected category, and with at least one active image allowed for
     * the current card color.
     */
    public function isDesignInCatalog(
        int $designId,
        int $categoryId,
        ?Collection $allowedImageIds,
        ?CustomizationWorkflowEnum $workflow = null,
    ): bool {
        if (! CustomizationWorkflowRegistry::isActive($workflow)) {
            return false;
        }

        return Design::query()
            ->where('id', $designId)
            ->where('cate_design_id', $categoryId)
            ->where('is_active', true)
            ->whereExists(fn ($query) => $this->scopeAllowedImages($query, $allowedImageIds))
            ->exists();
    }

    private function scopeAllowedImages($query, ?Collection $allowedImageIds): void
    {
        $query
            ->select('id')
            ->from('design_images')
            ->whereColumn('design_images.design_id', 'designs.id')
            ->where('design_images.is_active', true);

        if ($allowedImageIds !== null) {
            $query->whereIn('design_images.id', $allowedImageIds);
        }
    }
}
