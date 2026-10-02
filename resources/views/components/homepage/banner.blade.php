@php
    $settings = is_array($section->settings) ? $section->settings : [];
@endphp

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

        <div class="relative overflow-hidden rounded-2xl"
             style="background-color: {{ $settings['background_color'] ?? '#f3f4f6' }};">
            @if ($settings['background_image'] ?? false)
                <img src="{{ asset('storage/' . $settings['background_image']) }}"
                     alt=""
                     class="absolute inset-0 w-full h-full object-cover opacity-10">
            @endif

            <div class="relative py-16 sm:py-24 px-4 text-center">
                @if (isset($settings['cta_text']) && isset($settings['cta_url']))
                    <a href="{{ safe_url($settings['cta_url']) ?? '#' }}"
                       class="inline-flex items-center gap-2 px-8 py-3.5 text-base font-bold text-[#010619] bg-[#ffde5b] rounded-xl hover:bg-[#f5d347] hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 shadow-lg shadow-[#ffde5b]/25">
                        {{ $settings['cta_text'] }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>