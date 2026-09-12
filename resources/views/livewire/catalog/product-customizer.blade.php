<section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <h2 class="text-lg font-semibold text-gray-900">سفارشی‌ساز محصول</h2>
    <p class="mt-1 text-sm text-gray-500">رنگ، طرح و تصویر را انتخاب کنید.</p>

    <div class="mt-4 space-y-6">
        <div>
            <h3 class="mb-2 text-sm font-semibold text-gray-700">۱) رنگ و قیمت</h3>
            <div class="grid gap-2">
                @forelse($colorPrices as $priceItem)
                    <button
                        type="button"
                        wire:click="selectColor({{ $priceItem->color_id }})"
                        class="flex items-center justify-between rounded border px-3 py-2 text-sm text-start {{ (int) $color_id === (int) $priceItem->color_id ? 'border-primary-600 bg-primary-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
                    >
                        <span>{{ $priceItem->color?->name ?? 'ناموجود' }}</span>
                        <span class="font-semibold">{{ number_format($priceItem->price) }} تومان</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">رنگ فعالی موجود نیست.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-semibold text-gray-700">۲) طرح</h3>
            <div class="grid gap-2 sm:grid-cols-2">
                @forelse($designOptions as $design)
                    <button
                        type="button"
                        wire:click="selectDesign({{ $design->id }})"
                        class="rounded border px-3 py-2 text-start text-sm {{ (int) $design_id === (int) $design->id ? 'border-primary-600 bg-primary-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
                    >
                        <span class="font-medium">{{ $design->name }}</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">طرح سازگاری با رنگ انتخابی یافت نشد.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-semibold text-gray-700">۳) تصویر طرح</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($designImageOptions->where('design_id', $design_id) as $image)
                    <button
                        type="button"
                        wire:click="selectDesignImage({{ $image->id }})"
                        class="rounded border p-2 text-start text-sm {{ (int) $design_image_id === (int) $image->id ? 'border-primary-600 bg-primary-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
                    >
                        <p class="font-medium">{{ $image->color?->name ?? 'ناموجود' }}</p>
                        <p class="mt-1 break-all text-xs text-gray-500">{{ $image->image_path }}</p>
                    </button>
                @empty
                    <p class="text-sm text-gray-500">تصویری برای طرح انتخابی موجود نیست.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">اطلاعات انتخاب شده</h3>
            <div class="space-y-1 text-sm text-gray-700">
                <p>شناسه محصول: {{ $customization_json['product_id'] ?? '-' }}</p>
                <p>شناسه رنگ: {{ $customization_json['color_id'] ?? '-' }}</p>
                <p>شناسه طرح: {{ $customization_json['design_id'] ?? '-' }}</p>
                <p>شناسه تصویر طرح: {{ $customization_json['design_image_id'] ?? '-' }}</p>
            </div>
        </div>

        <div class="rounded-md border border-gray-200 bg-white p-3">
            <label class="mb-2 block text-sm font-semibold text-gray-700" for="quantity">تعداد</label>
            <input id="quantity" type="number" min="1" max="20" wire:model.live="quantity" class="w-28 rounded border border-gray-300 px-3 py-2 text-sm" />
            @error('quantity')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="button" wire:click="addToCart" class="inline-flex rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">
                افزودن به سبد خرید
            </button>
            <a href="{{ route('cart.index') }}" class="text-sm text-primary-600 hover:text-primary-800 transition">مشاهده سبد خرید</a>
        </div>
    </div>
</section>