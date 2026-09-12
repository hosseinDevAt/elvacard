@extends('layouts.app')

@section('content')
<main>
    @include('components.homepage.banner-slider')

    @foreach ($sections as $section)
        @php
            $type = $section->section_type?->value;
            $data = $sectionData[$type] ?? [];
        @endphp

        @switch ($type)
            @case('hero')
                @include('components.homepage.hero', ['section' => $section])
                @break
            @case('banner')
                @include('components.homepage.banner', ['section' => $section])
                @break
            @case('featured_products')
                @include('components.homepage.featured-products', [
                    'section' => $section,
                    'products' => $data['products'] ?? collect(),
                ])
                @break
            @case('featured_designs')
                @include('components.homepage.featured-designs', [
                    'section' => $section,
                    'designs' => $data['designs'] ?? collect(),
                ])
                @break
            @case('faq')
                @include('components.homepage.faq', [
                    'section' => $section,
                    'faqs' => $data['faqs'] ?? collect(),
                ])
                @break
            @case('text_block')
                @include('components.homepage.text-block', ['section' => $section])
                @break
            @case('newest_products')
                @include('components.homepage.newest-products', [
                    'section' => $section,
                    'products' => $data['products'] ?? collect(),
                ])
                @break
            @case('articles')
                @include('components.homepage.articles', [
                    'section' => $section,
                    'articles' => $data['articles'] ?? collect(),
                ])
                @break
            @default
                {{-- Unknown section type - skip gracefully --}}
        @endswitch
    @endforeach
</main>
@endsection