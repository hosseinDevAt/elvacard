<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="create" class="admin-btn admin-btn-primary">
            + دسته‌بندی جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h3>
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
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                    <label for="is_active" class="text-sm text-gray-700">فعال (قابل انتخاب در فروشگاه)</label>
                </div>
                <div class="flex items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">نام</th>
                    <th class="admin-th">اسلاگ</th>
                    <th class="admin-th">تعداد محصولات</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">ترتیب</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $category->id }}</td>
                        <td class="admin-td font-medium">{{ $category->name }}</td>
                        <td class="admin-td text-gray-500 font-mono text-xs" dir="ltr">{{ $category->slug }}</td>
                        <td class="admin-td text-gray-500">{{ $category->products_count }}</td>
                        <td class="admin-td">
                            <span class="{{ $category->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $category->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td text-gray-500">{{ $category->sort_order }}</td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $category->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $category->id }})" wire:confirm="آیا از حذف این دسته‌بندی مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="admin-empty">دسته‌بندی‌ای وجود ندارد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $categories->links() }}</div>
    </div>
</div>
