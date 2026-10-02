@php
    $settings = is_array($section->settings) ? $section->settings : [];
@endphp

<section class="py-12 sm:py-16"
         style="background-color: {{ $settings['background_color'] ?? '#ffffff' }};">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        @if ($section->title || $section->content)
            <div class="text-center mb-8">
                @if ($section->title)
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                        {{ $section->title }}
                    </h2>
                @endif
            </div>
        @endif

        @if ($section->content)
            <div class="prose prose-persian max-w-none text-gray-700 leading-relaxed">
                {{ $section->content }}
            </div>
        @endif

        @if (isset($settings['cta_text']) && isset($settings['cta_url']))
            <div class="text-center mt-8">
                <a href="{{ safe_url($settings['cta_url']) ?? '#' }}"
                   class="inline-flex items-center gap-2 px-8 py-3.5 text-base font-bold text-[#010619] bg-[#ffde5b] rounded-xl hover:bg-[#f5d347] hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 shadow-lg shadow-[#ffde5b]/25">
                    {{ $settings['cta_text'] }}
                </a>
            </div>
        @endif
    </div>
</section>