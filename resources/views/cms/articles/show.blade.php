@extends('layouts.app')

@php
    $siteName = site_setting('site_name', config('app.name'));
    $pageTitle = $article->meta_title ?: $article->title;
    if (!str_contains($pageTitle, $siteName)) {
        $pageTitle .= ' - ' . $siteName;
    }
@endphp
@section('title', $pageTitle)

@section('meta')
    @if ($article->meta_description)
        <meta name="description" content="{{ $article->meta_description }}">
    @endif

    @if ($article->canonical_url)
        <link rel="canonical" href="{{ $article->canonical_url }}">
    @endif

    @if ($article->robots_index === false)
        <meta name="robots" content="noindex, nofollow">
    @endif

    @if ($article->cover_image)
        <meta property="og:image" content="{{ asset('storage/' . $article->cover_image) }}">
    @endif
@endsection

@section('content')
<main class="min-h-[70vh] bg-slate-50/50 py-10 sm:py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-breadcrumbs :items="array_values(array_filter([
            ['label' => 'مقالات و راهنماها', 'url' => route('articles.index')],
            $article->category ? ['label' => $article->category->name, 'url' => route('articles.category', $article->category)] : null,
            ['label' => $article->title]
        ]))" />

        <article class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-10 shadow-sm">
            <header class="mb-8 border-b border-slate-100 pb-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    @if ($article->category)
                        <a href="{{ route('articles.category', $article->category) }}" wire:navigate class="badge-brand hover:bg-[#ffde5b]/30 transition">
                            {{ $article->category->name }}
                        </a>
                    @endif

                    @if ($article->published_at)
                        <time datetime="{{ $article->published_at->toISOString() }}" class="text-xs font-medium text-slate-400">
                            انتشار: {{ jalali_date($article->published_at, 'date') }}
                        </time>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-4xl font-black text-[#010619] leading-tight mb-4">{{ $article->title }}</h1>

                @if ($article->cover_image)
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 mt-6">
                        <img src="{{ asset('storage/' . $article->cover_image) }}" alt="{{ $article->title }}" class="w-full h-auto max-h-[440px] object-cover">
                    </div>
                @endif
            </header>

            @if ($article->excerpt)
                <div class="text-slate-700 text-sm sm:text-base leading-relaxed mb-8 p-5 bg-slate-50/80 rounded-2xl border border-slate-200/60 font-medium">
                    {{ $article->excerpt }}
                </div>
            @endif

            <div class="text-slate-800 leading-relaxed text-sm sm:text-base prose prose-slate prose-persian max-w-none">
                {{ $article->content }}
            </div>

            {{-- Footer / Return link --}}
            <div class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-between">
                @if ($article->category)
                    <a href="{{ route('articles.category', $article->category) }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition">
                        <x-icons.arrow-right class="h-4 w-4" />
                        <span>بازگشت به مقالات {{ $article->category->name }}</span>
                    </a>
                @else
                    <a href="{{ route('articles.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition">
                        <x-icons.arrow-right class="h-4 w-4" />
                        <span>بازگشت به لیست مقالات</span>
                    </a>
                @endif

                <a href="{{ route('home') }}" wire:navigate class="text-xs font-bold text-slate-400 hover:text-slate-700 transition">
                    صفحه اصلی
                </a>
            </div>
        </article>

        {{-- Commerce CTA Box --}}
        <section class="mt-10 rounded-3xl bg-[#010619] p-8 sm:p-10 text-white relative overflow-hidden border border-[#152244] shadow-xl">
            <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-[#ffde5b]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-2 text-center md:text-start max-w-xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/15 text-[#ffde5b] border border-[#ffde5b]/30">
                        <x-icons.sparkles class="w-3.5 h-3.5 text-[#ffde5b]" />
                        <span>شخصی‌سازی لوکس</span>
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">کارت فلزی اختصاصی خود را خلق کنید</h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        با انتخاب جنس فلز، رنگ مات یا براق و حکاکی لیزری طرح و نام دلخواه، کارت بانکی یا هوشمند متمایز خود را در استودیوی الواکارت طراحی کنید.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto shrink-0">
                    <a href="{{ route('custom-card.design') }}" wire:navigate
                       class="w-full sm:w-auto btn-brand-primary px-6 py-3 text-xs sm:text-sm font-bold shadow-lg shadow-[#ffde5b]/20 hover:scale-[1.02] active:scale-[0.98] transition text-center">
                        طراحی کارت اختصاصی
                    </a>
                    <a href="{{ route('catalog.products.index') }}" wire:navigate
                       class="w-full sm:w-auto px-5 py-3 rounded-xl text-xs sm:text-sm font-bold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 transition text-center">
                        مشاهده محصولات
                    </a>
                </div>
            </div>
        </section>

        {{-- Related Articles Section --}}
        @if(isset($relatedArticles) && $relatedArticles->isNotEmpty())
            <section class="mt-14" aria-label="مقالات مرتبط">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-black text-[#010619]">مقالات مرتبط و پیشنهادی</h2>
                        <p class="text-xs text-slate-500 mt-1">مطالب دیگری که ممکن است برای شما مفید باشد</p>
                    </div>
                    <a href="{{ route('articles.index') }}" wire:navigate class="text-xs font-bold text-slate-600 hover:text-[#010619] transition flex items-center gap-1">
                        <span>مشاهده همه</span>
                        <x-icons.chevron-left class="w-3.5 h-3.5" />
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($relatedArticles as $rel)
                        <article class="group store-card flex flex-col justify-between overflow-hidden bg-white rounded-2xl border border-slate-200/90 hover:border-slate-300 shadow-sm transition-all duration-300 hover:-translate-y-1">
                            <div>
                                <a href="{{ route('articles.show', $rel) }}" wire:navigate class="block overflow-hidden aspect-[16/9] bg-slate-100">
                                    @if($rel->cover_image)
                                        <img src="{{ asset('storage/' . $rel->cover_image) }}" alt="{{ $rel->title }}" class="w-full h-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-400">
                                            <x-icons.newspaper class="h-8 w-8 opacity-40" />
                                        </div>
                                    @endif
                                </a>

                                <div class="p-5">
                                    @if($rel->category)
                                        <span class="inline-block px-2.5 py-0.5 rounded-md bg-[#ffde5b]/15 text-[#664d00] border border-[#ffde5b]/40 text-[11px] font-bold mb-2">
                                            {{ $rel->category->name }}
                                        </span>
                                    @endif

                                    <h3 class="text-sm font-bold text-[#010619] line-clamp-2 leading-snug group-hover:text-slate-800 transition">
                                        <a href="{{ route('articles.show', $rel) }}" wire:navigate>
                                            {{ $rel->title }}
                                        </a>
                                    </h3>
                                </div>
                            </div>

                            <div class="px-5 pb-4 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                                @if($rel->published_at)
                                    <time datetime="{{ $rel->published_at->toISOString() }}">
                                        {{ jalali_date($rel->published_at, 'date') }}
                                    </time>
                                @endif
                                <a href="{{ route('articles.show', $rel) }}" wire:navigate class="font-bold text-[#010619] hover:text-[#664d00] transition flex items-center gap-1">
                                    <span>مطالعه</span>
                                    <x-icons.chevron-left class="w-3 h-3" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>
@endsection
