<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="$set('showForm', true)" class="admin-btn admin-btn-primary">
            + بخش جدید
        </button>
    </div>

    @if($showForm)
        <div class="admin-card p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش بخش' : 'بخش جدید' }}</h3>
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
                        <input type="text" wire:model="title" class="admin-input">
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
                    <textarea wire:model="content" rows="3" class="admin-input"></textarea>
                    @error('content') <p class="admin-error">{{ $message }}</p> @enderror
                </div>

                @if($sectionType === 'featured_products')
                    <div>
                        <label class="admin-label mb-2">انتخاب محصولات ویژه</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                            @forelse($products as $product)
                                <label class="flex items-center gap-2 text-sm text-gray-700 py-1">
                                    <input type="checkbox" wire:model="productIds" value="{{ $product->id }}" class="rounded border-gray-300 text-yellow-500">
                                    {{ $product->name }}
                                </label>
                            @empty
                                <p class="text-sm text-gray-400 col-span-full">محصولی برای انتخاب وجود ندارد</p>
                            @endforelse
                        </div>
                        @error('productIds') <p class="admin-error">{{ $message }}</p> @enderror
                    </div>
                @elseif($sectionType === 'featured_designs')
                    <div>
                        <label class="admin-label mb-2">انتخاب طرح‌های ویژه</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                            @forelse($designs as $design)
                                <label class="flex items-center gap-2 text-sm text-gray-700 py-1">
                                    <input type="checkbox" wire:model="designIds" value="{{ $design->id }}" class="rounded border-gray-300 text-yellow-500">
                                    {{ $design->name }}
                                </label>
                            @empty
                                <p class="text-sm text-gray-400 col-span-full">طرحی برای انتخاب وجود ندارد</p>
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
                        <p class="text-xs text-gray-400 mt-1">{{ $faqsCount }} سوال فعال موجود است.</p>
                    </div>
                @endif

                @if(! in_array($sectionType, ['featured_products', 'featured_designs', 'faq'], true))
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="admin-label">رنگ پس‌زمینه</label>
                            <input type="text" wire:model="backgroundColor" placeholder="#1a1a2e" dir="ltr" class="admin-input">
                            @error('backgroundColor') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">مسیر تصویر پس‌زمینه</label>
                            <input type="text" wire:model="backgroundImage" placeholder="images/hero.jpg" dir="ltr" class="admin-input">
                            @error('backgroundImage') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">متن دکمه</label>
                            <input type="text" wire:model="ctaText" class="admin-input">
                            @error('ctaText') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">لینک دکمه</label>
                            <input type="text" wire:model="ctaUrl" placeholder="https://example.com یا /pages/x" dir="ltr" class="admin-input">
                            @error('ctaUrl') <p class="admin-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

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
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در عنوان یا محتوا..." class="admin-input sm:w-80">
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">#</th>
                    <th class="admin-th">نوع</th>
                    <th class="admin-th">عنوان</th>
                    <th class="admin-th">ترتیب</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sections as $section)
                    <tr class="hover:bg-gray-50">
                        <td class="admin-td text-gray-500">{{ $section->id }}</td>
                        <td class="admin-td">
                            <span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-600 text-xs" dir="ltr">{{ $section->section_type?->value }}</span>
                        </td>
                        <td class="admin-td font-medium">{{ $section->title ?? '—' }}</td>
                        <td class="admin-td text-gray-500">{{ $section->sort_order }}</td>
                        <td class="admin-td">
                            <span class="{{ $section->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $section->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="admin-td">
                            <button wire:click="edit({{ $section->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $section->id }})" wire:confirm="آیا از حذف این بخش مطمئن هستید؟" class="text-rose-600 hover:text-rose-700 text-xs font-medium transition">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty">بخشی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>