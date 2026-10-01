<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="create" class="admin-btn admin-btn-primary">
            + تصویر جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش تصویر' : 'تصویر جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">طرح</label>
                        <select wire:model="designId" class="admin-input">
                            <option value="">— انتخاب طرح —</option>
                            @foreach($designOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('designId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">رنگ تصویر</label>
                        <select wire:model="colorId" class="admin-input">
                            <option value="">— انتخاب رنگ —</option>
                            @foreach($colorOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('colorId') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="admin-label">مسیر تصویر</label>
                    <input type="text" wire:model="imagePath" placeholder="designs/eagle-black.png" dir="ltr" class="admin-input">
                    @error('imagePath') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">متن جایگزین (Alt)</label>
                        <input type="text" wire:model="altText" class="admin-input">
                        @error('altText') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">عنوان تصویر</label>
                        <input type="text" wire:model="imageTitle" class="admin-input">
                        @error('imageTitle') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">فایل بهینه‌شده</label>
                        <input type="text" wire:model="optimizedFilename" placeholder="eagle-black-optimized.webp" dir="ltr" class="admin-input">
                        @error('optimizedFilename') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="admin-label">کپشن سئو</label>
                    <textarea wire:model="seoCaption" rows="2" class="admin-input"></textarea>
                    @error('seoCaption') <p class="admin-error">{{ $message }}</p> @enderror
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
        <div>
            <select wire:model.live="designFilter" class="admin-input sm:w-64">
                <option value="">همه طرح‌ها</option>
                @foreach($designFilterOptions as $design)
                    <option value="{{ $design->id }}">{{ $design->name }}</option>
                @endforeach
            </select>
            @error('designFilter')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در مسیر تصویر..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">طرح</th>
                    <th class="admin-th">رنگ</th>
                    <th class="admin-th">مسیر</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($images as $image)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $image->id }}</td>
                        <td class="admin-td font-medium">{{ $image->design?->name }}</td>
                        <td class="admin-td">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded border" style="background-color: {{ $image->color?->code_hex }}"></div>
                                <span>{{ $image->color?->name }}</span>
                            </div>
                        </td>
                        <td class="admin-td text-gray-500 font-mono text-xs break-all" dir="ltr">{{ $image->image_path }}</td>
                        <td class="admin-td">
                            <span class="{{ $image->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $image->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $image->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $image->id }})" wire:confirm="آیا از حذف این تصویر (به همراه سازگاری‌هایش) مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty">تصویری یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $images->links() }}</div>
    </div>
</div>