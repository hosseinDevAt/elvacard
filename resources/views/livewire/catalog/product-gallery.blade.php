<div class="grid gap-8 lg:grid-cols-2">
    <div>
        <div class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
            @if ($mainImagePath)
                <img src="{{ asset('storage/' . $mainImagePath) }}"
                     alt="{{ $product->name }}"
                     class="h-full w-full object-cover">
            @else
                <x-icons.photo-placeholder class="h-20 w-20 text-gray-300" />
            @endif
        </div>

        @if (count($gallery) > 1)
            <div class="mt-3 grid grid-cols-4 gap-2">
                @foreach ($gallery as $index => $imagePath)
                    <button
                        type="button"
                        wire:click="selectImage({{ $index }})"
                        class="aspect-square overflow-hidden rounded-lg border {{ $selected_image_index === $index ? 'border-yellow-500 ring-2 ring-yellow-200' : 'border-gray-200' }} bg-gray-50 transition hover:border-gray-300"
                    >
                        <img src="{{ asset('storage/' . $imagePath) }}"
                             alt="{{ $product->name }}"
                             loading="lazy"
                             class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-col gap-6">
        @if ($hasColors)
            <div>
                <h2 class="mb-2 text-sm font-medium text-gray-700">رنگ</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($colors as $colorOption)
                        <button
                            type="button"
                            wire:click="selectColor({{ $colorOption['color_id'] }})"
                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ (int) $selectedVariant?->color_id === (int) $colorOption['color_id'] ? 'border-yellow-500 bg-yellow-50 ring-1 ring-yellow-200' : 'border-gray-300 hover:border-gray-400' }}"
                        >
                            <span class="h-5 w-5 rounded-full border border-black/10" style="background-color: {{ $colorOption['color_hex'] }}"></span>
                            <span>{{ $colorOption['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <div class="text-sm text-gray-500">قیمت</div>
            @if ($unitPrice !== null)
                <div class="mt-1 text-3xl font-bold text-gray-900">
                    {{ number_format($unitPrice) }}
                    <span class="text-base font-medium text-gray-500">تومان</span>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('cart.add') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product_id }}">
            @if ($submitColorId !== null)
                <input type="hidden" name="color_id" value="{{ $submitColorId }}">
            @endif

            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="store_quantity" class="mb-1 block text-sm font-medium text-gray-700">تعداد</label>
                    <input
                        type="number"
                        name="quantity"
                        id="store_quantity"
                        wire:model="quantity"
                        min="1"
                        max="20"
                        dir="ltr"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition"
                    >
                </div>
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600 transition">
                    افزودن به سبد خرید
                </button>
            </div>
        </form>
    </div>
</div>