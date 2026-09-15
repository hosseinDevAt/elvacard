<?php

namespace App\Livewire\Admin;

use App\Models\FaqItem;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;
use Livewire\WithPagination;

class FaqItemManager extends Component
{
    use AuthorizesAdminActions;
    use WithPagination;

    public string $search = '';

    public string $question = '';

    public string $answer = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'question' => 'required|string|min:1',
            'answer' => 'required|string|min:1',
            'sortOrder' => 'required|integer|min:0',
            'isActive' => 'boolean',
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
            FaqItem::find($this->editingId)->update([
                'question' => $this->question,
                'answer' => $this->answer,
                'sort_order' => $this->sortOrder,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'سوال با موفقیت ویرایش شد');
        } else {
            FaqItem::create([
                'question' => $this->question,
                'answer' => $this->answer,
                'sort_order' => $this->sortOrder,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'سوال با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $faq = FaqItem::find($id);
        $this->editingId = $id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->sortOrder = $faq->sort_order;
        $this->isActive = $faq->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        FaqItem::find($id)->delete();
        session()->flash('success', 'سوال با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->question = '';
        $this->answer = '';
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.faq-item-manager', [
            'faqs' => FaqItem::query()
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('question', 'like', "%{$this->search}%")
                            ->orWhere('answer', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت سوالات متداول');
    }
}
