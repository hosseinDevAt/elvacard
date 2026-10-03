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
        <x-breadcrumbs :items="[
            ['label' => 'فروشگاه محصولات', 'url' => route('catalog.products.index')],
            ['label' => $product->name]
        ]" />

        <div class="mb-8">
            <a href="{{ route('catalog.products.index') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200/90 px-3.5 py-2 rounded-xl shadow-xs transition hover:border-slate-300">
                <x-icons.arrow-right class="w-4 h-4" />
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

        {{-- Trust & Benefits Section --}}
        <section class="mt-12 rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.sparkles class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">تولید با کیفیت برتر</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">فلز خالص و حکاکی دقیق لیزری</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.box class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">ارسال سریع و مطمئن</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">بسته‌بندی ایمن و تحویل اکسپرس</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.check-badge class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">امکان پیگیری سفارش</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">رهگیری لحظه‌ای با کد اختصاصی</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.card class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">طراحی کاملاً اختصاصی</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">استودیوی ساخت کارت شخصی‌سازی‌شده</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Full Description / SEO Content --}}
        @if ($product->seo_content || $product->description)
            <section class="mt-8 rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                    <span>مشخصات و توضیحات تکمیلی محصول</span>
                </h2>
                <div class="whitespace-pre-line leading-relaxed text-sm sm:text-base text-slate-700">
                    {{ $product->seo_content ?: $product->description }}
                </div>
            </section>
        @endif

        {{-- Similar Products Section --}}
        @if (isset($similarProducts) && $similarProducts->isNotEmpty())
            <section class="mt-14" aria-label="محصولات مشابه">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-[#010619] tracking-tight">محصولات مشابه و پرطرفدار</h2>
                        <p class="text-xs text-slate-500 mt-1">سایر محصولات فروشگاه الواکارت که کاربران دیگر پسندیده‌اند</p>
                    </div>
                    <a href="{{ route('catalog.products.index') }}" wire:navigate class="text-xs font-bold text-slate-700 hover:text-[#010619] transition flex items-center gap-1">
                        <span>مشاهده همه محصولات</span>
                        <x-icons.chevron-left class="w-3.5 h-3.5" />
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($similarProducts as $simProduct)
                        @include('catalog.partials.product-card', ['product' => $simProduct])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Custom Design Studio Banner --}}
        <section class="mt-14 rounded-3xl bg-[#010619] p-8 sm:p-12 text-white relative overflow-hidden border border-[#152244] shadow-2xl">
            <div class="pointer-events-none absolute -top-32 -left-32 h-80 w-80 rounded-full bg-[#ffde5b]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="space-y-3 text-center lg:text-start max-w-xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/15 text-[#ffde5b] border border-[#ffde5b]/30">
                        <x-icons.sparkles class="w-3.5 h-3.5 text-[#ffde5b]" />
                        <span>استودیوی ساخت کارت شخصی‌سازی‌شده</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">به دنبال کارت اختصاصی با نام و طرح خودتان هستید؟</h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        در استودیوی آنلاین الواکارت می‌توانید کارت بانکی یا کارت سوخت هوشمند خود را با فلز مات یا براق و حکاکی لیزری اختصاصی در چند دقیقه بسازید.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto shrink-0">
                    <a href="{{ route('custom-card.bank') }}" wire:navigate
                       class="w-full sm:w-auto btn-brand-primary px-7 py-3.5 text-xs sm:text-sm font-bold shadow-lg shadow-[#ffde5b]/20 hover:scale-[1.02] active:scale-[0.98] transition text-center">
                        طراحی کارت بانکی
                    </a>
                    <a href="{{ route('custom-card.fuel') }}" wire:navigate
                       class="w-full sm:w-auto px-6 py-3.5 rounded-xl text-xs sm:text-sm font-bold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 transition text-center">
                        طراحی کارت سوخت
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection