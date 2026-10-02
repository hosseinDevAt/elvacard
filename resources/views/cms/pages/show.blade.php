@extends('layouts.app')

@php
    $siteName = site_setting('site_name', config('app.name'));
    $baseTitle = $page->meta_title ?: $page->title;
    $title = str_ends_with(trim($baseTitle), $siteName)
        ? $baseTitle
        : ($baseTitle . ' - ' . $siteName);
@endphp
@section('title', $title)

@section('meta')
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
<main class="min-h-[70vh] bg-slate-50/50 py-10 sm:py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <article class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-10 shadow-sm">
            <header class="mb-8 border-b border-slate-100 pb-6">
                <h1 class="text-2xl sm:text-4xl font-black text-[#010619] tracking-tight mb-4">{{ $page->title }}</h1>

                @if ($page->image_path)
                    <div class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 mt-6">
                        <img src="{{ asset('storage/' . $page->image_path) }}" alt="{{ $page->title }}" class="w-full h-auto max-h-[440px] object-cover">
                    </div>
                @endif
            </header>

            @if ($page->page_type === 'contact')
                <section class="grid grid-cols-1 gap-5 md:grid-cols-3 mb-10">
                    @if ($contactPhone = site_setting('contact_phone'))
                        @if (site_icon_enabled('phone'))
                            <div class="store-card p-6 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-xs">
                                    <x-icons.phone class="h-6 w-6" />
                                </div>
                                <h2 class="text-xs font-bold text-slate-500 mb-1">شماره تماس پشتیبانی</h2>
                                <a href="tel:{{ $contactPhone }}" dir="ltr" class="text-base font-bold text-[#010619] hover:text-[#664d00] transition break-all font-mono">{{ $contactPhone }}</a>
                            </div>
                        @endif
                    @endif

                    @if ($contactEmail = site_setting('contact_email'))
                        @if (site_icon_enabled('mail'))
                            <div class="store-card p-6 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-xs">
                                    <x-icons.mail class="h-6 w-6" />
                                </div>
                                <h2 class="text-xs font-bold text-slate-500 mb-1">پست الکترونیکی</h2>
                                <a href="mailto:{{ $contactEmail }}" class="text-sm font-bold text-[#010619] hover:text-[#664d00] transition break-all">{{ $contactEmail }}</a>
                            </div>
                        @endif
                    @endif

                    @if ($contactAddress = site_setting('contact_address'))
                        @if (site_icon_enabled('map_pin'))
                            <div class="store-card p-6 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-xs">
                                    <x-icons.map-pin class="h-6 w-6" />
                                </div>
                                <h2 class="text-xs font-bold text-slate-500 mb-1">نشانی دفتر مرکزی</h2>
                                <p class="text-xs font-bold text-slate-800 break-words leading-relaxed">{{ $contactAddress }}</p>
                            </div>
                        @endif
                    @endif
                </section>
            @endif

            <div class="text-slate-800 leading-relaxed text-sm sm:text-base prose prose-slate prose-persian max-w-none">
                {{ $page->content }}
            </div>
        </article>
    </div>
</main>
@endsection