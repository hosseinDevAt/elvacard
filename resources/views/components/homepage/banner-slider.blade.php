@php
    $banners = collect([
        site_setting('homepage_banner_1', \App\Livewire\Admin\AppearanceManager::defaultBanner(1)),
        site_setting('homepage_banner_2', \App\Livewire\Admin\AppearanceManager::defaultBanner(2)),
        site_setting('homepage_banner_3', \App\Livewire\Admin\AppearanceManager::defaultBanner(3)),
    ])->filter(fn ($banner) => is_array($banner) && (! empty($banner['title']) || ! empty($banner['image_path']) || ! empty($banner['mobile_image_path'])))->values();

    $gradients = [
        'from-primary-900 via-primary-800 to-primary-600',
        'from-gray-900 via-gray-800 to-gray-700',
        'from-indigo-950 via-indigo-900 to-indigo-700',
    ];

    $hexRegex = '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/';
@endphp

@if ($banners->isNotEmpty())
    <section class="relative" x-data="bannerSlider(@js($banners->count()))" x-init="init()" aria-label="بنرهای تبلیغاتی">
        <div class="relative overflow-hidden bg-gray-900">
            <div class="flex transition-transform duration-500 ease-out" :style="{ transform: 'translateX(' + (slide * 100) + '%)' }">
                @foreach ($banners as $index => $banner)
                    @php
                        $hasDesktopImage = ! empty($banner['image_path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($banner['image_path']);
                        $hasMobileImage = ! empty($banner['mobile_image_path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($banner['mobile_image_path']);
                        $hasAnyImage = $hasDesktopImage || $hasMobileImage;

                        $hasTitle = filled($banner['title'] ?? null);
                        $hasSubtitle = filled($banner['subtitle'] ?? null);
                        $hasCta = filled($banner['cta_text'] ?? null) && filled($banner['cta_url'] ?? null);
                        $hasTextContent = $hasTitle || $hasSubtitle || $hasCta;

                        $textColor = (filled($banner['text_color'] ?? null) && preg_match($hexRegex, $banner['text_color'])) ? $banner['text_color'] : '#ffffff';
                        $buttonBgColor = (filled($banner['button_bg_color'] ?? null) && preg_match($hexRegex, $banner['button_bg_color'])) ? $banner['button_bg_color'] : '#ffde5b';
                        $buttonTextColor = (filled($banner['button_text_color'] ?? null) && preg_match($hexRegex, $banner['button_text_color'])) ? $banner['button_text_color'] : '#010619';
                    @endphp
                    <div class="w-full shrink-0" @mouseenter="pause()" @mouseleave="play()">
                        <div class="relative flex min-h-[45vh] sm:min-h-[55vh] lg:min-h-[65vh] items-center justify-center overflow-hidden {{ $gradients[$index % count($gradients)] }}">
                            @if ($hasTextContent)
                                <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>
                            @endif

                            @if ($hasAnyImage)
                                <picture class="absolute inset-0 h-full w-full">
                                    @if ($hasMobileImage && $hasDesktopImage)
                                        <source media="(max-width: 767px)" srcset="{{ asset('storage/' . $banner['mobile_image_path']) }}">
                                    @endif
                                    @php
                                        $fallbackSrc = $hasDesktopImage ? asset('storage/' . $banner['image_path']) : asset('storage/' . $banner['mobile_image_path']);
                                    @endphp
                                    <img src="{{ $fallbackSrc }}"
                                         alt="{{ $banner['title'] ?? '' }}"
                                         class="h-full w-full object-cover">
                                </picture>
                            @endif

                            @if ($hasTextContent)
                                <div class="relative z-10 mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-20 lg:px-8">
                                    @if ($hasTitle)
                                        <h2 class="mb-4 text-3xl font-bold sm:text-5xl lg:text-6xl leading-tight"
                                            style="color: {{ $textColor }};">
                                            {{ $banner['title'] }}
                                        </h2>
                                    @endif

                                    @if ($hasSubtitle)
                                        <p class="mx-auto mb-8 max-w-3xl text-lg leading-relaxed sm:text-xl"
                                           style="color: {{ $textColor }}; opacity: 0.9;">
                                            {{ $banner['subtitle'] }}
                                        </p>
                                    @endif

                                    @if ($hasCta)
                                        <a href="{{ safe_url($banner['cta_url']) ?? route('catalog.products.index') }}"
                                           style="background-color: {{ $buttonBgColor }}; color: {{ $buttonTextColor }};"
                                           class="inline-flex items-center gap-2 rounded-xl px-7 py-3 text-base sm:text-lg font-extrabold shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                                            <span>{{ $banner['cta_text'] }}</span>
                                            <x-icons.arrow-left class="h-5 w-5" style="color: {{ $buttonTextColor }};" />
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <button type="button" @click="prev()" :aria-label="'بنر قبلی'"
                        class="absolute start-4 top-1/2 z-20 -translate-y-1/2 rounded-2xl bg-black/30 p-2.5 text-white backdrop-blur-md border border-white/10 transition hover:bg-black/50 hover:scale-105 active:scale-95">
                    <x-icons.chevron-right />
                </button>
                <button type="button" @click="next()" :aria-label="'بنر بعدی'"
                        class="absolute end-4 top-1/2 z-20 -translate-y-1/2 rounded-2xl bg-black/30 p-2.5 text-white backdrop-blur-md border border-white/10 transition hover:bg-black/50 hover:scale-105 active:scale-95">
                    <x-icons.chevron-left />
                </button>
                <div class="absolute bottom-5 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                    <template x-for="i in count" :key="i">
                        <button type="button"
                                @click="go(i - 1)"
                                :aria-label="'برو به بنر ' + i"
                                class="h-2.5 rounded-full transition-all duration-300"
                                :class="slide === i - 1 ? 'w-8 bg-[#ffde5b]' : 'w-2.5 bg-white/40 hover:bg-white/70'">
                        </button>
                    </template>
                </div>
            @endif
        </div>
    </section>
@endif