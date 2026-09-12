<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت رنگ‌ها</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + رنگ جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش رنگ' : 'رنگ جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-full sm:flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-1">کد رنگ</label>
                        <input type="color" wire:model.live="colorCode" class="w-full h-10 rounded-lg border border-gray-300 cursor-pointer">
                        @error('colorCode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if($colorCode)
                        <div class="w-20">
                            <label class="block text-sm font-medium text-gray-700 mb-1">پیش‌نمایش</label>
                            <div class="w-10 h-10 rounded-lg border" style="background-color: {{ $colorCode }}"></div>
                        </div>
                    @endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تصویر (Path)</label>
                        <input type="text" wire:model="previewImage" placeholder="colors/black.png" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('previewImage') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('sortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                    <label for="is_active" class="text-sm text-gray-700">فعال (قابل انتخاب در سبد و سفارش)</label>
                </div>
                <div class="flex items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نام</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">کد رنگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">ترتیب</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($colors as $color)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $color->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $color->name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded border" style="background-color: {{ $color->code_hex }}"></div>
                                <span class="text-gray-500 font-mono text-xs">{{ $color->code_hex }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $color->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $color->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $color->sort_order }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $color->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $color->id }})" wire:confirm="آیا از حذف این رنگ مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">رنگی وجود ندارد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $colors->links() }}</div>
    </div>
</div>