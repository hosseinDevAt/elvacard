@php
    $lowestPrice = $product->colorPrices?->first()?->price ?? $product->base_price;
@endphp
<article class="group flex flex-col rounded-2xl border border-gray-200 bg-white overflow-hidden transition hover:border-primary-200 hover:shadow-lg">
    <a href="{{ $product->storefrontUrl() }}" wire:navigate class="block relative aspect-[4/3] overflow-hidden bg-gray-50">
        @if ($product->main_image)
            <img src="{{ asset('storage/' . $product->main_image) }}"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-100 to-primary-200">
                <x-icons.photo-placeholder class="text-primary-300" />
            </div>
        @endif
        <span class="absolute start-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium text-gray-700 backdrop-blur-sm">
            {{ $product->type?->faLabel() ?? $product->type }}
        </span>
    </a>

    <div class="flex flex-1 flex-col p-5">
        <h3 class="mb-1 font-semibold text-gray-900 line-clamp-1">
            <a href="{{ $product->storefrontUrl() }}" wire:navigate class="hover:text-primary-600 transition">
                {{ $product->name }}
            </a>
        </h3>

        @if ($product->category)
            <p class="mb-1 text-xs text-gray-500">{{ $product->category->name }}</p>
        @endif

        @if ($product->colorPrices && $product->colorPrices->isNotEmpty())
            <p class="mb-4 text-primary-600 font-bold">
                از {{ number_format($lowestPrice) }} تومان
            </p>
        @elseif ($product->base_price)
            <p class="mb-4 text-primary-600 font-bold">
                {{ number_format($product->base_price) }} تومان
            </p>
        @else
            <p class="mb-4 text-gray-400 text-sm">تعیین قیمت در استعلام</p>
@endif

        <a href="{{ $product->storefrontUrl() }}"
           wire:navigate
           class="mt-auto inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition duration-200 hover:bg-[#ffde5b] hover:text-[#010619] shadow-sm">
            مشاهده و سفارش
        </a>
    </div>
</article>
