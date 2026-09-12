@php
    $settings = is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 6;
    $products = $products->take($limit);
@endphp

@if ($products->isNotEmpty())
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($section->title || $section->content)
                <header class="text-center mb-10">
                    @if ($section->title)
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                            {!! $section->title !!}
                        </h2>
                    @endif
                    @if ($section->content)
                        <p class="text-gray-600 max-w-2xl mx-auto">
                            {!! $section->content !!}
                        </p>
                    @endif
                </header>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($products as $product)
                    <article class="bg-white border border-gray-100 rounded-2xl overflow-hidden hover:shadow-lg hover:border-primary-200 transition-all duration-300 group">
                        @if ($product->main_image)
                            <a href="{{ route('catalog.products.show', $product) }}"
                               class="block aspect-[4/3] overflow-hidden bg-gray-50">
                                <img src="{{ asset('storage/' . $product->main_image) }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </a>
                        @else
                            <div class="aspect-[4/3] bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center">
                                <svg class="w-16 h-16 text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif

                        <div class="p-5">
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
                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8l-4 4m0 0l4 4m-4-4h17" />
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($products->count() < $limit)
                {{-- Some products were filtered out (inactive/invalid) - section still renders with available products --}}
            @endif
        </div>
    </section>
@endif