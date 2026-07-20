<?php

namespace App\Livewire\Admin;

use App\Models\CardType;
use App\Models\Color;
use Livewire\Component;
use Livewire\WithPagination;

class CardTypeManager extends Component
{
    use WithPagination;

    public ?int $colorId = null;
    public int $basePrice = 0;
    public bool $isAvailable = true;
    public string $type = 'bank';
    public ?int $editingId = null;
    public bool $showForm = false;

    protected $rules = [
        'colorId' => 'required|exists:colors,id',
        'basePrice' => 'required|integer|min:0',
        'isAvailable' => 'boolean',
        'type' => 'required|in:bank,fuel',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            CardType::find($this->editingId)->update([
                'color_id' => $this->colorId,
                'base_price' => $this->basePrice,
                'is_available' => $this->isAvailable,
                'type' => $this->type,
            ]);
            session()->flash('success', 'نوع کارت با موفقیت ویرایش شد');
        } else {
            CardType::create([
                'color_id' => $this->colorId,
                'base_price' => $this->basePrice,
                'is_available' => $this->isAvailable,
                'type' => $this->type,
            ]);
            session()->flash('success', 'نوع کارت با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $ct = CardType::find($id);
        $this->editingId = $id;
        $this->colorId = $ct->color_id;
        $this->basePrice = $ct->base_price;
        $this->isAvailable = $ct->is_available;
        $this->type = $ct->type;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        CardType::find($id)->delete();
        session()->flash('success', 'نوع کارت با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->colorId = null;
        $this->basePrice = 0;
        $this->isAvailable = true;
        $this->type = 'bank';
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.card-type-manager', [
            'cardTypes' => CardType::with('color')->paginate(15),
            'colors' => Color::all(),
        ])->layout('layouts.admin');
    }
}
