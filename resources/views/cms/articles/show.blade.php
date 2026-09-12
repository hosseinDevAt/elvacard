@extends('layouts.app')

@section('meta')
    @if ($article->meta_title)
        <title>{{ $article->meta_title }} - {{ config('app.name') }}</title>
    @else
        <title>{{ $article->title }} - {{ config('app.name') }}</title>
    @endif

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
<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <article class="prose prose-persian max-w-none">
        <header class="mb-8">
            @if ($article->category)
                <a href="{{ route('articles.index') }}" class="inline-block text-sm font-medium text-primary-600 hover:text-primary-700 mb-3">
                    {{ $article->category->name }}
                </a>
            @endif
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ $article->title }}</h1>

            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                @if ($article->published_at)
                    <time datetime="{{ $article->published_at->toISOString() }}">
                        {{ $article->published_at->format('Y/m/d') }}
                    </time>
                @endif
            </div>

            @if ($article->cover_image)
                <img src="{{ asset('storage/' . $article->cover_image) }}" alt="{{ $article->title }}" class="w-full rounded-xl shadow-sm mt-6 mb-8">
            @endif
        </header>

        @if ($article->excerpt)
            <div class="text-gray-600 mb-8 p-4 bg-gray-50 rounded-xl border border-gray-100">
                {{ $article->excerpt }}
            </div>
        @endif

        <div class="text-gray-700 leading-relaxed">
            {!! $article->content !!}
        </div>
    </article>

    <hr class="my-10 border-gray-200">

    <div class="text-center">
        <a href="{{ route('articles.index') }}" class="text-primary-600 hover:text-primary-700 font-medium">
            ← بازگشت به لیست مقالات
        </a>
    </div>
</main>
@endsection