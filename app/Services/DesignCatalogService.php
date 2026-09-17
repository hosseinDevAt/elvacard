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
     *
     * The workflow gate is the customization authority: an inactive or null
     * workflow must never resolve a catalog.
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

        return $this->page($categoryId, $colorId, $allowedImageIds, $perPage);
    }

    /**
     * Returns one bounded page of the public design gallery. Identical to
     * paginate() but without the customization-workflow gate: the gallery is a
     * browse surface, not a workflow step. A null category id browses every
     * active category.
     */
    public function paginatePublic(
        ?int $categoryId = null,
        ?int $colorId = null,
        ?Collection $allowedImageIds = null,
        int $perPage = 12,
    ): LengthAwarePaginator {
        return $this->page($categoryId, $colorId, $allowedImageIds, $perPage);
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

        return $this->baseQuery($categoryId, $allowedImageIds)
            ->where('id', $designId)
            ->exists();
    }

    /**
     * Whether at least one purchasable design exists: the design and its
     * category are active and it has at least one active image allowed for the
     * given card color. Reused by activation readiness so fuel activation and
     * the public catalog share one design-visibility truth.
     */
    public function hasPurchasableDesign(?int $categoryId = null, ?Collection $allowedImageIds = null): bool
    {
        return $this->baseQuery($categoryId, $allowedImageIds)->exists();
    }

    /**
     * Returns every design currently visible in the public catalog (active,
     * in an active category, with at least one active image), optionally
     * restricted to the given ids. Homepage featured sections and admin
     * dropdowns use this so the storefront and the CMS share one
     * design-visibility truth.
     */
    public function visibleDesigns(array $ids = []): Collection
    {
        return $this->baseQuery(null, null)
            ->when(
                $ids !== [],
                fn ($query) => $query->whereIn(
                    'id',
                    collect($ids)
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all()
                )
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Whether a single design is currently visible in the public catalog.
     */
    public function isDesignVisible(int $designId): bool
    {
        return $this->baseQuery(null, null)
            ->whereKey($designId)
            ->exists();
    }

    /**
     * Whether an activated design would actually be displayable in the
     * customization workspace. The workspace only lists a design for a card
     * color when the design is active, its category is active, and one of its
     * active images is allowed for that color; a design missing any of those
     * is saved but silently invisible. This check mirrors that gate (via
     * ProductCustomizer::allowedImageIds()) but deliberately ignores the
     * design's own is_active flag so the wizard can validate the activation
     * before it is written.
     */
    public function isReadyForWorkspace(int $designId): bool
    {
        return Design::query()
            ->whereKey($designId)
            ->whereRelation('category', fn ($query) => $query->where('is_active', true))
            ->whereExists(function ($query) {
                $query->select('id')
                    ->from('design_images')
                    ->whereColumn('design_images.design_id', 'designs.id')
                    ->where('design_images.is_active', true)
                    ->whereExists(function ($compat) {
                        $compat->select('id')
                            ->from('design_color_compatibilities')
                            ->whereColumn('design_color_compatibilities.design_image_id', 'design_images.id')
                            ->where('design_color_compatibilities.is_allowed', true)
                            ->whereExists(function ($color) {
                                $color->select('id')
                                    ->from('colors')
                                    ->whereColumn('colors.id', 'design_color_compatibilities.card_color_id')
                                    ->where('colors.is_active', true);
                            });
                    });
            })
            ->exists();
    }

    /**
     * The single source of design visibility: active design, active category,
     * and at least one active image within the allowed set.
     */
    private function baseQuery(?int $categoryId, ?Collection $allowedImageIds)
    {
        return Design::query()
            ->when(
                $categoryId !== null,
                fn ($query) => $query->where('cate_design_id', $categoryId)
            )
            ->where('is_active', true)
            // Authoritative active-category enforcement: Livewire public
            // properties are client-hydrated, so visibility must be guaranteed
            // in the query itself, not by any component or controller input.
            ->whereRelation('category', fn ($query) => $query->where('is_active', true))
            ->whereExists(fn ($query) => $this->scopeAllowedImages($query, $allowedImageIds));
    }

    private function page(
        ?int $categoryId,
        ?int $colorId,
        ?Collection $allowedImageIds,
        int $perPage,
    ): LengthAwarePaginator {
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

        return $this->baseQuery($categoryId, $allowedImageIds)
            ->select(['id', 'cate_design_id', 'name'])
            ->addSelect(['preview_image_path' => $preview])
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
