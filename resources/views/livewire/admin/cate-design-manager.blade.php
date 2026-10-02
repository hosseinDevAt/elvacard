<div>
    <x-admin.page-header
        title="دسته‌بندی‌های طرح کارت"
        description="دسته‌بندی و سازماندهی موضوعی طرح‌های چاپ و سفارشی‌سازی کارت"
    >
        <x-slot:actions>
            <button wire:click="create" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>دسته‌بندی جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h3>
            <form wire:submit="save" class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:flex-1">
                    <label class="admin-label">نام دسته‌بندی</label>
                    <input type="text" wire:model="name" class="admin-input" placeholder="مثال: ورزشی، مینیمال، فانتزی...">
                    @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2 pb-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="is_active" class="text-sm font-medium text-slate-700">فعال</label>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <span>ذخیره</span>
                    </button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary">
                        <span>لغو</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="space-y-4">
        @forelse($categories as $category)
            <div class="admin-card p-5 transition hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="font-bold text-slate-900">{{ $category->name }}</h3>
                            <span class="admin-badge {{ $category->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $category->designs->count() }} طرح متصل</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="edit({{ $category->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                        <button wire:click="delete({{ $category->id }})" wire:confirm="آیا از حذف این دسته‌بندی مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                    </div>
                </div>
                @if($category->designs->isNotEmpty())
                    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap gap-2">
                        @foreach($category->designs as $design)
                            <span class="admin-badge admin-badge-neutral text-xs">{{ $design->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="admin-card p-8">
                <x-admin.empty-state
                    title="دسته‌بندی‌ای یافت نشد"
                    description="هنوز هیچ دسته‌بندی برای طرح‌ها ایجاد نشده است."
                />
            </div>
        @endforelse
    </div>

    @if($categories->hasPages())
        <div class="mt-4">{{ $categories->links() }}</div>
    @endif
</div>