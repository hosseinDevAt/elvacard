@php
    $settings = is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 6;
    $designs = $designs->take($limit);
@endphp

@if ($designs->isNotEmpty())
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($designs as $design)
                    @php
                        $firstImage = $design->images->first();
                        $imagePath = $firstImage?->image_path;
                    @endphp

                    <article class="bg-white border border-gray-100 rounded-2xl overflow-hidden hover:shadow-lg hover:border-primary-200 transition-all duration-300 group">
                        @if ($imagePath)
                            <a href="{{ route('catalog.designs.index', ['category' => $design->category?->slug]) . '#design-' . $design->id }}"
                               class="block aspect-[4/3] overflow-hidden bg-gray-50">
                                <img src="{{ asset('storage/' . $imagePath) }}"
                                     alt="{{ $design->name }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </a>
                        @else
                            <div class="aspect-[4/3] bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                                <svg class="w-16 h-16 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                            </div>
                        @endif

                        <div class="p-5">
                            <h3 class="font-semibold text-gray-900 mb-1 line-clamp-1">
                                <a href="{{ route('catalog.designs.index', ['category' => $design->category?->slug]) . '#design-' . $design->id }}"
                                   class="hover:text-primary-600 transition">
                                    {{ $design->name }}
                                </a>
                            </h3>

                            @if ($design->category)
                                <span class="inline-block px-2 py-0.5 text-xs font-medium text-purple-700 bg-purple-100 rounded-full mb-2">
                                    {{ $design->category->name }}
                                </span>
                            @endif

                            <a href="{{ route('catalog.designs.index', ['category' => $design->category?->slug]) . '#design-' . $design->id }}"
                               class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 transition">
                                مشاهده طرح
                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8l-4 4m0 0l4 4m-4-4h17" />
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($designs->count() < $limit)
                {{-- Some designs were filtered out --}}
            @endif
        </div>
    </section>
@endif