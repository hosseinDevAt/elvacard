<div>
    <x-admin.page-header title="دسته‌بندی مقالات" subtitle="مدیریت دسته‌ها و موضوعات مقالات و وبلاگ سایت">
        <x-slot:actions>
            <button wire:click="$set('showForm', true)" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-indigo-600/20">
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
            <form wire:submit="save" class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:flex-1">
                    <label class="admin-label">نام دسته‌بندی</label>
                    <input type="text" wire:model="name" class="admin-input">
                    @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : 'ایجاد دسته‌بندی' }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">لیست دسته‌بندی‌های مقاله</h3>
            <div class="w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام یا اسلاگ..." class="admin-input py-2 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">نام</th>
                        <th class="admin-th">اسلاگ</th>
                        <th class="admin-th">تعداد مقالات</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $category->id }}</td>
                            <td class="admin-td font-bold text-slate-900">{{ $category->name }}</td>
                            <td class="admin-td text-slate-500 font-mono text-xs" dir="ltr">{{ $category->slug }}</td>
                            <td class="admin-td">
                                <span class="admin-badge admin-badge-neutral text-xs font-mono">{{ $category->articles_count }} مقاله</span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $category->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $category->id }})" wire:confirm="آیا از حذف این دسته‌بندی مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8">
                                <x-admin.empty-state title="دسته‌بندی‌ای یافت نشد" description="هیچ دسته‌بندی مقاله‌ای در سیستم ثبت نشده است." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $categories->links() }}</div>
    </div>
</div>