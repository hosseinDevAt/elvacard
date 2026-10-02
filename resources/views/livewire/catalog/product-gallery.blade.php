<div class="grid gap-8 lg:grid-cols-2">
    <div>
        <div class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm">
            @if ($mainImagePath)
                <img src="{{ asset('storage/' . $mainImagePath) }}"
                     alt="{{ $product->name }}"
                     class="h-full w-full object-cover">
            @else
                <x-icons.photo-placeholder class="h-20 w-20 text-slate-300" />
            @endif
        </div>

        @if (count($gallery) > 1)
            <div class="mt-4 grid grid-cols-4 gap-3">
                @foreach ($gallery as $index => $imagePath)
                    <button
                        type="button"
                        wire:click="selectImage({{ $index }})"
                        class="aspect-square overflow-hidden rounded-xl border {{ $selected_image_index === $index ? 'border-[#010619] ring-2 ring-[#ffde5b] shadow-xs' : 'border-slate-200 opacity-80 hover:opacity-100' }} bg-white transition hover:border-slate-300"
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

    <div class="flex flex-col gap-5">
        @if ($hasColors)
            <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
                <h2 class="mb-3 text-xs font-bold text-slate-700 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#ffde5b]"></span>
                    <span>انتخاب رنگ:</span>
                </h2>
                <div class="flex flex-wrap gap-2.5">
                    @foreach ($colors as $colorOption)
                        <button
                            type="button"
                            wire:click="selectColor({{ $colorOption['color_id'] }})"
                            class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs sm:text-sm font-semibold transition {{ (int) $selectedVariant?->color_id === (int) $colorOption['color_id'] ? 'border-[#010619] bg-[#ffde5b]/20 ring-2 ring-[#ffde5b] text-[#010619] shadow-xs' : 'border-slate-200 bg-white hover:border-slate-300 text-slate-700' }}"
                        >
                            <span class="h-4 w-4 rounded-full border border-black/15 shadow-xs" style="background-color: {{ $colorOption['color_hex'] }}"></span>
                            <span>{{ $colorOption['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
            <div class="text-xs font-bold text-slate-500 mb-1">قیمت واحد</div>
            @if ($unitPrice !== null)
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($unitPrice) }}</span>
                    <span class="text-sm font-semibold text-slate-500">تومان</span>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('cart.add') }}" class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product_id }}">
            @if ($submitColorId !== null)
                <input type="hidden" name="color_id" value="{{ $submitColorId }}">
            @endif

            <div class="flex flex-wrap items-end gap-4">
                <div class="w-28">
                    <label for="store_quantity" class="mb-1.5 block text-xs font-bold text-slate-700">تعداد</label>
                    <input
                        type="number"
                        name="quantity"
                        id="store_quantity"
                        wire:model="quantity"
                        min="1"
                        max="20"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-center font-bold text-slate-900 focus:border-[#010619] focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                    >
                </div>
                <button type="submit" class="flex-1 btn-brand-primary py-3 text-sm font-bold shadow-md shadow-[#ffde5b]/25 hover:scale-[1.02] active:scale-[0.98]">
                    <x-icons.cart class="w-5 h-5" />
                    <span>افزودن به سبد خرید</span>
                </button>
            </div>
        </form>
    </div>
</div>