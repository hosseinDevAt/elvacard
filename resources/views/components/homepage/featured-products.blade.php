@php
    $settings = isset($section) && is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 8;
    $products = $products->take($limit);
@endphp

@if ($products->isNotEmpty())
    <section class="py-16 sm:py-20 bg-slate-50/70 border-b border-slate-100" aria-label="محصولات ویژه">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 sm:mb-12">
                <header class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/40 mb-3">
                        <x-icons.sparkles class="w-3.5 h-3.5 text-[#010619]" />
                        <span>محصولات منتخب</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ isset($section) && $section->title ? $section->title : 'پرفروش‌ترین محصولات' }}
                    </h2>
                    @if (isset($section) && $section->content)
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ $section->content }}
                        </p>
                    @else
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            مجموعه محبوب‌ترین کارت‌های شخصی و اداری با کیفیت ساخت ممتاز
                        </p>
                    @endif
                </header>

                <div class="shrink-0">
                    <a href="{{ route('catalog.products.index') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-800 hover:text-[#010619] bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                        <span>مشاهده همه محصولات</span>
                        <x-icons.arrow-left class="w-4 h-4" />
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-7">
                @foreach ($products as $product)
                    <article class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-sm hover:shadow-2xl hover:shadow-slate-200/80 hover:border-slate-300 transition-all duration-300 group flex flex-col justify-between hover:-translate-y-1.5">
                        <div>
                            @if ($product->main_image)
                                <a href="{{ $product->storefrontUrl() }}" wire:navigate
                                   class="block aspect-[4/3] overflow-hidden rounded-xl bg-slate-50 p-2 sm:p-3 relative group-hover:bg-slate-100/60 transition-colors">
                                    <span class="absolute top-2.5 end-2.5 z-10 px-2.5 py-0.5 rounded-md bg-[#010619]/80 backdrop-blur-sm text-white text-[11px] font-bold">
                                        {{ $product->type?->faLabel() ?? $product->type }}
                                    </span>
                                    <img src="{{ asset('storage/' . $product->main_image) }}"
                                         alt="{{ $product->name }}"
                                         loading="lazy"
                                         class="w-full h-full object-contain drop-shadow-md group-hover:scale-105 transition-transform duration-500 ease-out">
                                </a>
                            @else
                                <a href="{{ $product->storefrontUrl() }}" wire:navigate
                                   class="block aspect-[4/3] rounded-xl bg-slate-100 flex items-center justify-center p-3 relative">
                                    <span class="absolute top-2.5 end-2.5 z-10 px-2.5 py-0.5 rounded-md bg-[#010619]/80 backdrop-blur-sm text-white text-[11px] font-bold">
                                        {{ $product->type?->faLabel() ?? $product->type }}
                                    </span>
                                    <x-icons.photo-placeholder class="text-slate-400" />
                                </a>
                            @endif

                            <div class="pt-3.5 px-1 pb-1">
                                <h3 class="font-extrabold text-slate-900 text-sm sm:text-base mb-2 line-clamp-1 group-hover:text-[#010619] transition-colors">
                                    <a href="{{ $product->storefrontUrl() }}" wire:navigate>
                                        {{ $product->name }}
                                    </a>
                                </h3>

                                <div class="pt-1">
                                    @if ($product->colorPrices && $product->colorPrices->isNotEmpty())
                                        <span class="text-[11px] text-slate-400 block font-medium">شروع قیمت از:</span>
                                        <div class="flex items-baseline gap-1 mt-0.5">
                                            <span class="text-base sm:text-lg font-black text-slate-900 tracking-tight">{{ number_format($product->colorPrices->min('price')) }}</span>
                                            <span class="text-xs font-semibold text-slate-500">تومان</span>
                                        </div>
                                    @elseif ($product->base_price)
                                        <span class="text-[11px] text-slate-400 block font-medium">قیمت محصول:</span>
                                        <div class="flex items-baseline gap-1 mt-0.5">
                                            <span class="text-base sm:text-lg font-black text-slate-900 tracking-tight">{{ number_format($product->base_price) }}</span>
                                            <span class="text-xs font-semibold text-slate-500">تومان</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="px-1 pt-3">
                            <a href="{{ $product->storefrontUrl() }}" wire:navigate
                               class="inline-flex items-center justify-between w-full py-2.5 px-3.5 rounded-xl bg-slate-100 group-hover:bg-[#ffde5b] text-slate-800 group-hover:text-[#010619] text-xs font-bold transition-all duration-200">
                                <span>مشاهده و خرید</span>
                                <x-icons.arrow-left class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif