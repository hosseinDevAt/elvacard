<?php

namespace App\Livewire\Admin;

use App\Models\CateDesign;
use App\Models\GroupDesign;
use Livewire\Component;
use Livewire\WithPagination;

class CateDesignManager extends Component
{
    use WithPagination;

    public string $name = '';
    public bool $isActive = true;
    public ?int $editingId = null;
    public bool $showForm = false;

    protected $rules = [
        'name' => 'required|string|min:1',
        'isActive' => 'boolean',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            CateDesign::find($this->editingId)->update([
                'name' => $this->name,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت ویرایش شد');
        } else {
            CateDesign::create([
                'name' => $this->name,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $cate = CateDesign::find($id);
        $this->editingId = $id;
        $this->name = $cate->name;
        $this->isActive = $cate->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        CateDesign::find($id)->delete();
        session()->flash('success', 'دسته‌بندی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.cate-design-manager', [
            'categories' => CateDesign::with('groupDesigns')->paginate(15),
        ])->layout('layouts.admin');
    }
}
