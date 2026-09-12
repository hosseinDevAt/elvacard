<?php

namespace App\Livewire\Admin;

use App\Models\Announcement;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $title = '';
    public string $content = '';
    public ?string $link = null;
    public ?string $backgroundColor = null;
    public ?string $textColor = null;
    public bool $isActive = true;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public ?int $editingId = null;
    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:1',
            'link' => [
                'nullable',
                'string',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim($value) === '') {
                        return;
                    }

                    $value = trim($value);

                    if (str_starts_with(strtolower($value), '//')) {
                        $fail('لینک پروتکل‌نسبی (//...) مجاز نیست.');
                        return;
                    }

                    if (safe_url($value) === null) {
                        $fail('لینک باید با http://، https:// یا / شروع شود.');
                    }
                },
            ],
            'backgroundColor' => 'nullable|string|max:100|regex:/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/',
            'textColor' => 'nullable|string|max:100|regex:/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/',
            'isActive' => 'boolean',
            'startDate' => 'nullable|date',
            'endDate' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim($value) === '') {
                        return;
                    }

                    if ($this->startDate === null || trim($this->startDate) === '') {
                        $fail('تاریخ پایان بدون تاریخ شروع مجاز نیست.');
                        return;
                    }

                    if (strtotime($value) < strtotime($this->startDate)) {
                        $fail('تاریخ پایان باید بزرگ‌تر یا مساوی تاریخ شروع باشد.');
                    }
                },
            ],
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $this->validate();

        $this->link = $this->link !== null && trim($this->link) !== '' ? trim($this->link) : null;
        $this->backgroundColor = $this->backgroundColor !== null && trim($this->backgroundColor) !== '' ? trim($this->backgroundColor) : null;
        $this->textColor = $this->textColor !== null && trim($this->textColor) !== '' ? trim($this->textColor) : null;
        $this->startDate = $this->startDate !== null && trim($this->startDate) !== '' ? $this->startDate : null;
        $this->endDate = $this->endDate !== null && trim($this->endDate) !== '' ? $this->endDate : null;

        $data = [
            'title' => $this->title,
            'content' => $this->content,
            'link' => $this->link,
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
            'is_active' => $this->isActive,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ];

        if ($this->editingId) {
            Announcement::find($this->editingId)->update($data);
            session()->flash('success', 'اطلاعیه با موفقیت ویرایش شد');
        } else {
            Announcement::create($data);
            session()->flash('success', 'اطلاعیه با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $announcement = Announcement::find($id);
        $this->editingId = $id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->link = $announcement->link;
        $this->backgroundColor = $announcement->background_color;
        $this->textColor = $announcement->text_color;
        $this->isActive = $announcement->is_active;
        $this->startDate = $announcement->start_date?->format('Y-m-d\TH:i');
        $this->endDate = $announcement->end_date?->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Announcement::find($id)->delete();
        session()->flash('success', 'اطلاعیه با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->title = '';
        $this->content = '';
        $this->link = null;
        $this->backgroundColor = null;
        $this->textColor = null;
        $this->isActive = true;
        $this->startDate = null;
        $this->endDate = null;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.announcement-manager', [
            'announcements' => Announcement::query()
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhere('content', 'like', "%{$this->search}%");
                    });
                })
                ->latest()
                ->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت اطلاعیه‌ها');
    }
}