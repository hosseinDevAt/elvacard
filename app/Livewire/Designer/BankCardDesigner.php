<?php

namespace App\Livewire\Designer;

use App\Models\BankCardData;
use App\Models\CardType;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Customization;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\GroupDesign;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BankCardDesigner extends Component
{
    public int $step = 1;
    public ?int $selectedColorId = null;
    public ?int $selectedCardTypeId = null;
    public ?int $selectedCateDesignId = null;
    public ?int $selectedGroupDesignId = null;
    public ?int $selectedDesignId = null;
    public ?int $selectedDesignImageId = null;
    public string $search = '';

    // Back card fields
    public string $holderName = '';
    public string $cardNumber = '';
    public string $cvv2 = '';
    public string $expiryDate = '';
    public array $fieldPositions = [];
    public bool $saved = false;

    public function mount(): void
    {
        $this->initDefaultPositions();
    }

    private function initDefaultPositions(): void
    {
        $this->fieldPositions = [
            'card_number' => ['top' => '15', 'left' => '5', 'width' => '90', 'fontSize' => '16'],
            'holder_name' => ['top' => '45', 'left' => '5', 'width' => '60', 'fontSize' => '13'],
            'expiry_date' => ['top' => '45', 'left' => '70', 'width' => '25', 'fontSize' => '13'],
            'cvv2' => ['top' => '65', 'left' => '35', 'width' => '30', 'fontSize' => '12'],
        ];
    }

    public function updatePosition(string $field, string $top, string $left): void
    {
        if (isset($this->fieldPositions[$field])) {
            $this->fieldPositions[$field]['top'] = $top;
            $this->fieldPositions[$field]['left'] = $left;
        }
    }

    public function updateFontSize(string $field, string $fontSize): void
    {
        if (isset($this->fieldPositions[$field])) {
            $this->fieldPositions[$field]['fontSize'] = $fontSize;
        }
    }

    public function getFormattedCardNumber(): string
    {
        $clean = preg_replace('/\D/', '', $this->cardNumber);
        $chunks = str_split(str_pad($clean, 16, '0'), 4);
        return implode(' ', $chunks);
    }

    public function isLightColor(): bool
    {
        $code = $this->selectedColor?->color_code;
        if (!$code) return false;
        $code = ltrim($code, '#');
        $r = hexdec(substr($code, 0, 2));
        $g = hexdec(substr($code, 2, 2));
        $b = hexdec(substr($code, 4, 2));
        return ($r * 299 + $g * 587 + $b * 114) / 1000 > 160;
    }

    public function getAvailableColorsProperty()
    {
        return Color::whereIn('id', CardType::where('type', 'bank')
            ->where('is_available', true)
            ->pluck('color_id'))
            ->get();
    }

    public function getCateDesignsProperty()
    {
        return CateDesign::where('is_active', true)
            ->with(['groupDesigns' => function ($q) {
                $q->with('designs');
            }])
            ->get();
    }

    public function getGroupDesignsProperty()
    {
        if (!$this->selectedCateDesignId) {
            return collect();
        }
        return GroupDesign::where('cate_design_id', $this->selectedCateDesignId)
            ->with('designs')
            ->get();
    }

    public function getDesignsProperty()
    {
        if (!$this->selectedGroupDesignId) {
            return collect();
        }
        $q = Design::where('group_design_id', $this->selectedGroupDesignId);
        if ($this->search !== '') {
            $q->where('name', 'like', '%' . $this->search . '%');
        }
        return $q->with('designImages')->get();
    }

    public function getDesignImagesProperty()
    {
        if (!$this->selectedDesignId || !$this->selectedColorId) {
            return collect();
        }

        return DesignImage::where('design_id', $this->selectedDesignId)
            ->whereDoesntHave('colorRestrictions', function ($q) {
                $q->where('forbidden_card_color_id', $this->selectedColorId);
            })
            ->with('color')
            ->get();
    }

    public function getSelectedColorProperty()
    {
        return Color::find($this->selectedColorId);
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

    public function selectColor(int $colorId): void
    {
        $cardType = CardType::where('type', 'bank')
            ->where('color_id', $colorId)
            ->where('is_available', true)
            ->first();

        if ($cardType) {
            $this->selectedColorId = $colorId;
            $this->selectedCardTypeId = $cardType->id;
            $this->step = 2;
            $this->selectedCateDesignId = null;
            $this->selectedGroupDesignId = null;
            $this->selectedDesignId = null;
            $this->selectedDesignImageId = null;
        }
    }

    public function selectCateDesign(int $id): void
    {
        $this->selectedCateDesignId = $id;
        $this->step = 3;
        $this->selectedGroupDesignId = null;
        $this->selectedDesignId = null;
        $this->selectedDesignImageId = null;
    }

    public function selectGroupDesign(int $id): void
    {
        $this->selectedGroupDesignId = $id;
        $this->step = 4;
        $this->selectedDesignId = null;
        $this->selectedDesignImageId = null;
    }

    public function selectDesign(int $id): void
    {
        $this->selectedDesignId = $id;
        $this->step = 5;
        $this->selectedDesignImageId = null;
    }

    public function selectDesignImage(int $id): void
    {
        $this->selectedDesignImageId = $id;
        $this->step = 6;
    }

    public function goToBackForm(): void
    {
        $this->step = 7;
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
            'holderName' => 'required|string|min:2',
            'cardNumber' => 'required|string|digits:16',
        ]);

        $data = BankCardData::create([
            'holder_name' => $this->holderName,
            'card_number' => $this->cardNumber,
            'cvv2' => $this->cvv2,
            'expiry_date' => $this->expiryDate,
            'field_positions' => $this->fieldPositions,
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
        return view('livewire.designer.bank-card-designer');
    }
}
