<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">تصاویر طرح‌ها</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + تصویر جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش تصویر' : 'تصویر جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">طرح</label>
                        <select wire:model="designId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">— انتخاب طرح —</option>
                            @foreach($designOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('designId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رنگ تصویر</label>
                        <select wire:model="colorId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">— انتخاب رنگ —</option>
                            @foreach($colorOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('colorId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">مسیر تصویر</label>
                    <input type="text" wire:model="imagePath" placeholder="designs/eagle-black.png" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('imagePath') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">متن جایگزین (Alt)</label>
                        <input type="text" wire:model="altText" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('altText') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عنوان تصویر</label>
                        <input type="text" wire:model="imageTitle" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('imageTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">فایل بهینه‌شده</label>
                        <input type="text" wire:model="optimizedFilename" placeholder="eagle-black-optimized.webp" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('optimizedFilename') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('sortOrder') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">کپشن سئو</label>
                    <textarea wire:model="seoCaption" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('seoCaption') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                    <label for="is_active" class="text-sm text-gray-700">فعال (نمایش در سفارشی‌ساز)</label>
                </div>
                <div class="flex items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="flex flex-wrap gap-4 mb-4">
        <select wire:model.live="designFilter" class="w-full sm:w-64 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
            <option value="">همه طرح‌ها</option>
            @foreach($designFilterOptions as $design)
                <option value="{{ $design->id }}">{{ $design->name }}</option>
            @endforeach
        </select>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در مسیر تصویر..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">طرح</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">رنگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">مسیر</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($images as $image)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $image->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $image->design?->name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded border" style="background-color: {{ $image->color?->code_hex }}"></div>
                                <span>{{ $image->color?->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs break-all" dir="ltr">{{ $image->image_path }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $image->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $image->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $image->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $image->id }})" wire:confirm="آیا از حذف این تصویر (به همراه سازگاری‌هایش) مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">تصویری یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $images->links() }}</div>
    </div>
</div>