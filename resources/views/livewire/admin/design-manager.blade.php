<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت طرح‌ها</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + طرح جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش طرح' : 'طرح جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">دسته‌بندی طرح</label>
                        <select wire:model="cateDesignId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">— انتخاب دسته‌بندی —</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (غیرفعال)' }}</option>
                            @endforeach
                        </select>
                        @error('cateDesignId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام طرح</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                    <textarea wire:model="description" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('sortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_active" class="text-sm text-gray-700">فعال (نمایش در سفارشی‌ساز)</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-gray-300 text-yellow-500">
                        <label for="robots_index" class="text-sm text-gray-700">قابل ایندکس در موتورهای جستجو</label>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-900 mb-3">سئو</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">عنوان سئو</label>
                            <input type="text" wire:model="metaTitle" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('metaTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات سئو</label>
                            <textarea wire:model="metaDescription" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                            @error('metaDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">آدرس Canonical</label>
                            <input type="text" wire:model="canonicalUrl" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('canonicalUrl') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">محتوی سئو</label>
                        <textarea wire:model="seoContent" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                        @error('seoContent') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام طرح..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نام</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">اسلاگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">دسته‌بندی</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تصاویر</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($designs as $design)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $design->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $design->name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs" dir="ltr">{{ $design->slug }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $design->category?->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $design->images_count }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $design->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $design->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $design->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $design->id }})" wire:confirm="آیا از حذف این طرح مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">طرحی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $designs->links() }}</div>
    </div>
</div>