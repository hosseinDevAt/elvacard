<?php

namespace App\Livewire\Admin;

use App\Models\CateDesign;
use App\Models\Design;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class CateDesignManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

    public string $name = '';

    public string $slug = '';

    public bool $slugManuallyChanged = false;

    public string $workspaceVisibility = 'both';

    public bool $isActive = true;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                function ($attribute, $value, $fail) {
                    $slug = (string) $value;

                    if ($this->editingId) {
                        $existing = CateDesign::find($this->editingId);
                        if ($existing && $existing->slug !== $slug) {
                            $fail('امکان تغییر نامک دسته‌بندی وجود ندارد.');

                            return;
                        }
                    }

                    $exists = CateDesign::query()
                        ->where('slug', $slug)
                        ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('این نامک قبلاً ثبت شده و تکراری است.');
                    }
                },
            ],
            'workspaceVisibility' => 'required|string|in:bank,fuel,both',
            'isActive' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'slug.required' => 'وارد کردن نامک دسته‌بندی الزامی است.',
            'slug.regex' => 'نامک فقط می‌تواند شامل حروف کوچک انگلیسی، اعداد، خط تیره (-) و زیرخط (_) باشد.',
            'workspaceVisibility.required' => 'انتخاب محل نمایش دسته‌بندی الزامی است.',
            'workspaceVisibility.in' => 'محل نمایش انتخاب شده نامعتبر است.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'نام دسته‌بندی',
            'slug' => 'نامک انگلیسی',
        ];
    }

    public function updatedName(string $value): void
    {
        if (! $this->editingId && (! $this->slugManuallyChanged || trim($this->slug) === '')) {
            $suggested = Str::slug($value);
            if ($suggested !== '') {
                $this->slug = $suggested;
            }
        }
    }

    public function updatedSlug(string $value): void
    {
        $this->slugManuallyChanged = true;
    }

    public function save(): void
    {
        $normalizedSlug = strtolower(trim($this->slug ?? ''));

        if ($this->editingId) {
            $cateDesign = CateDesign::find($this->editingId);

            if (! $cateDesign) {
                session()->flash('error', 'دسته‌بندی طرح موردنظر یافت نشد');

                return;
            }

            $originalSlug = (string) $cateDesign->slug;

            if (! $this->slugManuallyChanged || $normalizedSlug === '') {
                $normalizedSlug = $originalSlug;
            }

            $this->slug = $normalizedSlug;
        } else {
            if (! $this->slugManuallyChanged || $normalizedSlug === '') {
                $fallbackBase = trim($this->name);
                $normalizedSlug = $this->uniqueSlug(
                    $fallbackBase !== '' ? $fallbackBase : 'category',
                    CateDesign::class,
                    null,
                    'category',
                );
            }

            $this->slug = $normalizedSlug;
        }

        $this->validate();

        if ($this->editingId && ! $this->isActive) {
            $blocker = ProductPurchaseabilityService::categoryDeactivationBlocker($this->editingId);

            if ($blocker !== null) {
                session()->flash('error', $blocker);

                return;
            }

            if (Design::where('cate_design_id', $this->editingId)->where('is_active', true)->exists()) {
                session()->flash('error', 'این دسته‌بندی شامل طرح‌های فعال است؛ ابتدا طرح‌های آن را غیرفعال یا به دسته‌بندی دیگری منتقل کنید.');

                return;
            }
        }

        if ($this->editingId) {
            $cateDesign = CateDesign::find($this->editingId);

            if (! $cateDesign) {
                session()->flash('error', 'دسته‌بندی طرح موردنظر یافت نشد');

                return;
            }

            $cateDesign->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'workspace_visibility' => $this->workspaceVisibility,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت ویرایش شد');
        } else {
            CateDesign::create([
                'name' => $this->name,
                'slug' => $this->slug,
                'workspace_visibility' => $this->workspaceVisibility,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Opens the form for a brand new category. Always resets first: otherwise
     * opening the form right after editing another row keeps the previous
     * editingId and silently overwrites that category on save.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $cate = CateDesign::find($id);

        if (! $cate) {
            session()->flash('error', 'دسته‌بندی موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->name = $cate->name;
        $this->slug = (string) $cate->slug;
        $this->slugManuallyChanged = false;
        $this->workspaceVisibility = $cate->workspace_visibility instanceof \App\Enums\DesignWorkspaceVisibilityEnum
            ? $cate->workspace_visibility->value
            : (string) ($cate->workspace_visibility ?? 'both');
        $this->isActive = (bool) $cate->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $cate = CateDesign::find($id);

        if (! $cate) {
            session()->flash('error', 'دسته‌بندی موردنظر یافت نشد');

            return;
        }

        if ($cate->designs()->exists()) {
            session()->flash('error', 'این دسته‌بندی دارای طرح است و قابل حذف نیست. ابتدا طرح‌های آن را حذف کنید.');

            return;
        }

        $cate->delete();
        session()->flash('success', 'دسته‌بندی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->slugManuallyChanged = false;
        $this->workspaceVisibility = 'both';
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.cate-design-manager', [
            'categories' => CateDesign::with('designs')->orderByDesc('id')->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت دسته‌بندی طرح‌ها');
    }
}
