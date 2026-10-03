@php
    $settings = isset($section) && is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 3;
    $articles = $articles->take($limit);
@endphp

@if ($articles->isNotEmpty())
    <section class="py-16 sm:py-20 bg-white border-b border-slate-100" aria-label="مقالات">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 sm:mb-12">
                <header class="max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/40 mb-3">
                        <x-icons.newspaper class="w-3.5 h-3.5 text-[#010619]" />
                        <span>دانستنی‌ها و اخبار</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ isset($section) && $section->title ? $section->title : 'آخرین مقالات' }}
                    </h2>
                    @if (isset($section) && $section->content)
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ $section->content }}
                        </p>
                    @else
                        <p class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            راهنماها و نکات کاربردی درباره کارت‌های هوشمند شخصی، استانداردهای ساخت و نگهداری
                        </p>
                    @endif
                </header>

                <div class="shrink-0">
                    <a href="{{ route('articles.index') }}" wire:navigate
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-800 hover:text-[#010619] bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                        <span>همه مقالات</span>
                        <x-icons.arrow-left class="w-4 h-4" />
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7">
                @foreach ($articles as $article)
                    <article class="bg-white border border-slate-200/90 rounded-2xl p-3.5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/80 hover:border-slate-300 transition-all duration-300 group flex flex-col justify-between hover:-translate-y-1.5">
                        <div>
                            <a href="{{ route('articles.show', $article) }}" wire:navigate class="block">
                                @if ($article->cover_image)
                                    <div class="aspect-[16/9] overflow-hidden rounded-xl bg-slate-50 relative">
                                        @if ($article->category)
                                            <span class="absolute top-2.5 start-2.5 z-10 px-2.5 py-0.5 rounded-md bg-white/95 backdrop-blur-sm text-slate-800 text-[11px] font-bold shadow-sm">
                                                {{ $article->category->name }}
                                            </span>
                                        @endif
                                        <img src="{{ asset('storage/' . $article->cover_image) }}"
                                             alt="{{ $article->title }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                                    </div>
                                @else
                                    <div class="aspect-[16/9] rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center relative">
                                        @if ($article->category)
                                            <span class="absolute top-2.5 start-2.5 z-10 px-2.5 py-0.5 rounded-md bg-white/95 backdrop-blur-sm text-slate-800 text-[11px] font-bold shadow-sm">
                                                {{ $article->category->name }}
                                            </span>
                                        @endif
                                        <x-icons.document class="text-slate-400" />
                                    </div>
                                @endif
                            </a>

                            <div class="pt-3.5 px-1 pb-1">
                                <h3 class="font-extrabold text-slate-900 text-sm sm:text-base mb-2 line-clamp-2 group-hover:text-[#010619] transition-colors leading-snug">
                                    <a href="{{ route('articles.show', $article) }}" wire:navigate>
                                        {{ $article->title }}
                                    </a>
                                </h3>

                                @if ($article->excerpt)
                                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed line-clamp-2 mb-3">
                                        {{ $article->excerpt }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="px-1 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 mt-2">
                            <span>{{ jalali_date($article->published_at ?? $article->created_at, 'date') }}</span>
                            <span class="font-bold text-slate-700 group-hover:text-[#010619] inline-flex items-center gap-1 transition-colors">
                                <span>مطالعه مقاله</span>
                                <x-icons.arrow-left class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform" />
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif