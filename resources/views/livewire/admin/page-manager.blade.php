<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + صفحه جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش صفحه' : 'صفحه جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">عنوان</label>
                        <input type="text" wire:model="title" class="admin-input">
                        @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">نوع صفحه</label>
                        <input type="text" wire:model="pageType" dir="ltr" placeholder="general" class="admin-input">
                        @error('pageType') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="admin-label">محتوا</label>
                    <textarea wire:model="content" rows="12" class="admin-input"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">تصویر</label>
                    <input type="file" wire:model="imageUpload" accept="image/*" class="w-full text-sm text-gray-600 file:me-3 file:rounded-lg file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-gray-700">
                    @error('imageUpload') <p class="admin-error">{{ $message }}</p> @enderror
                    @if ($imageUpload)
                        <img src="{{ $imageUpload->temporaryUrl() }}" alt="پیش‌نمایش تصویر" class="mt-3 h-32 w-full object-cover rounded-lg border border-gray-100">
                    @elseif ($imagePath)
                        <img src="{{ asset('storage/' . $imagePath) }}" alt="تصویر فعلی" class="mt-3 h-32 w-full object-cover rounded-lg border border-gray-100">
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_active" class="text-sm text-gray-700">فعال (نمایش عمومی)</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-gray-300 text-yellow-500">
                        <label for="robots_index" class="text-sm text-gray-700">قابل ایندکس در موتورهای جستجو</label>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-900 mb-3">سئو</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="admin-label">عنوان سئو</label>
                            <input type="text" wire:model="metaTitle" class="admin-input">
                            @error('metaTitle') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">توضیحات سئو</label>
                            <textarea wire:model="metaDescription" rows="3" class="admin-input"></textarea>
                            @error('metaDescription') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">آدرس Canonical</label>
                            <input type="text" wire:model="canonicalUrl" placeholder="https://example.com/..." dir="ltr" class="admin-input">
                            @error('canonicalUrl') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
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
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا اسلاگ..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">عنوان</th>
                    <th class="admin-th">اسلاگ</th>
                    <th class="admin-th">نوع</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($pages as $page)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $page->id }}</td>
                        <td class="admin-td font-medium">{{ $page->title }}</td>
                        <td class="admin-td text-gray-500 font-mono text-xs" dir="ltr">{{ $page->slug }}</td>
                        <td class="admin-td text-gray-500 text-xs" dir="ltr">{{ $page->page_type }}</td>
                        <td class="admin-td">
                            <span class="{{ $page->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $page->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $page->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $page->id }})" wire:confirm="آیا از حذف این صفحه مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty">صفحه‌ای یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $pages->links() }}</div>
    </div>
</div>