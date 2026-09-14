<?php

namespace App\Livewire\Admin;

use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Services\Customization\ProductPurchaseabilityService;
use Illuminate\Support\Collection;
use Livewire\Component;

class DesignColorCompatibilityManager extends Component
{
    public ?int $designFilter = null;

    public function toggle(int $designImageId, int $colorId): void
    {
        $image = DesignImage::query()->find($designImageId);

        if (! $image) {
            session()->flash('error', 'تصویر طرح موردنظر یافت نشد.');

            return;
        }

        if ($this->designFilter && (int) $image->design_id !== (int) $this->designFilter) {
            session()->flash('error', 'تصویر انتخاب‌شده متعلق به این طرح نیست.');

            return;
        }

        $color = Color::query()->find($colorId);

        if (! $color) {
            session()->flash('error', 'رنگ موردنظر یافت نشد.');

            return;
        }

        $existing = DesignColorCompatibility::query()
            ->where('design_image_id', $designImageId)
            ->where('card_color_id', $colorId)
            ->first();

        if ($existing) {
            if ($existing->is_allowed) {
                $removalBlocker = ProductPurchaseabilityService::compatibilityRemovalBlocker($designImageId, $colorId);

                if ($removalBlocker !== null) {
                    session()->flash('error', $removalBlocker);

                    return;
                }
            }

            $existing->update(['is_allowed' => ! $existing->is_allowed]);
            session()->flash('success', 'وضعیت سازگاری با موفقیت تغییر کرد');
        } else {
            DesignColorCompatibility::create([
                'design_image_id' => $designImageId,
                'card_color_id' => $colorId,
                'is_allowed' => true,
            ]);
            session()->flash('success', 'سازگاری جدید ثبت شد');
        }
    }

    public function getDesignOptionsProperty(): Collection
    {
        return Design::query()->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $colors = Color::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        $images = [];

        if ($this->designFilter) {
            $designImages = DesignImage::query()
                ->where('design_id', $this->designFilter)
                ->with(['color', 'compatibilities'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($designImages as $image) {
                $map = [];

                foreach ($colors as $color) {
                    $row = $image->compatibilities->firstWhere('card_color_id', $color->id);
                    $map[$color->id] = $row ? (bool) $row->is_allowed : false;
                }

                $images[] = [
                    'image' => $image,
                    'allowedCount' => count(array_filter($map)),
                    'map' => $map,
                ];
            }
        }

        return view('livewire.admin.design-color-compatibility-manager', [
            'designOptions' => $this->designOptions,
            'colors' => $colors,
            'images' => $images,
        ])->layout('layouts.admin')->title('سازگاری رنگ طرح‌ها');
    }
}
