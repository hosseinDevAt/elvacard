<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use Livewire\Component;
use Livewire\WithPagination;

class MenuManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $name = '';
    public string $location = 'header';

    public ?int $editingId = null;
    public bool $showForm = false;

    private const ALLOWED_LOCATIONS = ['header', 'footer'];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'location' => 'required|in:'.implode(',', self::ALLOWED_LOCATIONS),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            Menu::find($this->editingId)->update([
                'name' => $this->name,
                'location' => $this->location,
            ]);
            session()->flash('success', 'منو با موفقیت ویرایش شد');
        } else {
            Menu::create([
                'name' => $this->name,
                'location' => $this->location,
            ]);
            session()->flash('success', 'منو با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $menu = Menu::find($id);
        $this->editingId = $id;
        $this->name = $menu->name;
        $this->location = $menu->location;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Menu::find($id)->delete();
        session()->flash('success', 'منو با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->location = 'header';
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.menu-manager', [
            'menus' => Menu::query()
                ->when($this->search !== '', function ($query) {
                    $query->where('name', 'like', "%{$this->search}%");
                })
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت منوها');
    }
}