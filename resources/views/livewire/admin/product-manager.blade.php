<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت محصولات</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + محصول جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش محصول' : 'محصول جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع محصول</label>
                        <select wire:model="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @foreach($typeOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">فرآیند شخصی‌سازی</label>
                        <select wire:model="customizationWorkflow" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @foreach($workflowOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('customizationWorkflow') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @if($customizationWorkflow === \App\Enums\CustomizationWorkflowEnum::FUEL_CARD->value)
                            <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 space-y-1">
                                <p class="font-bold text-amber-900">پیش‌نیازهای فعال‌سازی و فروش کارت سوخت:</p>
                                <p class="text-amber-900">این محصول تا تکمیل پیش‌نیازهای زیر قابل فعال‌سازی نیست:</p>
                                <ul class="list-disc ms-4 space-y-0.5">
                                    @foreach($fuelPreparation as $item)
                                        <li>
                                            {{ $item['label'] }}
                                            @if($editingId)
                                                @if($item['ok'])
                                                    <span class="text-green-600">✓ تکمیل شده</span>
                                                @else
                                                    <span class="text-red-500">✗ لازم است</span>
                                                @endif
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if($editingId)
                                    <div class="pt-1">
                                        <a href="{{ route('admin.product-colors', ['product' => $editingId]) }}" class="text-amber-900 underline">مدیریت رنگ و قیمت این محصول</a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام محصول</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                    <textarea wire:model="description" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تصویر اصلی</label>
                        <input type="text" wire:model="mainImage" placeholder="products/card.jpg" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        <input type="file" wire:model="mainImageUpload" accept="image/*" class="block w-full mt-2 text-sm text-gray-600 file:me-3 file:border-0 file:bg-yellow-50 file:px-4 file:py-2 file:text-yellow-700 file:cursor-pointer">
                        @error('mainImage') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('mainImageUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @if($mainImageUpload)
                            <img src="{{ $mainImageUpload->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-lg border border-gray-200" alt="">
                        @elseif($mainImage)
                            <img src="{{ asset('storage/'.$mainImage) }}" class="mt-2 h-24 w-24 object-cover rounded-lg border border-gray-200" alt="">
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">قیمت پایه (اختیاری)</label>
                        <input type="number" wire:model="basePrice" min="0" placeholder="500000" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        @error('basePrice') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">پیکربندی طراحی (JSON، اختیاری)</label>
                    <textarea wire:model="designConfig" rows="2" dir="ltr" placeholder='{"note":"رایگان"}' class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                    @error('designConfig') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    @if($customizationWorkflow !== \App\Enums\CustomizationWorkflowEnum::FUEL_CARD->value)
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="supportsChipSelection" id="supports_chip" class="rounded border-gray-300 text-yellow-500">
                            <label for="supports_chip" class="text-sm text-gray-700">پشتیبانی از انتخاب چیپ</label>
                        </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                        <label for="is_active" class="text-sm text-gray-700">فعال (نمایش در فروشگاه)</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="robotsIndex" id="robots_index" class="rounded border-gray-300 text-yellow-500">
                        <label for="robots_index" class="text-sm text-gray-700">قابل ایندکس در موتورهای جستجو</label>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <h4 class="font-bold text-gray-900 mb-3">سئو</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">عنوان سئو</label>
                            <input type="text" wire:model="metaTitle" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('metaTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات سئو</label>
                            <textarea wire:model="metaDescription" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                            @error('metaDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">آدرس Canonical</label>
                            <input type="text" wire:model="canonicalUrl" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            @error('canonicalUrl') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">تصویر Open Graph</label>
                            <input type="text" wire:model="ogImage" dir="ltr" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <input type="file" wire:model="ogImageUpload" accept="image/*" class="block w-full mt-2 text-sm text-gray-600 file:me-3 file:border-0 file:bg-yellow-50 file:px-4 file:py-2 file:text-yellow-700 file:cursor-pointer">
                            @error('ogImage') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @error('ogImageUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @if($ogImageUpload)
                                <img src="{{ $ogImageUpload->temporaryUrl() }}" class="mt-2 h-24 w-24 object-cover rounded-lg border border-gray-200" alt="">
                            @elseif($ogImage)
                                <img src="{{ asset('storage/'.$ogImage) }}" class="mt-2 h-24 w-24 object-cover rounded-lg border border-gray-200" alt="">
                            @endif
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">محتوی سئو</label>
                        <textarea wire:model="seoContent" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"></textarea>
                        @error('seoContent') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام محصول..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
        <select wire:model.live="typeFilter" class="px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
            @foreach($typeFilterOptions as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
        <select wire:model.live="workflowFilter" class="px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
            @foreach($workflowFilterOptions as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نام</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">اسلاگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نوع</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">فرآیند</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">قیمت‌های رنگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $product->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs" dir="ltr">{{ $product->slug }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs" dir="ltr">{{ $product->type?->value }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $product->customization_workflow?->faLabel() ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($product->active_color_prices_min_price !== null)
                                <div class="text-xs text-gray-600 mb-1">از {{ number_format($product->active_color_prices_min_price) }} تومان</div>
                            @endif
                            <a href="{{ route('admin.product-colors', ['product' => $product->id]) }}" class="inline-flex items-center gap-1 text-yellow-600 hover:text-yellow-800 text-xs">
                                قیمت رنگ‌ها ({{ $product->color_prices_count }})
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $product->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $product->is_active ? 'فعال' : 'غیرفعال' }}</span>
                            @if($product->is_active)
                                @if(in_array($product->id, $purchasableIds, true))
                                    <span class="ms-2 text-green-600">قابل فروش</span>
                                @else
                                    <span class="ms-2 text-red-500">قابل فروش نیست</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $product->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $product->id }})" wire:confirm="آیا از حذف این محصول مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">محصولی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $products->links() }}</div>
    </div>
</div>