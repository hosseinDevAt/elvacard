@php
    $settings = is_array($section->settings) ? $section->settings : [];
@endphp

<section class="relative min-h-[40vh] sm:min-h-[60vh] lg:min-h-[70vh] flex items-center justify-center overflow-hidden"
         style="background-color: {{ $settings['background_color'] ?? '#1a1a2e' }};">
    <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/20 to-transparent"></div>

    @if ($settings['background_image'] ?? false)
        <img src="{{ asset('storage/' . $settings['background_image']) }}"
             alt=""
             class="absolute inset-0 w-full h-full object-cover opacity-30">
    @endif

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20 text-center">
        @if ($section->title)
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                {!! $section->title !!}
            </h1>
        @endif

        @if ($section->content)
            <p class="text-xl sm:text-2xl text-white/90 mb-10 max-w-3xl mx-auto leading-relaxed">
                {!! $section->content !!}
            </p>
        @endif

        @if (isset($settings['cta_text']) && isset($settings['cta_url']))
            <a href="{{ safe_url($settings['cta_url']) ?? '#' }}"
               class="inline-flex items-center px-8 py-4 text-lg font-semibold text-white bg-primary-600 rounded-full hover:bg-primary-700 transition shadow-lg">
                {{ $settings['cta_text'] }}
                @if ($settings['cta_icon'] ?? false)
                    <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8l-4 4m0 0l4 4m-4-4h17" />
                    </svg>
                @endif
            </a>
        @endif
    </div>
</section>