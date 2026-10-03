@php
    $settings = isset($section) && is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 8;
    $faqs = $faqs->take($limit);
@endphp

@if ($faqs->isNotEmpty())
    <section class="py-16 sm:py-20 bg-slate-50/70 border-b border-slate-100" aria-label="سوالات متداول">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 sm:mb-12">
                <header class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/40 mb-3">
                        <x-icons.sparkles class="w-3.5 h-3.5 text-[#010619]" />
                        <span>پاسخ به سوالات متداول</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ isset($section) && $section->title ? $section->title : 'سوالات متداول' }}
                    </h2>
                    @if (isset($section) && $section->content)
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ $section->content }}
                        </p>
                    @else
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            پاسخ به رایج‌ترین پرسش‌های کاربران درباره جنس کارت‌ها، نحوه شخصی‌سازی و شرایط ارسال
                        </p>
                    @endif
                </header>

                <div class="shrink-0">
                    <a href="{{ route('faq.index') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-800 hover:text-[#010619] bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                        <span>مشاهده همه سوالات</span>
                        <x-icons.arrow-left class="w-4 h-4" />
                    </a>
                </div>
            </div>

            <div class="space-y-3.5">
                @foreach ($faqs as $faq)
                    @include('components.faq-item', ['faq' => $faq])
                @endforeach
            </div>
        </div>
    </section>
@endif