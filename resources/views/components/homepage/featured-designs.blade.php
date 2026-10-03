@php
    $settings = isset($section) && is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 8;
    $designs = $designs->take($limit);
@endphp

@if ($designs->isNotEmpty())
    <section class="py-16 sm:py-20 bg-white border-b border-slate-100" aria-label="طرح‌های محبوب">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 sm:mb-12">
                <header class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/40 mb-3">
                        <x-icons.palette class="w-3.5 h-3.5 text-[#010619]" />
                        <span>گالری طرح‌های اختصاصی</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ isset($section) && $section->title ? $section->title : 'طرح‌های محبوب' }}
                    </h2>
                    @if (isset($section) && $section->content)
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ $section->content }}
                        </p>
                    @else
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            مجموعه‌ای از برترین طرح‌های مدرن و کلاسیک برای انواع کارت‌های شخصی، بانکی و سوخت
                        </p>
                    @endif
                </header>

                <div class="shrink-0">
                    <a href="{{ route('catalog.designs.index') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-[#010619] bg-[#ffde5b] hover:bg-[#f5d347] shadow-sm shadow-[#ffde5b]/30 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                        <span>مشاهده همه طرح‌ها</span>
                        <x-icons.arrow-left class="w-4 h-4" />
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-7">
                @foreach ($designs as $design)
                    @php
                        $firstImage = $design->images->first();
                        $imagePath = $firstImage?->image_path;
                        $designUrl = route('catalog.designs.index', ['category' => $design->category?->slug]) . '#design-' . $design->id;
                    @endphp

                    <article class="bg-white border border-slate-200/90 rounded-2xl p-3.5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/80 hover:border-slate-300 transition-all duration-300 group flex flex-col justify-between hover:-translate-y-1.5">
                        <div>
                            @if ($imagePath)
                                <a href="{{ $designUrl }}" wire:navigate
                                   class="block aspect-[16/10] overflow-hidden rounded-xl bg-slate-950 p-2 sm:p-3 relative group-hover:bg-slate-900 transition-colors">
                                    @if ($design->category)
                                        <span class="absolute top-2.5 start-2.5 z-10 px-2.5 py-0.5 text-[11px] font-bold text-slate-800 bg-white/95 backdrop-blur-sm rounded-md shadow-sm">
                                            {{ $design->category->name }}
                                        </span>
                                    @endif
                                    <img src="{{ asset('storage/' . $imagePath) }}"
                                         alt="{{ $design->name }}"
                                         loading="lazy"
                                         class="w-full h-full object-contain drop-shadow-md group-hover:scale-105 transition-transform duration-500 ease-out">
                                </a>
                            @else
                                <div class="aspect-[16/10] rounded-xl bg-slate-900 flex items-center justify-center p-3 relative">
                                    @if ($design->category)
                                        <span class="absolute top-2.5 start-2.5 z-10 px-2.5 py-0.5 text-[11px] font-bold text-slate-800 bg-white/95 backdrop-blur-sm rounded-md shadow-sm">
                                            {{ $design->category->name }}
                                        </span>
                                    @endif
                                    <x-icons.image-placeholder class="text-slate-600" />
                                </div>
                            @endif

                            <div class="pt-3.5 px-1 pb-1">
                                <h3 class="font-extrabold text-slate-900 text-sm sm:text-base mb-1 line-clamp-1 group-hover:text-[#010619] transition-colors">
                                    <a href="{{ $designUrl }}" wire:navigate>
                                        {{ $design->name }}
                                    </a>
                                </h3>
                                <p class="text-xs text-slate-500 line-clamp-1">
                                    کارت شخصی با طرح {{ $design->name }}
                                </p>
                            </div>
                        </div>

                        <div class="px-1 pt-2">
                            <a href="{{ $designUrl }}" wire:navigate
                               class="inline-flex items-center justify-between w-full py-2.5 px-3.5 rounded-xl bg-slate-100 group-hover:bg-[#ffde5b] text-slate-800 group-hover:text-[#010619] text-xs font-bold transition-all duration-200">
                                <span>انتخاب و مشاهده طرح</span>
                                <x-icons.arrow-left class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif