<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت اطلاعیه‌ها</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + اطلاعیه جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش اطلاعیه' : 'اطلاعیه جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عنوان</label>
                    <input type="text" wire:model="title" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">متن</label>
                    <textarea wire:model="content" rows="4" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">لینک</label>
                    <input type="text" wire:model="link" placeholder="https://example.com یا /page" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('link') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رنگ پس‌زمینه (کلاس)</label>
                        <input type="text" wire:model="backgroundColor" placeholder="bg-primary-50" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('backgroundColor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رنگ متن (کلاس)</label>
                        <input type="text" wire:model="textColor" placeholder="text-primary-900" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('textColor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تاریخ شروع (اختیاری)</label>
                        <input type="datetime-local" wire:model="startDate" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('startDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تاریخ پایان (اختیاری)</label>
                        <input type="datetime-local" wire:model="endDate" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('endDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex items-center gap-2 pb-1">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_active" class="text-sm text-gray-700">فعال</label>
                    </div>
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا متن..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عنوان</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ شروع</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ پایان</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($announcements as $announcement)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $announcement->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $announcement->title }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $announcement->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $announcement->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $announcement->start_date?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $announcement->end_date?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $announcement->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $announcement->id }})" wire:confirm="آیا از حذف این اطلاعیه مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">اطلاعیه‌ای یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $announcements->links() }}</div>
    </div>
</div>