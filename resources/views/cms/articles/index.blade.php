@extends('layouts.app')

@section('meta')
    <title>مقالات - {{ config('app.name') }}</title>
    <meta name="description" content="مقالات و راهنماهای {{ config('app.name') }}">
@endsection

@section('content')
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <header class="mb-10">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">مقالات</h1>
        <p class="text-gray-600">آخرین مطالب و راهنماها</p>
    </header>

    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($articles as $article)
            <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition duration-300">
                @if ($article->cover_image)
                    <img src="{{ asset('storage/' . $article->cover_image) }}" alt="{{ $article->title }}" class="w-full h-48 object-cover">
                @endif
                <div class="p-6">
                    @if ($article->category)
                        <span class="inline-block px-2 py-1 text-xs font-medium text-primary-700 bg-primary-100 rounded-full mb-3">{{ $article->category->name }}</span>
                    @endif
                    <h2 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2">
                        <a href="{{ route('articles.show', $article) }}" wire:navigate class="hover:text-primary-600 transition">
                            {{ $article->title }}
                        </a>
                    </h2>
                    @if ($article->excerpt)
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">{{ $article->excerpt }}</p>
                    @endif
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        @if ($article->published_at)
                            <time datetime="{{ $article->published_at->toISOString() }}">
                                {{ $article->published_at->format('Y/m/d') }}
                            </time>
                        @endif
                        <a href="{{ route('articles.show', $article) }}" wire:navigate class="text-primary-600 hover:text-primary-700 font-medium">
                            خواندن بیشتر
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                مقاله‌ای منتشر نشده است.
            </div>
        @endforelse
    </div>

    <div class="mt-10">
        {{ $articles->links() }}
    </div>
</main>
@endsection