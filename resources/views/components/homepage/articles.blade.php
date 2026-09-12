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
                <a href="{{ route('articles.index') }}"
                   class="hidden sm:inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 transition">
                    همه مقالات
                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8l-4 4m0 0l4 4m-4-4h17" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($articles as $article)
                    <article class="bg-white border border-gray-100 rounded-2xl overflow-hidden hover:shadow-lg transition-all duration-300">
                        <a href="{{ route('articles.show', $article) }}" class="block">
                            @if ($article->cover_image)
                                <div class="aspect-[16/9] overflow-hidden bg-gray-50">
                                    <img src="{{ asset('storage/' . $article->cover_image) }}"
                                         alt="{{ $article->title }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                                </div>
                            @else
                                <div class="aspect-[16/9] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                    </svg>
                                </div>
                            @endif
                        </a>

                        <div class="p-5">
                            @if ($article->category)
                                <span class="inline-block px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium mb-2">
                                    {{ $article->category->name }}
                                </span>
                            @endif

                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                <a href="{{ route('articles.show', $article) }}" class="hover:text-primary-600 transition">
                                    {{ $article->title }}
                                </a>
                            </h3>

                            @if ($article->excerpt)
                                <p class="text-sm text-gray-600 leading-relaxed line-clamp-2 mb-3">
                                    {{ $article->excerpt }}
                                </p>
                            @endif

                            <time class="text-xs text-gray-500">
                                {{ $article->published_at?->format('Y/m/d') ?? $article->created_at->format('Y/m/d') }}
                            </time>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif