<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت دسته‌بندی مقالات</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + دسته‌بندی جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h3>
            <form wire:submit="save" class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">نام</label>
                    <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام یا اسلاگ..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نام</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">اسلاگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تعداد مقالات</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $category->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs" dir="ltr">{{ $category->slug }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $category->articles_count }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $category->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $category->id }})" wire:confirm="آیا از حذف این دسته‌بندی مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">دسته‌بندی‌ای یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $categories->links() }}</div>
    </div>
</div>