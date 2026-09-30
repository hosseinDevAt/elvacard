<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + اطلاعیه جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش اطلاعیه' : 'اطلاعیه جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="admin-label">عنوان</label>
                    <input type="text" wire:model="title" class="admin-input">
                    @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">متن</label>
                    <textarea wire:model="content" rows="4" class="admin-input"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">لینک</label>
                    <input type="text" wire:model="link" placeholder="https://example.com یا /page" class="admin-input">
                    @error('link') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">رنگ پس‌زمینه (کد رنگ hex)</label>
                        <input type="text" wire:model="backgroundColor" placeholder="#F5F5F5" dir="ltr" class="admin-input">
                        @error('backgroundColor') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">رنگ متن (کد رنگ hex)</label>
                        <input type="text" wire:model="textColor" placeholder="#1F2937" dir="ltr" class="admin-input">
                        @error('textColor') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">تاریخ شروع (اختیاری)</label>
                        <x-jalali-date-input mode="datetime" wire:model="startDate" id="announcement_start_date" />
                        @error('startDate') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">تاریخ پایان (اختیاری)</label>
                        <x-jalali-date-input mode="datetime" wire:model="endDate" id="announcement_end_date" />
                        @error('endDate') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex items-center gap-2 pb-1">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_active" class="text-sm text-gray-700">فعال</label>
                    </div>
                    <div class="pb-1">
                        <label for="sort_order" class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" id="sort_order" min="0" class="admin-input !w-32">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا متن..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">عنوان</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">ترتیب</th>
                    <th class="admin-th">تاریخ شروع</th>
                    <th class="admin-th">تاریخ پایان</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($announcements as $announcement)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $announcement->id }}</td>
                        <td class="admin-td font-medium">{{ $announcement->title }}</td>
                        <td class="admin-td">
                            <span class="{{ $announcement->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $announcement->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td text-gray-500">{{ $announcement->sort_order }}</td>
                        <td class="admin-td text-gray-500">{{ $announcement->start_date ? jalali_date($announcement->start_date, 'datetime') : '—' }}</td>
                        <td class="admin-td text-gray-500">{{ $announcement->end_date ? jalali_date($announcement->end_date, 'datetime') : '—' }}</td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $announcement->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $announcement->id }})" wire:confirm="آیا از حذف این اطلاعیه مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="admin-empty">اطلاعیه‌ای یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $announcements->links() }}</div>
    </div>
</div>