<?php

namespace App\Livewire\Admin;

use App\Models\Color;
use Livewire\Component;
use Livewire\WithPagination;

class ColorManager extends Component
{
    use WithPagination;

    public string $name = '';
    public string $colorCode = '#000000';
    public ?int $editingId = null;
    public bool $showForm = false;

    protected $rules = [
        'name' => 'required|string|min:1',
        'colorCode' => 'required|string',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            Color::find($this->editingId)->update([
                'name' => $this->name,
                'color_code' => $this->colorCode,
            ]);
            session()->flash('success', 'رنگ با موفقیت ویرایش شد');
        } else {
            Color::create([
                'name' => $this->name,
                'color_code' => $this->colorCode,
            ]);
            session()->flash('success', 'رنگ با موفقیت اضافه شد');
        }

        $this->reset();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $color = Color::find($id);
        $this->editingId = $id;
        $this->name = $color->name;
        $this->colorCode = $color->color_code;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Color::find($id)->delete();
        session()->flash('success', 'رنگ با موفقیت حذف شد');
    }

    public function resetFields(): void
    {
        $this->name = '';
        $this->colorCode = '#000000';
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.color-manager', [
            'colors' => Color::paginate(15),
        ])->layout('layouts.admin');
    }
}
