<div>
    <x-admin.page-header title="مدیریت رنگ‌های پایه" subtitle="مدیریت کدهای رنگی و پالت‌های عمومی مورد استفاده در فروشگاه و کارت‌ها">
        <x-slot:actions>
            <button type="button" wire:click="create" type="button" class="admin-btn admin-btn-primary gap-2 text-xs font-semibold shadow-md shadow-[#ffde5b]/25">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>افزودن رنگ جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش رنگ' : 'رنگ جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-full sm:flex-1">
                        <label class="admin-label">نام رنگ</label>
                        <input type="text" wire:model="name" class="admin-input">
                        @error('name') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="w-52">
                        <label class="admin-label">کد رنگ (HEX)</label>
                        <div class="flex items-center gap-2">
                            <input type="color" wire:model.live="colorCode" class="h-11 w-12 shrink-0 rounded-xl border border-slate-200 cursor-pointer p-1 bg-white">
                            <input type="text" wire:model.live.debounce.300ms="colorCode" dir="ltr" maxlength="7" placeholder="#FF5733" class="admin-input font-mono text-xs uppercase flex-1">
                        </div>
                        @error('colorCode') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    @if($colorCode)
                        <div class="w-24">
                            <label class="admin-label">پیش‌نمایش سطح</label>
                            <div class="relative w-20 h-11 rounded-xl border border-slate-200 shadow-xs overflow-hidden flex items-center justify-center" style="background-color: {{ $colorCode }}">
                                <x-card-surface-texture :scratched="$isScratched" />
                                <span class="relative z-10 text-[10px] font-bold {{ in_array(strtolower($colorCode), ['#ffffff', '#fff']) ? 'text-slate-800' : 'text-white' }}">
                                    {{ $isScratched ? 'خش‌دار' : 'ساده' }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="admin-label">مسیر تصویر (اختیاری)</label>
                        <input type="text" wire:model="previewImage" placeholder="colors/black.png" dir="ltr" class="admin-input">
                        @error('previewImage') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">ترتیب نمایش</label>
                        <input type="number" wire:model="sortOrder" min="0" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-6 pt-1">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="is_active" class="text-xs font-semibold text-slate-700">فعال (قابل انتخاب در سبد و سفارش)</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model.live="isScratched" id="is_scratched" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="is_scratched" class="text-xs font-semibold text-slate-700">بافت سطح کارت: خش‌دار (متالیک براش‌خورده)</label>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">{{ $editingId ? 'ذخیره تغییرات' : 'ایجاد رنگ' }}</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary admin-btn-sm">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">لیست رنگ‌های ثبت شده</h3>
            <span class="text-xs text-slate-400">مجموع رنگ‌ها: {{ $colors->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">نام رنگ</th>
                        <th class="admin-th">نمونه و کد رنگ</th>
                        <th class="admin-th">بافت سطح</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">ترتیب نمایش</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($colors as $color)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $color->id }}</td>
                            <td class="admin-td font-bold text-slate-900">{{ $color->name }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-6 h-6 rounded-full border-2 border-white shadow-xs" style="background-color: {{ $color->code_hex }}"></div>
                                    <span class="text-slate-500 font-mono text-xs" dir="ltr">{{ $color->code_hex }}</span>
                                </div>
                            </td>
                            <td class="admin-td">
                                @if($color->is_scratched)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <svg class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                        خش‌دار
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        معمولی
                                    </span>
                                @endif
                            </td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $color->is_active ? 'admin-badge-success' : 'admin-badge-danger' }}">
                                    {{ $color->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-slate-400 text-xs font-mono">{{ $color->sort_order }}</td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $color->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button type="button" wire:click="delete({{ $color->id }})" wire:confirm="آیا از حذف این رنگ مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8">
                                <x-admin.empty-state title="رنگی ثبت نشده است" description="هنوز هیچ رنگی به سیستم اضافه نشده است." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $colors->links() }}</div>
    </div>
</div>