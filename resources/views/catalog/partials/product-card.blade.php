@php
    $lowestPrice = $product->colorPrices?->first()?->price ?? $product->base_price;
@endphp
<article class="group relative flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-slate-300 transition-all duration-300 hover:-translate-y-1">
    <div>
        <a href="{{ $product->storefrontUrl() }}" wire:navigate class="block relative aspect-[4/3] overflow-hidden rounded-xl bg-slate-50">
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
            <span class="absolute start-2.5 top-2.5 rounded-full bg-[#010619]/80 text-[#ffde5b] px-2.5 py-0.5 text-[11px] font-bold backdrop-blur-md">
                {{ $product->type?->faLabel() ?? $product->type }}
            </span>
        </a>

        <div class="pt-3.5 px-1 pb-1">
            <h3 class="mb-1 font-bold text-slate-900 text-sm sm:text-base line-clamp-1 group-hover:text-[#010619] transition-colors">
                <a href="{{ $product->storefrontUrl() }}" wire:navigate>
                    {{ $product->name }}
                </a>
            </h3>

            @if ($product->category)
                <p class="mb-1.5 text-xs text-slate-500 font-medium">{{ $product->category->name }}</p>
            @endif

            @if ($product->colorPrices && $product->colorPrices->isNotEmpty())
                <p class="text-slate-900 font-extrabold text-base mb-1">
                    از {{ number_format($lowestPrice) }} تومان
                </p>
            @elseif ($product->base_price)
                <p class="text-slate-900 font-extrabold text-base mb-1">
                    {{ number_format($product->base_price) }} تومان
                </p>
            @else
                <p class="text-slate-400 text-xs mb-1">تعیین قیمت در استعلام</p>
            @endif
        </div>
    </div>

    <div class="px-1 pt-1">
        <a href="{{ $product->storefrontUrl() }}"
           wire:navigate
           class="inline-flex items-center justify-between w-full rounded-xl bg-slate-100 hover:bg-[#ffde5b] text-slate-800 hover:text-[#010619] px-4 py-2.5 text-xs font-bold transition-all duration-200 shadow-sm">
            <span>مشاهده و سفارش</span>
            <x-icons.arrow-left class="w-4 h-4" />
        </a>
    </div>
</article>
