@php
    $settings = is_array($section->settings) ? $section->settings : [];
    $limit = $settings['limit'] ?? 3;
    $articles = $articles->take($limit);
@endphp

@if ($articles->isNotEmpty())
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-10">
                <header>
                    @if ($section->title)
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                            {{ $section->title }}
                        </h2>
                    @endif
                    @if ($section->content)
                        <p class="text-gray-600">
                            {{ $section->content }}
                        </p>
                    @endif
                </header>
                <a href="{{ route('articles.index') }}" wire:navigate
                   class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 hover:text-[#010619] bg-slate-100 hover:bg-[#ffde5b] transition-all duration-200">
                    <span>همه مقالات</span>
                    <x-icons.arrow-left class="w-4 h-4" />
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($articles as $article)
                    <article class="bg-white border border-slate-200/80 rounded-2xl p-3 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 hover:border-slate-300 transition-all duration-300 group flex flex-col justify-between hover:-translate-y-1">
                        <div>
                            <a href="{{ route('articles.show', $article) }}" wire:navigate class="block">
                                @if ($article->cover_image)
                                    <div class="aspect-[16/9] overflow-hidden rounded-xl bg-slate-50 relative">
                                        <img src="{{ asset('storage/' . $article->cover_image) }}"
                                             alt="{{ $article->title }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                                    </div>
                                @else
                                    <div class="aspect-[16/9] rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                                        <x-icons.document class="text-slate-400" />
                                    </div>
                                @endif
                            </a>

                            <div class="pt-3.5 px-1 pb-1">
                                @if ($article->category)
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold mb-2">
                                        {{ $article->category->name }}
                                    </span>
                                @endif

                                <h3 class="font-bold text-slate-900 text-sm sm:text-base mb-2 line-clamp-2 group-hover:text-[#010619] transition-colors">
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

                        <div class="px-1 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>{{ jalali_date($article->published_at ?? $article->created_at, 'date') }}</span>
                            <span class="font-bold text-slate-700 group-hover:text-[#010619] inline-flex items-center gap-1 transition-colors">
                                <span>مطالعه</span>
                                <x-icons.arrow-left class="w-3.5 h-3.5" />
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif