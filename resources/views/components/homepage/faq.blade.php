@php
    $settings = is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? null;
    $faqs = $faqs->when($limit, fn ($q) => $q->take($limit));
@endphp

@if ($faqs->isNotEmpty())
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
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

            <div class="space-y-4">
                @foreach ($faqs as $faq)
                    @include('components.faq-item', ['faq' => $faq])
                @endforeach
            </div>

            @if ($faqs->count() < ($limit ?? PHP_INT_MAX))
                {{-- Some FAQs were filtered out --}}
            @endif
        </div>
    </section>
@endif