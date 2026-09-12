@extends('layouts.app')

@section('meta')
    @if ($page->meta_title)
        <title>{{ $page->meta_title }} - {{ site_setting('site_name', config('app.name')) }}</title>
    @else
        <title>{{ $page->title }} - {{ site_setting('site_name', config('app.name')) }}</title>
    @endif

    @if ($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif

    @if ($page->canonical_url)
        <link rel="canonical" href="{{ $page->canonical_url }}">
    @endif

    @if ($page->robots_index === false)
        <meta name="robots" content="noindex, nofollow">
    @endif
@endsection

@section('content')
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <article class="max-w-none">
        <header class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>

            @if ($page->image_path)
                <img src="{{ asset('storage/' . $page->image_path) }}" alt="{{ $page->title }}" class="w-full rounded-xl shadow-sm mb-6">
            @endif
        </header>

        @if ($page->page_type === 'contact')
            <section class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-8">
                @if ($contactPhone = site_setting('contact_phone'))
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                        <svg class="h-8 w-8 mx-auto text-primary-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-gray-500 mb-1">تلفن</h2>
                        <a href="tel:{{ $contactPhone }}" dir="ltr" class="text-lg font-semibold text-gray-900 hover:text-primary-600 transition break-all">{{ $contactPhone }}</a>
                    </div>
                @endif

                @if ($contactEmail = site_setting('contact_email'))
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                        <svg class="h-8 w-8 mx-auto text-primary-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-gray-500 mb-1">ایمیل</h2>
                        <a href="mailto:{{ $contactEmail }}" class="text-lg font-semibold text-gray-900 hover:text-primary-600 transition break-all">{{ $contactEmail }}</a>
                    </div>
                @endif

                @if ($contactAddress = site_setting('contact_address'))
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                        <svg class="h-8 w-8 mx-auto text-primary-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <h2 class="text-sm font-semibold text-gray-500 mb-1">آدرس</h2>
                        <p class="text-lg font-semibold text-gray-900 break-words">{{ $contactAddress }}</p>
                    </div>
                @endif
            </section>
        @endif

        <div class="text-gray-700 leading-relaxed text-base sm:text-lg prose prose-persian max-w-none">
            {{ $page->content }}
        </div>
    </article>
</main>
@endsection