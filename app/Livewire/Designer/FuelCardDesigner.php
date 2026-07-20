<?php

namespace App\Livewire\Designer;

use App\Models\CardType;
use App\Models\Color;
use App\Models\Customization;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\FuelCardData;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FuelCardDesigner extends Component
{
    public int $step = 1;
    public string $chipSize = 'small';
    public ?int $selectedCardTypeId = null;
    public ?int $selectedDesignId = null;
    public ?int $selectedDesignImageId = null;
    public string $search = '';
    public ?int $blackColorId = null;

    // Back card fields
    public string $ownerName = '';
    public string $carModel = '';
    public string $vinNumber = '';
    public string $sysNumber = '';
    public string $plateNumber = '';
    public bool $saved = false;

    public function mount(): void
    {
        $this->blackColorId = Color::where('color_code', '#1a1a1a')->value('id');

        if ($this->blackColorId) {
            $cardType = CardType::where('type', 'fuel')
                ->where('color_id', $this->blackColorId)
                ->where('is_available', true)
                ->value('id');
            if ($cardType) {
                $this->selectedCardTypeId = $cardType;
            }
        }
    }

    public function getDesignsProperty()
    {
        $allDesigns = Design::with(['groupDesign.cateDesign', 'designImages.color']);

        if ($this->search !== '') {
            $allDesigns->where('name', 'like', '%' . $this->search . '%');
        }

        $designs = $allDesigns->get();

        return $designs->sortBy(function ($d) {
            $catName = $d->groupDesign?->cateDesign?->name ?? '';
            return match ($catName) {
                'ماشین‌ها' => 0,
                'ابستراکت' => 1,
                default => 2,
            };
        })->values();
    }

    public function getDesignImagesProperty()
    {
        if (!$this->selectedDesignId) {
            return collect();
        }

        $query = DesignImage::where('design_id', $this->selectedDesignId)
            ->with('color');

        if ($this->blackColorId) {
            $query->whereDoesntHave('colorRestrictions', function ($q) {
                $q->where('forbidden_card_color_id', $this->blackColorId);
            });
        }

        return $query->get();
    }

    public function getSelectedCardTypeProperty()
    {
        return CardType::with('color')->find($this->selectedCardTypeId);
    }

    public function getSelectedDesignImageProperty()
    {
        if (!$this->selectedDesignImageId) {
            return null;
        }
        return DesignImage::with(['design', 'color'])->find($this->selectedDesignImageId);
    }

    public function selectChipSize(string $size): void
    {
        $this->chipSize = $size;
        $this->step = 2;
    }

    public function selectDesign(int $id): void
    {
        $this->selectedDesignId = $id;
        $this->step = 3;
        $this->selectedDesignImageId = null;
    }

    public function selectDesignImage(int $id): void
    {
        $this->selectedDesignImageId = $id;
        $this->step = 4;
    }

    public function goToBackForm(): void
    {
        $this->step = 5;
    }

    public function goBack(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function saveCard(): void
    {
        $this->validate([
            'ownerName' => 'required|string|min:2',
            'carModel' => 'required|string|min:1',
            'vinNumber' => 'required|string|size:17',
            'sysNumber' => 'required|string',
        ]);

        $data = FuelCardData::create([
            'owner_name' => $this->ownerName,
            'car_model' => $this->carModel,
            'vin_number' => $this->vinNumber,
            'sys_number' => $this->sysNumber,
            'plate_number' => $this->plateNumber,
            'chip_type' => $this->chipSize,
            'chip_size' => $this->chipSize,
        ]);

        if (Auth::check()) {
            Customization::create([
                'card_type_id' => $this->selectedCardTypeId,
                'design_image_id' => $this->selectedDesignImageId,
                'customizable_id' => $data->id,
                'customizable_type' => get_class($data),
            ]);
        }

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.designer.fuel-card-designer');
    }
}
