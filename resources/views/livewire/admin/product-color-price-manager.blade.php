<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">قیمت رنگ محصولات</h1>
        <button wire:click="$set('showForm', true)" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
            + قیمت جدید
        </button>
    </div>

    @if($showForm)
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="font-bold text-gray-900 mb-4">{{ $editingId ? 'ویرایش قیمت' : 'قیمت جدید' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">محصول</label>
                        <select wire:model="productId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">— انتخاب محصول —</option>
                            @foreach($productOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('productId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">رنگ</label>
                        <select wire:model="colorId" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                            <option value="">— انتخاب رنگ —</option>
                            @foreach($colorOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        @error('colorId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @if($selectedProductIsFuel)
                            <p class="text-xs text-amber-600 mt-1">کارت سوخت فقط یک رنگ فعال می‌پذیرد؛ برای تعویض رنگ فعال، ابتدا رنگ فعال فعلی را غیرفعال کنید. ردیف‌های غیرفعال اضافی مجاز هستند.</p>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">قیمت (تومان)</label>
                    <input type="number" wire:model="price" min="0" dir="ltr" class="w-full sm:w-72 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="isActive" id="is_active" class="rounded border-gray-300 text-yellow-500">
                    <label for="is_active" class="text-sm text-gray-700">فعال (قابل فروش)</label>
                </div>
                <div class="flex items-end gap-4">
                    <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">ذخیره</button>
                    <button type="button" wire:click="$set('showForm', false); $wire.resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-300 transition">لغو</button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="جستجو در نام محصول..." class="w-full sm:w-80 px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">محصول</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">رنگ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">قیمت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($prices as $priceItem)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $priceItem->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $priceItem->product?->name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded border" style="background-color: {{ $priceItem->color?->code_hex }}"></div>
                                <span>{{ $priceItem->color?->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500" dir="ltr">{{ number_format((int) $priceItem->price) }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $priceItem->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $priceItem->is_active ? 'فعال' : 'غیرفعال' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="edit({{ $priceItem->id }})" class="text-yellow-500 hover:text-yellow-700 text-xs me-2">ویرایش</button>
                            <button wire:click="delete({{ $priceItem->id }})" wire:confirm="آیا از حذف این قیمت مطمئن هستید؟" class="text-red-600 hover:text-red-800 text-xs">حذف</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">قیمتی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $prices->links() }}</div>
    </div>
</div>