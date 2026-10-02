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
        <div class="mb-8">
            <a href="{{ route('catalog.products.index') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200/90 px-3.5 py-2 rounded-xl shadow-xs transition hover:border-slate-300">
                <x-icons.arrow-left class="w-4 h-4" />
                <span>بازگشت به محصولات</span>
            </a>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $product->name }}</h1>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#ffde5b]/20 text-[#664d00] border border-[#ffde5b]/50 text-xs font-bold">
                    {{ $product->type?->faLabel() ?? $product->type }}
                </span>
            </div>
            @if($product->description)
                <p class="mt-2.5 max-w-3xl text-sm leading-relaxed text-slate-600">{{ $product->description }}</p>
            @endif
        </div>

        <div class="mb-8">
            @if($hasCustomization && $customizationAvailable)
                <livewire:catalog.product-customizer :productId="$product->id" />
            @elseif($hasCustomization)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 text-sm text-amber-800 shadow-xs">
                    این محصول تا راه‌اندازی سرویس شخصی‌سازی هنوز قابل خرید نیست.
                </div>
            @else
                @if($purchasable)
                    <livewire:catalog.product-gallery
                        :productId="$product->id"
                        :colorId="$selectedColorId > 0 ? $selectedColorId : null"
                    />
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 text-sm text-amber-800 shadow-xs">
                        این محصول در حال حاضر قابل خرید نیست.
                    </div>
                @endif
            @endif
        </div>

        @if ($product->seo_content)
            <section class="mt-10 rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100">درباره این محصول</h2>
                <div class="whitespace-pre-line leading-relaxed text-sm sm:text-base text-slate-700">
                    {{ $product->seo_content }}
                </div>
            </section>
        @endif
    </div>
@endsection