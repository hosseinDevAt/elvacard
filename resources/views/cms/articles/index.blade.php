@extends('layouts.app')

@section('title'){{ isset($category) ? $category->name : 'مقالات' }} - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    @if (isset($category))
        <meta name="description" content="مقالات دسته‌بندی {{ $category->name }}">
    @else
        <meta name="description" content="مقالات و راهنماهای {{ site_setting('site_name', config('app.name')) }}">
    @endif
@endsection

@section('content')
<main class="min-h-[70vh] bg-slate-50/50 py-10 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <header class="mb-10 text-center sm:text-start border-b border-slate-200/80 pb-6">
            @if (isset($category))
                <a href="{{ route('articles.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-[#010619] mb-3 transition">
                    <x-icons.arrow-left class="h-4 w-4" />
                    <span>همه مقالات</span>
                </a>
                <h1 class="text-2xl sm:text-4xl font-black text-[#010619] tracking-tight mb-2">{{ $category->name }}</h1>
            @else
                <h1 class="text-2xl sm:text-4xl font-black text-[#010619] tracking-tight mb-2">مقالات و راهنماها</h1>
            @endif
            <p class="text-sm text-slate-600">آخرین مطالب، نکات آموزشی و راهنماهای تخصصی کارت‌های هوشمند و متالیک</p>
        </header>

        {{-- Articles Grid --}}
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($articles as $article)
                <article class="group store-card flex flex-col justify-between overflow-hidden">
                    <div>
                        @if ($article->cover_image)
                            <div class="overflow-hidden aspect-[16/9] bg-slate-100">
                                <img src="{{ asset('storage/' . $article->cover_image) }}" alt="{{ $article->title }}" class="w-full h-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                            </div>
                        @else
                            <div class="flex aspect-[16/9] items-center justify-center bg-slate-100 text-slate-400">
                                <x-icons.newspaper class="h-10 w-10 opacity-40" />
                            </div>
                        @endif

                        <div class="p-6">
                            @if ($article->category)
                                <a href="{{ route('articles.category', $article->category) }}" wire:navigate class="inline-block badge-brand mb-3 hover:bg-[#ffde5b]/30 transition">{{ $article->category->name }}</a>
                            @endif

                            <h2 class="text-base font-bold text-[#010619] mb-2 line-clamp-2 leading-snug group-hover:text-slate-800 transition">
                                <a href="{{ route('articles.show', $article) }}" wire:navigate>
                                    {{ $article->title }}
                                </a>
                            </h2>

                            @if ($article->excerpt)
                                <p class="text-slate-600 text-xs mb-4 line-clamp-3 leading-relaxed">{{ $article->excerpt }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="px-6 pb-6 pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        @if ($article->published_at)
                            <time datetime="{{ $article->published_at->toISOString() }}" class="text-[11px] text-slate-400 font-medium">
                                {{ jalali_date($article->published_at, 'date') }}
                            </time>
                        @endif
                        <a href="{{ route('articles.show', $article) }}" wire:navigate class="inline-flex items-center gap-1 font-bold text-[#010619] hover:text-[#664d00] transition">
                            <span>خواندن مقاله</span>
                            <x-icons.arrow-left class="h-3.5 w-3.5 transition-transform group-hover:-translate-x-0.5" />
                        </a>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-xs">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <x-icons.newspaper class="h-7 w-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-[#010619]">مقاله‌ای منتشر نشده است</h3>
                    <p class="mt-1 text-xs text-slate-500">به‌زودی مقالات و راهنماهای جدید در این بخش قرار خواهد گرفت.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($articles->hasPages())
            <div class="mt-10">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</main>
@endsection