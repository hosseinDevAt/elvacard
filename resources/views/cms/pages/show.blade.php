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
                    @if (site_icon_enabled('phone'))
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                            <x-icons.phone class="h-8 w-8 mx-auto text-primary-600 mb-3" />
                            <h2 class="text-sm font-semibold text-gray-500 mb-1">تلفن</h2>
                            <a href="tel:{{ $contactPhone }}" dir="ltr" class="text-lg font-semibold text-gray-900 hover:text-primary-600 transition break-all">{{ $contactPhone }}</a>
                        </div>
                    @endif
                @endif

                @if ($contactEmail = site_setting('contact_email'))
                    @if (site_icon_enabled('mail'))
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                            <x-icons.mail class="h-8 w-8 mx-auto text-primary-600 mb-3" />
                            <h2 class="text-sm font-semibold text-gray-500 mb-1">ایمیل</h2>
                            <a href="mailto:{{ $contactEmail }}" class="text-lg font-semibold text-gray-900 hover:text-primary-600 transition break-all">{{ $contactEmail }}</a>
                        </div>
                    @endif
                @endif

                @if ($contactAddress = site_setting('contact_address'))
                    @if (site_icon_enabled('map_pin'))
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                            <x-icons.map-pin class="h-8 w-8 mx-auto text-primary-600 mb-3" />
                            <h2 class="text-sm font-semibold text-gray-500 mb-1">آدرس</h2>
                            <p class="text-lg font-semibold text-gray-900 break-words">{{ $contactAddress }}</p>
                        </div>
                    @endif
                @endif
            </section>
        @endif

        <div class="text-gray-700 leading-relaxed text-base sm:text-lg prose prose-persian max-w-none">
            {{ $page->content }}
        </div>
    </article>
</main>
@endsection