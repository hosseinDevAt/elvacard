<div class="grid gap-8 lg:grid-cols-12 items-start">
    {{-- Image / Gallery Column --}}
    <div class="lg:col-span-6 space-y-4">
        <div class="relative aspect-square overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-sm flex items-center justify-center group">
            @if ($mainImagePath)
                <img src="{{ asset('storage/' . $mainImagePath) }}"
                     alt="{{ $product->name }}"
                     class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-105">
            @else
                <div class="flex flex-col items-center justify-center gap-3 text-slate-300">
                    <x-icons.photo-placeholder class="h-20 w-20" />
                    <span class="text-xs font-medium text-slate-400">تصویر اختصاصی محصول</span>
                </div>
            @endif

            <div class="absolute top-4 start-4 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#010619]/90 text-[#ffde5b] shadow-sm backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-[#ffde5b] animate-pulse"></span>
                    <span>اصل الواکارت</span>
                </span>
            </div>
        </div>

        @if (count($gallery) > 1)
            <div class="grid grid-cols-4 sm:grid-cols-5 gap-3">
                @foreach ($gallery as $index => $imagePath)
                    <button
                        type="button"
                        wire:click="selectImage({{ $index }})"
                        class="aspect-square overflow-hidden rounded-2xl border-2 {{ $selected_image_index === $index ? 'border-[#010619] ring-2 ring-[#ffde5b] shadow-md' : 'border-slate-200 hover:border-slate-400 opacity-80 hover:opacity-100' }} bg-white transition cursor-pointer p-1"
                    >
                        <img src="{{ asset('storage/' . $imagePath) }}"
                             alt="{{ $product->name }}"
                             loading="lazy"
                             class="h-full w-full object-cover rounded-xl">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Product Purchasing & Options Column --}}
    <div class="lg:col-span-6 flex flex-col gap-6">
        {{-- Color Selection --}}
        @if ($hasColors)
            <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-slate-700 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                        <span>انتخاب رنگ ورقه فلزی:</span>
                    </h3>
                    @if($selectedVariant && $selectedVariant->color)
                        <span class="text-xs font-black text-[#010619] bg-slate-100 px-2.5 py-1 rounded-lg">
                            {{ $selectedVariant->color->name }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    @foreach ($colors as $colorOption)
                        @php
                            $isSelected = (int) ($selectedVariant?->color_id) === (int) $colorOption['color_id'];
                        @endphp
                        <button
                            type="button"
                            wire:click="selectColor({{ $colorOption['color_id'] }})"
                            class="inline-flex items-center gap-2.5 rounded-2xl border px-4 py-2.5 text-xs sm:text-sm font-bold transition cursor-pointer {{ $isSelected ? 'border-[#010619] bg-[#010619] text-[#ffde5b] shadow-md ring-2 ring-[#ffde5b]/60' : 'border-slate-200 bg-white hover:border-slate-300 text-slate-700 hover:bg-slate-50' }}"
                        >
                            <span class="h-4 w-4 rounded-full border border-black/20 shadow-xs shrink-0" style="background-color: {{ $colorOption['color_hex'] }}"></span>
                            <span>{{ $colorOption['name'] }}</span>
                            @if(isset($colorOption['price']) && $colorOption['price'] > 0)
                                <span class="text-[11px] font-normal opacity-80">{{ number_format($colorOption['price']) }} ت</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Price Box --}}
        <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 block mb-1">قیمت نهایی محصول</span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>موجود در انبار و آماده ارسال</span>
                </span>
            </div>

            @if ($unitPrice !== null)
                <div class="text-end">
                    <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ number_format($unitPrice) }}</span>
                    <span class="text-xs font-semibold text-slate-500 ms-1">تومان</span>
                </div>
            @endif
        </div>

        {{-- Add to Cart Form --}}
        <form method="POST" action="{{ route('cart.add') }}" class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product_id }}">
            @if ($submitColorId !== null)
                <input type="hidden" name="color_id" value="{{ $submitColorId }}">
            @endif

            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
                <div class="sm:w-36">
                    <label for="store_quantity" class="mb-2 block text-xs font-bold text-slate-700">تعداد سفارش</label>
                    <div class="flex items-center rounded-2xl border border-slate-200 overflow-hidden bg-slate-50/50">
                        <button type="button" onclick="const q=document.getElementById('store_quantity'); q.value=Math.max(1, parseInt(q.value||1)-1); q.dispatchEvent(new Event('input'))"
                                class="w-10 h-11 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 font-bold text-base cursor-pointer">
                            -
                        </button>
                        <input
                            type="number"
                            name="quantity"
                            id="store_quantity"
                            wire:model="quantity"
                            min="1"
                            max="20"
                            dir="ltr"
                            class="w-full text-center font-black text-slate-900 bg-transparent border-none focus:outline-none focus:ring-0 p-0 text-sm"
                        >
                        <button type="button" onclick="const q=document.getElementById('store_quantity'); q.value=Math.min(20, parseInt(q.value||1)+1); q.dispatchEvent(new Event('input'))"
                                class="w-10 h-11 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 font-bold text-base cursor-pointer">
                            +
                        </button>
                    </div>
                </div>

                <button type="submit" class="flex-1 btn-brand-primary py-3.5 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                    <x-icons.cart class="w-5 h-5" />
                    <span>افزودن به سبد خرید</span>
                </button>
            </div>
        </form>
    </div>
</div>