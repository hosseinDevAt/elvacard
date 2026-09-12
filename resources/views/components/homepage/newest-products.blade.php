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
                    <article class="bg-white border border-gray-100 rounded-2xl overflow-hidden hover:shadow-lg hover:border-primary-200 transition-all duration-300 group">
                        @if ($product->main_image)
                            <a href="{{ route('catalog.products.show', $product) }}"
                               class="block aspect-[4/3] overflow-hidden bg-gray-50">
                                <img src="{{ asset('storage/' . $product->main_image) }}"
                                     alt="{{ $product->name }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </a>
                        @else
                            <a href="{{ route('catalog.products.show', $product) }}"
                               class="block aspect-[4/3] bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center">
<x-icons.photo-placeholder class="text-primary-300" />
                            </a>
                        @endif

                        <div class="p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-block px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium">
                                    {{ $product->type?->faLabel() ?? $product->type }}
                                </span>
                            </div>

                            <h3 class="font-semibold text-gray-900 mb-1 line-clamp-1">
                                <a href="{{ route('catalog.products.show', $product) }}" class="hover:text-primary-600 transition">
                                    {{ $product->name }}
                                </a>
                            </h3>

                            @if ($product->colorPrices && $product->colorPrices->isNotEmpty())
                                <div class="text-primary-600 font-bold text-lg mb-2">
                                    از {{ number_format($product->colorPrices->min('price')) }} تومان
                                </div>
                            @elseif ($product->base_price)
                                <div class="text-primary-600 font-bold text-lg mb-2">
                                    {{ number_format($product->base_price) }} تومان
                                </div>
                            @endif

                            <a href="{{ route('catalog.products.show', $product) }}"
                               class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 transition">
                                مشاهده جزئیات
                                <x-icons.arrow-left class="me-1" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif