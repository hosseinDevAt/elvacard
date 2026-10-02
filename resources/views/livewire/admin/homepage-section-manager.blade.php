<div>
    <x-admin.page-header
        title="بخش‌های صفحه اصلی"
        description="مدیریت، چیدمان و فعال‌سازی بلوک‌ها و بخش‌های نمایشی صفحه اصلی"
    >
        <x-slot:actions>
            <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>بخش جدید</span>
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-slate-900 mb-4">{{ $editingId ? 'ویرایش بخش' : 'بخش جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="admin-label">نوع بخش</label>
                        <select wire:model="sectionType" class="admin-input">
                            @foreach($sectionTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->faLabel() }}</option>
                            @endforeach
                        </select>
                        @error('sectionType') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">عنوان</label>
                        <input type="text" wire:model="title" class="admin-input" placeholder="عنوان نمایشی بخش">
                        @error('title') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">ترتیب</label>
                        <input type="number" min="0" wire:model="sortOrder" class="admin-input">
                        @error('sortOrder') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="admin-label">محتوا (اختیاری)</label>
                    <textarea wire:model="content" rows="3" class="admin-input" placeholder="توضیحات تکمیلی یا متن زیر عنوان"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                @if($sectionType === 'featured_products')
                    <div>
                        <label class="admin-label mb-2">انتخاب محصولات ویژه</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                            @forelse($products as $product)
                                <label class="flex items-center gap-2 text-sm text-slate-700 py-1 hover:text-slate-900 cursor-pointer">
                                    <input type="checkbox" wire:model="productIds" value="{{ $product->id }}" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                                    {{ $product->name }}
                                </label>
                            @empty
                                <p class="text-sm text-slate-400 col-span-full py-2">محصولی برای انتخاب وجود ندارد</p>
                            @endforelse
                        </div>
                        @error('productIds') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($sectionType === 'featured_designs')
                    <div>
                        <label class="admin-label mb-2">انتخاب طرح‌های ویژه</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-3 bg-slate-50/50">
                            @forelse($designs as $design)
                                <label class="flex items-center gap-2 text-sm text-slate-700 py-1 hover:text-slate-900 cursor-pointer">
                                    <input type="checkbox" wire:model="designIds" value="{{ $design->id }}" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                                    {{ $design->name }}
                                </label>
                            @empty
                                <p class="text-sm text-slate-400 col-span-full py-2">طرحی برای انتخاب وجود ندارد</p>
                            @endforelse
                        </div>
                        @error('designIds') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if(in_array($sectionType, ['featured_products', 'featured_designs'], true))
                    <div class="sm:w-64">
                        <label class="admin-label">حداکثر تعداد نمایش</label>
                        <input type="number" min="1" max="100" wire:model="limit" class="admin-input">
                        @error('limit') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($sectionType === 'faq')
                    <div class="sm:w-64">
                        <label class="admin-label">تعداد سوالات متداول (حداکثر)</label>
                        <input type="number" min="1" max="100" wire:model="faqLimit" placeholder="خالی = همه" class="admin-input">
                        @error('faqLimit') <p class="admin-error">{{ $message }}</p> @enderror
                        <p class="text-xs text-slate-400 mt-1">{{ $faqsCount }} سوال فعال موجود است.</p>
                    </div>
                @endif

                @if(! in_array($sectionType, ['featured_products', 'featured_designs', 'faq'], true))
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">رنگ پس‌زمینه</label>
                            <input type="text" wire:model="backgroundColor" placeholder="#1a1a2e" dir="ltr" class="admin-input font-mono text-xs">
                            @error('backgroundColor') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">مسیر تصویر پس‌زمینه</label>
                            <input type="text" wire:model="backgroundImage" placeholder="images/hero.jpg" dir="ltr" class="admin-input font-mono text-xs">
                            @error('backgroundImage') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">متن دکمه</label>
                            <input type="text" wire:model="ctaText" class="admin-input">
                            @error('ctaText') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">لینک دکمه</label>
                            <input type="text" wire:model="ctaUrl" placeholder="https://example.com یا /pages/x" dir="ltr" class="admin-input font-mono text-xs">
                            @error('ctaUrl') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                        <label for="is_active" class="text-sm font-medium text-slate-700">فعال</label>
                    </div>
                    <div class="flex items-center gap-2 ms-auto">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <span>ذخیره</span>
                        </button>
                        <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="admin-btn admin-btn-secondary">
                            <span>لغو</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <div class="relative sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا محتوا..." class="admin-input ps-9">
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
                        <th class="admin-th">نوع</th>
                        <th class="admin-th">عنوان</th>
                        <th class="admin-th">ترتیب</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sections as $section)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-slate-400 font-mono text-xs">{{ $section->id }}</td>
                            <td class="admin-td">
                                <span class="admin-badge admin-badge-neutral text-xs" dir="ltr">{{ $section->section_type?->value }}</span>
                            </td>
                            <td class="admin-td font-medium text-slate-900">{{ $section->title ?? '—' }}</td>
                            <td class="admin-td text-slate-500 font-mono text-xs">{{ $section->sort_order }}</td>
                            <td class="admin-td">
                                <span class="admin-badge {{ $section->is_active ? 'admin-badge-emerald' : 'admin-badge-gray' }}">
                                    {{ $section->is_active ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="admin-td text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <button wire:click="edit({{ $section->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">ویرایش</button>
                                    <button wire:click="delete({{ $section->id }})" wire:confirm="آیا از حذف این بخش مطمئن هستید؟" class="text-xs text-rose-600 hover:text-rose-700 px-2 py-1.5 font-medium transition">حذف</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8">
                                <x-admin.empty-state
                                    title="بخشی یافت نشد"
                                    description="هنوز هیچ بخشی برای صفحه اصلی تعریف نشده است."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>