<div>
    <x-admin.page-header
        title="تصاویر طرح‌ها"
        description="مدیریت، بارگذاری و تنظیم تصاویر نسخه‌های رنگی هر طرح کارت"
    >
        <x-slot:actions>
            <button wire:click="create" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>تصویر جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش تصویر' : 'تصویر جدید' }}</h3>
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
                    <input type="text" wire:model="imagePath" placeholder="designs/eagle-black.png" dir="ltr" class="admin-input font-mono text-xs">
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
                        <input type="text" wire:model="optimizedFilename" placeholder="eagle-black-optimized.webp" dir="ltr" class="admin-input font-mono text-xs">
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
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                    <label for="is_active" class="text-sm font-medium text-slate-700">فعال (نمایش در سفارشی‌ساز)</label>
                </div>
                <div class="flex items-center gap-2 pt-2">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <span>ذخیره</span>
                    </button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary">
                        <span>لغو</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-4 mb-4">
        <div>
            <select wire:model.live="designFilter" class="admin-input sm:w-64 text-xs">
                <option value="">همه طرح‌ها</option>
                @foreach($designFilterOptions as $design)
                    <option value="{{ $design->id }}">{{ $design->name }}</option>
                @endforeach
            </select>
            @error('designFilter')
                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="relative sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در مسیر تصویر..." class="admin-input ps-9">
            <svg class="w-4 h-4 text-slate-400 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">طرح</th>
                        <th class="admin-th">رنگ</th>
                        <th class="admin-th">مسیر</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($images as $image)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $image->id }}</td>
                            <td class="admin-td font-medium text-slate-900">{{ $image->design?->name }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full border border-slate-200 shadow-sm shrink-0" style="background-color: {{ $image->color?->code_hex }}"></span>
                                    <span class="text-xs text-slate-700">{{ $image->color?->name }}</span>
                                </div>
                            </td>
                            <td class="admin-td text-slate-500 font-mono text-xs break-all" dir="ltr">{{ $image->image_path }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $image->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                    {{ $image->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $image->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $image->id }})" wire:confirm="آیا از حذف این تصویر (به همراه سازگاری‌هایش) مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8">
                                <x-admin.empty-state
                                    title="تصویری یافت نشد"
                                    description="هنوز هیچ تصویری برای طرح‌های انتخاب‌شده ثبت نشده است."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($images->hasPages())
            <div class="p-4 border-t border-slate-100">{{ $images->links() }}</div>
        @endif
    </div>
</div>