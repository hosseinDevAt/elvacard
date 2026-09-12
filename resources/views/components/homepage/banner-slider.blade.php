@php
    $banners = collect([
        site_setting('homepage_banner_1', []),
        site_setting('homepage_banner_2', []),
        site_setting('homepage_banner_3', []),
    ])->filter(fn ($banner) => is_array($banner) && (! empty($banner['title']) || ! empty($banner['image_path'])));

    $siteName = site_setting('site_name', config('app.name'));
    $gradients = [
        'from-primary-900 via-primary-800 to-primary-600',
        'from-gray-900 via-gray-800 to-gray-700',
        'from-indigo-950 via-indigo-900 to-indigo-700',
    ];
@endphp

@if ($banners->isNotEmpty())
    <section class="relative" x-data="bannerSlider(@js($banners->count()))" x-init="init()" aria-label="بنرهای تبلیغاتی">
        <div class="relative overflow-hidden bg-gray-900">
            <div class="flex transition-transform duration-500 ease-out" :style="'transform: translateX(-' + (slide * 100) + '%)'">
                @foreach ($banners as $index => $banner)
                    <div class="w-full shrink-0" @mouseenter="pause()" @mouseleave="play()">
                        <div class="relative flex min-h-[45vh] sm:min-h-[55vh] lg:min-h-[65vh] items-center justify-center overflow-hidden {{ $gradients[$index % count($gradients)] }}">
                            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>

                            @if (($banner['image_path'] ?? null) && file_exists(public_path('storage/' . $banner['image_path'])))
                                <img src="{{ asset('storage/' . $banner['image_path']) }}"
                                     alt="{{ $banner['title'] ?? '' }}"
                                     class="absolute inset-0 h-full w-full object-cover">
                            @endif

                            <div class="relative z-10 mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-20 lg:px-8">
                                @if ($banner['title'] ?? false)
                                    <h2 class="mb-4 text-3xl font-bold text-white sm:text-5xl lg:text-6xl leading-tight">
                                        {{ $banner['title'] }}
                                    </h2>
                                @else
                                    <h2 class="mb-4 text-3xl font-bold text-white sm:text-5xl lg:text-6xl leading-tight">
                                        {{ $siteName }}
                                    </h2>
                                @endif

                                @if ($banner['subtitle'] ?? false)
                                    <p class="mx-auto mb-8 max-w-3xl text-lg leading-relaxed text-white/90 sm:text-xl">
                                        {{ $banner['subtitle'] }}
                                    </p>
                                @endif

                                @if (($banner['cta_text'] ?? false) && ($banner['cta_url'] ?? false))
                                    <a href="{{ safe_url($banner['cta_url']) ?? route('catalog.products.index') }}"
                                       class="inline-flex items-center rounded-full bg-white px-6 py-3 text-lg font-semibold text-gray-900 shadow-lg transition hover:bg-gray-100 sm:px-8 sm:py-4">
                                        {{ $banner['cta_text'] }}
                                        <x-icons.arrow-left class="me-0 ms-2 h-5 w-5" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <button type="button" @click="prev()" :aria-label="'بنر قبلی'"
                        class="absolute start-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/15 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                    <x-icons.chevron-left />
                </button>
                <button type="button" @click="next()" :aria-label="'بنر بعدی'"
                        class="absolute end-3 top-1/2 z-20 -translate-y-1/2 rounded-full bg-white/15 p-2 text-white backdrop-blur-sm transition hover:bg-white/30">
                    <x-icons.chevron-right />
                </button>
                <div class="absolute bottom-5 left-1/2 z-20 flex -translate-x-1/2 gap-2">
                    <template x-for="i in count" :key="i">
                        <button type="button"
                                @click="go(i - 1)"
                                :aria-label="'برو به بنر ' + i"
                                class="h-2.5 rounded-full transition-all duration-300"
                                :class="slide === i - 1 ? 'w-8 bg-white' : 'w-2.5 bg-white/40 hover:bg-white/70'">
                        </button>
                    </template>
                </div>
            @endif
        </div>
    </section>
@endif