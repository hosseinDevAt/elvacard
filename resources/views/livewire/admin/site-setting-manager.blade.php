<div>
    <x-admin.page-header title="تنظیمات سامانه" subtitle="مدیریت کلیدها و پیکربندی‌های عمومی و ساختاری سایت">
        <x-slot:actions>
            <button type="button" wire:click="$set('showForm', true)" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-[#ffde5b]/25">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>تنظیمات جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش تنظیمات' : 'تنظیمات جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="admin-label">کلید (key)</label>
                        <input type="text" wire:model="key" placeholder="site_name" dir="ltr" class="admin-input font-mono text-xs">
                        @error('key') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">گروه</label>
                        <input type="text" wire:model="group" placeholder="general" dir="ltr" class="admin-input font-mono text-xs">
                        @error('group') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">نوع مقدار</label>
                        <select wire:model="type" class="admin-select text-xs">
                            <option value="string">string (متن)</option>
                            <option value="integer">integer (عدد)</option>
                            <option value="boolean">boolean (منطقی)</option>
                            <option value="json">json (ساختاریافته)</option>
                        </select>
                        @error('type') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">مقدار (value)</label>
                    @if($type === 'boolean')
                        <select wire:model="value" class="admin-select text-xs">
                            <option value="1">true (فعال / بله)</option>
                            <option value="0">false (غیرفعال / خیر)</option>
                        </select>
                    @elseif($type === 'json')
                        <textarea wire:model="value" rows="4" dir="ltr" placeholder='{"key": "value"}' class="admin-input font-mono text-xs"></textarea>
                    @else
                        <input type="{{ $type === 'integer' ? 'number' : 'text' }}" wire:model="value" dir="ltr" class="admin-input font-mono text-xs">
                    @endif
                    @error('value') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" wire:model="isPublic" id="is_public" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="is_public" class="text-xs font-semibold text-slate-700">عمومی (قابل استفاده در قالب و فرانت‌اند)</label>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : 'ایجاد تنظیمات' }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <span class="text-xs font-semibold text-slate-500">جستجو در تنظیمات:</span>
            <div class="w-full sm:w-80">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در کلید، مقدار یا گروه..." class="admin-input py-2 text-xs">
            </div>
        </div>
    </div>

    @forelse($settings as $group => $groupSettings)
        <div class="admin-card overflow-hidden mb-6">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-[#010619]/10 text-[#010619] text-xs font-bold">
                        #
                    </span>
                    <h3 class="text-sm font-extrabold text-slate-800 font-mono tracking-wide uppercase">{{ $group }}</h3>
                </div>
                <span class="admin-badge admin-badge-neutral text-xs font-mono">{{ $groupSettings->count() }} رکورد</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="admin-th">کلید (Key)</th>
                            <th class="admin-th">مقدار (Value)</th>
                            <th class="admin-th w-28">نوع</th>
                            <th class="admin-th w-28">دسترسی</th>
                            <th class="admin-th text-center w-32">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($groupSettings as $setting)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="admin-td font-mono font-bold text-slate-800 text-xs" dir="ltr">{{ $setting->key }}</td>
                                <td class="admin-td text-slate-600 font-mono text-xs max-w-md truncate" dir="ltr">{{ $setting->value }}</td>
                                <td class="admin-td">
                                    <span class="admin-badge admin-badge-neutral text-[11px] font-mono" dir="ltr">{{ $setting->type }}</span>
                                </td>
                                <td class="admin-td">
                                    <span class="admin-badge {{ $setting->is_public ? 'admin-badge-success' : 'admin-badge-neutral' }}">
                                        {{ $setting->is_public ? 'عمومی' : 'اختصاصی' }}
                                    </span>
                                </td>
                                <td class="admin-td text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" wire:click="edit({{ $setting->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                        <button type="button" wire:click="delete({{ $setting->id }})" wire:confirm="آیا از حذف این تنظیم مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="admin-card p-8">
            <x-admin.empty-state title="تنظیماتی یافت نشد" description="هیچ تنظیمی با فیلتر جستجوی فعلی پیدا نشد." />
        </div>
    @endforelse
</div>