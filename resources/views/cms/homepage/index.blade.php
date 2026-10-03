@extends('layouts.app')

@section('content')
<main>
    {{-- 1. Hero Banner Slider --}}
    @include('components.homepage.banner-slider')

    @php
        // Organize CMS sections by type to enforce the canonical storefront flow
        // while preserving all existing CMS data sources and admin configurations
        $heroSections = $sections->filter(fn ($s) => in_array($s->section_type?->value, ['hero', 'banner']));
        $featuredDesignsSection = $sections->firstWhere(fn ($s) => $s->section_type?->value === 'featured_designs');
        $featuredProductsSection = $sections->firstWhere(fn ($s) => $s->section_type?->value === 'featured_products');
        $newestProductsSection = $sections->firstWhere(fn ($s) => $s->section_type?->value === 'newest_products');
        $faqSection = $sections->firstWhere(fn ($s) => $s->section_type?->value === 'faq');
        $articlesSection = $sections->firstWhere(fn ($s) => $s->section_type?->value === 'articles');
        $otherSections = $sections->reject(fn ($s) => in_array($s->section_type?->value, [
            'hero', 'banner', 'featured_designs', 'featured_products', 'newest_products', 'faq', 'articles'
        ]));

        $designsData = $sectionData['featured_designs']['designs'] ?? collect();
        $productsData = $featuredProductsSection
            ? ($sectionData['featured_products']['products'] ?? collect())
            : ($sectionData['newest_products']['products'] ?? collect());
        $faqData = $sectionData['faq']['faqs'] ?? collect();
        $articlesData = $sectionData['articles']['articles'] ?? collect();
    @endphp

    {{-- Any top CMS hero/banner sections --}}
    @foreach ($heroSections as $section)
        @if ($section->section_type?->value === 'hero')
            @include('components.homepage.hero', ['section' => $section])
        @elseif ($section->section_type?->value === 'banner')
            @include('components.homepage.banner', ['section' => $section])
        @endif
    @endforeach

    {{-- 2. Featured Designs --}}
    @if ($featuredDesignsSection || $designsData->isNotEmpty())
        @include('components.homepage.featured-designs', [
            'section' => $featuredDesignsSection ?? (object)['title' => 'طرح‌های محبوب', 'content' => null, 'settings' => []],
            'designs' => $designsData,
        ])
    @endif

    {{-- 3. Best Selling Products --}}
    @if ($featuredProductsSection)
        @include('components.homepage.featured-products', [
            'section' => $featuredProductsSection,
            'products' => $productsData,
        ])
    @elseif ($newestProductsSection || $productsData->isNotEmpty())
        @include('components.homepage.newest-products', [
            'section' => $newestProductsSection ?? (object)['title' => 'جدیدترین محصولات', 'content' => null, 'settings' => []],
            'products' => $productsData,
        ])
    @endif

    {{-- 4. Trust / Benefits section --}}
    @include('components.homepage.trust')

    {{-- 5. FAQ section --}}
    @if ($faqSection || $faqData->isNotEmpty())
        @include('components.homepage.faq', [
            'section' => $faqSection ?? (object)['title' => 'سوالات متداول', 'content' => null, 'settings' => []],
            'faqs' => $faqData,
        ])
    @endif

    {{-- 6. Articles section --}}
    @if ($articlesSection || $articlesData->isNotEmpty())
        @include('components.homepage.articles', [
            'section' => $articlesSection ?? (object)['title' => 'آخرین مقالات', 'content' => null, 'settings' => []],
            'articles' => $articlesData,
        ])
    @endif

    {{-- 7. Other CMS sections (e.g. text_block) --}}
    @foreach ($otherSections as $section)
        @if ($section->section_type?->value === 'text_block')
            @include('components.homepage.text-block', ['section' => $section])
        @endif
    @endforeach
</main>
@endsection