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
                            {{ $section->title }}
                        </h2>
                    @endif
                    @if ($section->content)
                        <p class="text-gray-600 max-w-2xl mx-auto">
                            {{ $section->content }}
                        </p>
                    @endif
                </header>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($designs as $design)
                    @php
                        $firstImage = $design->images->first();
                        $imagePath = $firstImage?->image_path;
                        $designUrl = route('catalog.designs.index', ['category' => $design->category?->slug]) . '#design-' . $design->id;
                    @endphp

                    <article class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-slate-300 transition-all duration-300 group flex flex-col justify-between hover:-translate-y-1">
                        <div>
                            @if ($imagePath)
                                <a href="{{ $designUrl }}" wire:navigate
                                   class="block aspect-[4/3] overflow-hidden rounded-xl bg-slate-50 relative">
                                    <img src="{{ asset('storage/' . $imagePath) }}"
                                         alt="{{ $design->name }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                                </a>
                            @else
                                <div class="aspect-[4/3] rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                    <x-icons.image-placeholder class="text-slate-400" />
                                </div>
                            @endif

                            <div class="pt-3.5 px-1 pb-1">
                                @if ($design->category)
                                    <span class="inline-block px-2.5 py-0.5 text-xs font-semibold text-slate-700 bg-slate-100 rounded-full mb-1.5">
                                        {{ $design->category->name }}
                                    </span>
                                @endif

                                <h3 class="font-bold text-slate-900 text-sm sm:text-base mb-1.5 line-clamp-1 group-hover:text-[#010619] transition-colors">
                                    <a href="{{ $designUrl }}" wire:navigate>
                                        {{ $design->name }}
                                    </a>
                                </h3>
                            </div>
                        </div>

                        <div class="px-1 pt-1">
                            <a href="{{ $designUrl }}" wire:navigate
                               class="inline-flex items-center justify-between w-full py-2.5 px-3.5 rounded-xl bg-slate-100 hover:bg-[#ffde5b] text-slate-800 hover:text-[#010619] text-xs font-bold transition-all duration-200">
                                <span>مشاهده طرح</span>
                                <x-icons.arrow-left class="w-4 h-4" />
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