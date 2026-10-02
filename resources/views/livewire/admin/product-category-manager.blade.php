<div>
    <x-admin.page-header title="دسته‌بندی محصولات" subtitle="مدیریت و دسته‌بندی محصولات برای دسترسی سریع‌تر در فروشگاه">
        <x-slot:actions>
            <button wire:click="create" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-[#ffde5b]/25">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>دسته‌بندی جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">نام</label>
                        <input type="text" wire:model="name" class="admin-input">
                        @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="is_active" class="text-xs font-semibold text-slate-700">فعال (قابل انتخاب در فروشگاه)</label>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : 'ایجاد دسته‌بندی' }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">لیست دسته‌بندی‌ها</h3>
            <span class="text-xs text-slate-400">مجموع: {{ $categories->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">نام</th>
                        <th class="admin-th">اسلاگ</th>
                        <th class="admin-th">تعداد محصولات</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">ترتیب</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $category->id }}</td>
                            <td class="admin-td font-bold text-slate-900">{{ $category->name }}</td>
                            <td class="admin-td text-slate-500 font-mono text-xs" dir="ltr">{{ $category->slug }}</td>
                            <td class="admin-td text-slate-600 font-mono text-xs">{{ $category->products_count }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $category->is_active ? 'admin-badge-success' : 'admin-badge-neutral' }}">
                                    {{ $category->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-slate-400 text-xs font-mono">{{ $category->sort_order }}</td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $category->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $category->id }})" wire:confirm="آیا از حذف این دسته‌بندی مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8">
                                <x-admin.empty-state title="دسته‌بندی‌ای یافت نشد" description="هنوز هیچ دسته‌بندی برای محصولات تعریف نشده است." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $categories->links() }}</div>
    </div>
</div>
