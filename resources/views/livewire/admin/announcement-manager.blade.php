<div>
    <x-admin.page-header title="اطلاعیه‌ها و نوار اعلانات" subtitle="مدیریت بنرهای اعلاناتی، متون هشدار و اطلاع‌رسانی بالای سایت">
        <x-slot:actions>
            <button wire:click="$set('showForm', true)" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-[#ffde5b]/25">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>اطلاعیه جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش اطلاعیه' : 'اطلاعیه جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="admin-label">عنوان اطلاعیه</label>
                    <input type="text" wire:model="title" class="admin-input">
                    @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">متن اطلاعیه</label>
                    <textarea wire:model="content" rows="4" class="admin-input"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="admin-label">لینک مقصد (اختیاری)</label>
                    <input type="text" wire:model="link" placeholder="https://example.com یا /page" dir="ltr" class="admin-input">
                    @error('link') <p class="admin-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">رنگ پس‌زمینه (کد رنگ hex)</label>
                        <input type="text" wire:model="backgroundColor" placeholder="#F5F5F5" dir="ltr" class="admin-input font-mono">
                        @error('backgroundColor') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">رنگ متن (کد رنگ hex)</label>
                        <input type="text" wire:model="textColor" placeholder="#1F2937" dir="ltr" class="admin-input font-mono">
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
                <div class="flex flex-wrap items-end gap-6 pt-1">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="is_active" class="text-xs font-semibold text-slate-700">فعال (نمایش در سایت)</label>
                    </div>
                    <div>
                        <label for="sort_order" class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" id="sort_order" min="0" class="admin-input !w-32">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : 'ایجاد اطلاعیه' }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">لیست اطلاعیه‌ها</h3>
            <div class="w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا متن..." class="admin-input py-2 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">عنوان</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">ترتیب</th>
                        <th class="admin-th">تاریخ شروع</th>
                        <th class="admin-th">تاریخ پایان</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($announcements as $announcement)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $announcement->id }}</td>
                            <td class="admin-td font-bold text-slate-900">{{ $announcement->title }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $announcement->is_active ? 'admin-badge-success' : 'admin-badge-neutral' }}">
                                    {{ $announcement->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-slate-400 text-xs font-mono">{{ $announcement->sort_order }}</td>
                            <td class="admin-td text-slate-500 text-xs">{{ $announcement->start_date ? jalali_date($announcement->start_date, 'datetime') : '—' }}</td>
                            <td class="admin-td text-slate-500 text-xs">{{ $announcement->end_date ? jalali_date($announcement->end_date, 'datetime') : '—' }}</td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $announcement->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $announcement->id }})" wire:confirm="آیا از حذف این اطلاعیه مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8">
                                <x-admin.empty-state title="اطلاعیه‌ای یافت نشد" description="هیچ اطلاعیه‌ای مطابق با جستجوی فعلی پیدا نشد." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $announcements->links() }}</div>
    </div>
</div>