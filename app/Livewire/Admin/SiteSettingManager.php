<?php

namespace App\Livewire\Admin;

use App\Models\SiteSetting;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SiteSettingManager extends Component
{
    use AuthorizesAdminActions;

    public string $search = '';

    public string $key = '';

    public string $value = '';

    public string $type = 'string';

    public string $group = 'general';

    public bool $isPublic = false;

    public ?int $editingId = null;

    public bool $showForm = false;

    private const SUPPORTED_TYPES = ['string', 'integer', 'boolean', 'json'];

    protected function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('site_settings', 'key')->ignore($this->editingId),
            ],
            'type' => 'required|in:'.implode(',', self::SUPPORTED_TYPES),
            'group' => 'required|string|max:100',
            'value' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $value = trim((string) $value);
                    if ($value === '') {
                        return;
                    }

                    match ($this->type) {
                        'integer' => $this->validateIntegerValue($value, $fail),
                        'boolean' => $this->validateBooleanValue($value, $fail),
                        'json' => $this->validateJsonValue($value, $fail),
                        default => null,
                    };
                },
            ],
            'isPublic' => 'boolean',
        ];
    }

    private function validateIntegerValue(string $value, callable $fail): void
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false && ! preg_match('/^-?\d+$/', $value)) {
            $fail('مقدار باید عدد صحیح باشد.');
        }
    }

    private function validateBooleanValue(string $value, callable $fail): void
    {
        if (! in_array($value, ['0', '1', 'true', 'false'], true)) {
            $fail('مقدار باید true یا false باشد.');
        }
    }

    private function validateJsonValue(string $value, callable $fail): void
    {
        json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('مقدار JSON معتبر نیست.');
        }
    }

    public function updatedSearch(): void
    {
        // no pagination; search is a filter only
    }

    public function updatedType(): void
    {
        if ($this->type === 'boolean' && ! in_array($this->value, ['0', '1'], true)) {
            $this->value = '1';
        }
        $this->resetValidation(['value']);
    }

    public function save(): void
    {
        $this->key = strtolower(trim($this->key));
        $this->group = trim($this->group);
        $this->value = trim($this->value);

        $this->validate();

        $value = $this->normalizeValue($this->value, $this->type);

        $data = [
            'key' => $this->key,
            'value' => $value,
            'type' => $this->type,
            'group' => $this->group,
            'is_public' => $this->isPublic,
            'updated_at' => now(),
        ];

        if ($this->editingId) {
            SiteSetting::find($this->editingId)->update($data);
            session()->flash('success', 'تنظیمات با موفقیت ویرایش شد');
        } else {
            SiteSetting::create($data);
            session()->flash('success', 'تنظیمات با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    private function normalizeValue(string $value, string $type): ?string
    {
        if ($value === '') {
            return null;
        }

        return match ($type) {
            'integer' => (string) (int) $value,
            'boolean' => in_array($value, ['1', 'true'], true) ? '1' : '0',
            'json' => json_encode(json_decode($value, true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => $value,
        };
    }

    public function edit(int $id): void
    {
        $setting = SiteSetting::find($id);
        $this->editingId = $id;
        $this->key = $setting->key;
        $this->value = (string) $setting->value;
        $this->type = $setting->type;
        $this->group = $setting->group;
        $this->isPublic = $setting->is_public;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        SiteSetting::find($id)->delete();
        session()->flash('success', 'تنظیمات با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->key = '';
        $this->value = '';
        $this->type = 'string';
        $this->group = 'general';
        $this->isPublic = false;
        $this->editingId = null;
    }

    public function render()
    {
        $settings = SiteSetting::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('key', 'like', "%{$this->search}%")
                        ->orWhere('value', 'like', "%{$this->search}%")
                        ->orWhere('group', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return view('livewire.admin.site-setting-manager', [
            'settings' => $settings->groupBy('group'),
            'totalCount' => $settings->count(),
        ])->layout('layouts.admin')->title('تنظیمات سایت');
    }
}
