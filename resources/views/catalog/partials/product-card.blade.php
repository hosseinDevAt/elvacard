@php
    $lowestPrice = $product->colorPrices?->first()?->price ?? $product->base_price;
    $hasColorVariants = $product->colorPrices && $product->colorPrices->isNotEmpty();
@endphp

{{-- Default Grid Mode Card --}}
<article x-show="viewMode === 'grid'" class="group relative flex flex-col justify-between rounded-3xl border border-slate-200/90 bg-white p-3.5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/80 hover:border-slate-300 transition-all duration-300 hover:-translate-y-1.5">
    <div>
        <a href="{{ $product->storefrontUrl() }}" wire:navigate class="block relative aspect-[4/3] overflow-hidden rounded-2xl bg-slate-50">
            @if ($product->main_image)
                <img src="{{ asset('storage/' . $product->main_image) }}"
                     alt="{{ $product->name }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200">
                    <x-icons.photo-placeholder class="text-slate-400" />
                </div>
            @endif
            <div class="absolute top-2.5 start-2.5 flex flex-wrap gap-1.5">
                <span class="rounded-full bg-[#010619]/85 text-[#ffde5b] px-3 py-1 text-[11px] font-bold backdrop-blur-md shadow-xs">
                    {{ $product->type?->faLabel() ?? $product->type }}
                </span>
            </div>
        </a>

        <div class="pt-4 px-2 pb-2">
            <div class="flex items-center justify-between mb-1.5">
                @if ($product->category)
                    <span class="text-xs text-slate-500 font-semibold">{{ $product->category->name }}</span>
                @else
                    <span class="text-xs text-slate-400 font-medium">الواکارت</span>
                @endif
                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>موجود</span>
                </span>
            </div>

            <h3 class="mb-2 font-bold text-slate-900 text-sm sm:text-base line-clamp-1 group-hover:text-[#010619] transition-colors">
                <a href="{{ $product->storefrontUrl() }}" wire:navigate>
                    {{ $product->name }}
                </a>
            </h3>

            @if ($hasColorVariants)
                <div class="flex items-center gap-1.5 mb-2.5">
                    <div class="flex -space-x-1.5 space-x-reverse">
                        @foreach($product->colorPrices->take(4) as $cp)
                            <span class="inline-block h-3.5 w-3.5 rounded-full border border-white ring-1 ring-slate-200" style="background-color: {{ $cp->color?->code_hex }}"></span>
                        @endforeach
                    </div>
                    @if($product->colorPrices->count() > 4)
                        <span class="text-[10px] text-slate-400">+{{ $product->colorPrices->count() - 4 }} رنگ</span>
                    @endif
                </div>
            @endif

            <div class="mt-2 pt-2 border-t border-slate-100 flex items-baseline justify-between">
                <span class="text-xs text-slate-500">قیمت:</span>
                <div>
                    @if ($hasColorVariants)
                        <span class="text-xs text-slate-400 font-medium">از</span>
                        <span class="text-slate-900 font-black text-base">{{ number_format($lowestPrice) }}</span>
                    @elseif ($product->base_price)
                        <span class="text-slate-900 font-black text-base">{{ number_format($product->base_price) }}</span>
                    @else
                        <span class="text-slate-400 text-xs">استعلامی</span>
                    @endif
                    <span class="text-[11px] text-slate-500 font-normal">تومان</span>
                </div>
            </div>
        </div>
    </div>

    <div class="px-2 pt-2">
        <a href="{{ $product->storefrontUrl() }}"
           wire:navigate
           class="inline-flex items-center justify-between w-full rounded-2xl bg-slate-100 group-hover:bg-[#ffde5b] text-slate-800 group-hover:text-[#010619] px-4 py-3 text-xs font-bold transition-all duration-300 shadow-xs group-hover:shadow-md">
            <span>مشاهده و سفارش</span>
            <x-icons.arrow-left class="w-4 h-4 transition-transform group-hover:-translate-x-1" />
        </a>
    </div>
</article>

{{-- Compact Mode Card --}}
<article x-show="viewMode === 'compact'" class="group relative flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-slate-200/90 bg-white p-3.5 shadow-sm hover:shadow-xl hover:border-slate-300 transition-all duration-300 col-span-full">
    <div class="flex items-center gap-4 w-full sm:w-auto">
        <a href="{{ $product->storefrontUrl() }}" wire:navigate class="block relative h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-50">
            @if ($product->main_image)
                <img src="{{ asset('storage/' . $product->main_image) }}"
                     alt="{{ $product->name }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
            @else
                <div class="flex h-full w-full items-center justify-center bg-slate-100">
                    <x-icons.photo-placeholder class="h-6 w-6 text-slate-400" />
                </div>
            @endif
        </a>

        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5 text-[10px] font-bold">
                    {{ $product->type?->faLabel() ?? $product->type }}
                </span>
                @if ($product->category)
                    <span class="text-xs text-slate-400">{{ $product->category->name }}</span>
                @endif
            </div>

            <h3 class="font-bold text-slate-900 text-sm sm:text-base group-hover:text-[#010619] transition-colors">
                <a href="{{ $product->storefrontUrl() }}" wire:navigate>
                    {{ $product->name }}
                </a>
            </h3>

            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>موجود در انبار</span>
            </span>
        </div>
    </div>

    <div class="flex items-center justify-between sm:justify-end gap-5 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100">
        <div class="text-start sm:text-end">
            <div class="text-xs text-slate-400">قیمت:</div>
            <div class="font-black text-slate-900 text-base">
                {{ number_format($lowestPrice) }} <span class="text-[11px] font-normal text-slate-500">تومان</span>
            </div>
        </div>

        <a href="{{ $product->storefrontUrl() }}"
           wire:navigate
           class="inline-flex items-center gap-2 rounded-xl bg-slate-100 group-hover:bg-[#ffde5b] text-slate-800 group-hover:text-[#010619] px-4 py-2.5 text-xs font-bold transition-all duration-300 shadow-xs">
            <span>مشاهده</span>
            <x-icons.arrow-left class="w-3.5 h-3.5" />
        </a>
    </div>
</article>
