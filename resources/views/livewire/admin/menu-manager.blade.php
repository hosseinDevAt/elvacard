<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + منوی جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش منو' : 'منوی جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">نام</label>
                        <input type="text" wire:model="name" class="admin-input">
                        @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">موقعیت</label>
                        <select wire:model="location" class="admin-input">
                            <option value="header">هدر</option>
                            <option value="footer">فوتر</option>
                        </select>
                        @error('location') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام منو..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">نام</th>
                    <th class="admin-th">موقعیت</th>
                    <th class="admin-th">تعداد آیتم‌ها</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($menus as $menu)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $menu->id }}</td>
                        <td class="admin-td font-medium">{{ $menu->name }}</td>
                        <td class="admin-td text-gray-500 text-xs" dir="ltr">{{ $menu->location }}</td>
                        <td class="admin-td text-gray-500">{{ $menu->items_count }}</td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $menu->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $menu->id }})" wire:confirm="آیا از حذف این منو مطمئن هستید؟ تمام آیتم‌های آن حذف خواهند شد." class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="admin-empty">منویی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $menus->links() }}</div>
    </div>
</div>