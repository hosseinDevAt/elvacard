@extends('layouts.app')

@section('title'){{ $product->meta_title ?: $product->name }} - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    @if ($product->meta_description)
        <meta name="description" content="{{ $product->meta_description }}">
    @endif

    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta name="robots" content="{{ $product->robots_index ? 'index,follow' : 'noindex,follow' }}">

    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->meta_title ?: $product->name }}">
    @if ($product->meta_description)
        <meta property="og:description" content="{{ $product->meta_description }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($ogImageUrl)
        <meta property="og:image" content="{{ $ogImageUrl }}">
    @endif

    @if ($ogImageUrl)
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $product->meta_title ?: $product->name }}">
    @if ($product->meta_description)
        <meta name="twitter:description" content="{{ $product->meta_description }}">
    @endif
    @if ($ogImageUrl)
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @endif

    <script type="application/ld+json">{!! $schemaJson !!}</script>
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('catalog.products.index') }}" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 transition">
                <x-icons.arrow-left class="ms-1" />
                بازگشت به محصولات
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">نوع: {{ $product->type?->faLabel() ?? $product->type }}</p>
            @if($product->description)
                <p class="mt-3 text-sm text-gray-700">{{ $product->description }}</p>
            @endif
        </div>

        <div class="mb-6">
            @if($hasCustomization && $customizationAvailable)
                <livewire:catalog.product-customizer :productId="$product->id" />
            @elseif($hasCustomization)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    این محصول تا راه‌اندازی سرویس شخصی‌سازی هنوز قابل خرید نیست.
                </div>
            @else
                @if($purchasable)
                    <livewire:catalog.product-gallery
                        :productId="$product->id"
                        :colorId="$selectedColorId > 0 ? $selectedColorId : null"
                    />
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        این محصول در حال حاضر قابل خرید نیست.
                    </div>
                @endif
            @endif
        </div>

        @if ($product->seo_content)
            <section class="mt-8 rounded-xl border border-gray-200 bg-white p-6">
                <div class="whitespace-pre-line leading-relaxed text-gray-700">
                    {{ $product->seo_content }}
                </div>
            </section>
        @endif
    </div>
@endsection