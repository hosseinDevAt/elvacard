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
    </div>
</main>
@endsection
