@php
    $settings = is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 8;
    $products = $products->take($limit);
@endphp

@if ($products->isNotEmpty())
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <header class="text-center mb-10">
                @if ($section->title)
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                        {{ $section->title }}
                    </h2>
                @endif
                @if ($section->content)
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        {{ $section->content }}
                    </p>
                @endif
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($products as $product)
                    <article class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-slate-300 transition-all duration-300 group flex flex-col justify-between hover:-translate-y-1">
                        <div>
                            @if ($product->main_image)
                                <a href="{{ $product->storefrontUrl() }}" wire:navigate
                                   class="block aspect-[4/3] overflow-hidden rounded-xl bg-slate-50 relative">
                                    <img src="{{ asset('storage/' . $product->main_image) }}"
                                         alt="{{ $product->name }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                                </a>
                            @else
                                <a href="{{ $product->storefrontUrl() }}" wire:navigate
                                   class="block aspect-[4/3] rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                    <x-icons.photo-placeholder class="text-slate-400" />
                                </a>
                            @endif

                            <div class="pt-3.5 px-1 pb-1">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">
                                        {{ $product->type?->faLabel() ?? $product->type }}
                                    </span>
                                </div>

                                <h3 class="font-bold text-slate-900 text-sm sm:text-base mb-1.5 line-clamp-1 group-hover:text-[#010619] transition-colors">
                                    <a href="{{ $product->storefrontUrl() }}" wire:navigate>
                                        {{ $product->name }}
                                    </a>
                                </h3>

                                @if ($product->colorPrices && $product->colorPrices->isNotEmpty())
                                    <div class="text-slate-900 font-extrabold text-base mb-2">
                                        از {{ number_format($product->colorPrices->min('price')) }} تومان
                                    </div>
                                @elseif ($product->base_price)
                                    <div class="text-slate-900 font-extrabold text-base mb-2">
                                        {{ number_format($product->base_price) }} تومان
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="px-1 pt-1">
                            <a href="{{ $product->storefrontUrl() }}" wire:navigate
                               class="inline-flex items-center justify-between w-full py-2.5 px-3.5 rounded-xl bg-slate-100 hover:bg-[#ffde5b] text-slate-800 hover:text-[#010619] text-xs font-bold transition-all duration-200">
                                <span>مشاهده جزئیات</span>
                                <x-icons.arrow-left class="w-4 h-4" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif