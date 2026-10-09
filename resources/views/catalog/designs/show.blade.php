@extends('layouts.app')

@section('title'){{ $design->meta_title ?: $design->name }} - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    @if ($design->meta_description)
        <meta name="description" content="{{ $design->meta_description }}">
    @elseif($design->description)
        <meta name="description" content="{{ Str::limit($design->description, 160) }}">
    @endif

    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta name="robots" content="{{ $design->robots_index ? 'index,follow' : 'noindex,follow' }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $design->meta_title ?: $design->name }}">
    @if ($design->meta_description || $design->description)
        <meta property="og:description" content="{{ $design->meta_description ?: Str::limit($design->description, 160) }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($ogImageUrl)
        <meta property="og:image" content="{{ $ogImageUrl }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $design->meta_title ?: $design->name }}">

    @if ($schemaJson)
        <script type="application/ld+json">{!! $schemaJson !!}</script>
    @endif
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Breadcrumbs & Back Navigation --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <x-breadcrumbs :items="array_values(array_filter([
                ['label' => 'فهرست طرح‌ها', 'url' => route('catalog.designs.index')],
                $design->category ? ['label' => $design->category->name, 'url' => route('catalog.designs.index', ['category' => $design->category->slug])] : null,
                ['label' => $design->name]
            ]))" />

            <div class="flex items-center gap-2">
                @if($design->is_popular)
                    <a href="{{ route('catalog.designs.popular') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-[#010619] bg-amber-50 border border-amber-200/80 px-3.5 py-2 rounded-xl shadow-xs transition hover:border-amber-300">
                        <span class="text-amber-600">★</span>
                        <span>طرح‌های پرطرفدار</span>
                    </a>
                @endif

                <a href="{{ route('catalog.designs.index') }}" wire:navigate
                   class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200/90 px-3.5 py-2 rounded-xl shadow-xs transition hover:border-slate-300">
                    <x-icons.arrow-right class="w-4 h-4" />
                    <span>بازگشت به گالری طرح‌ها</span>
                </a>
            </div>
        </div>

        {{-- Main Design Showcase Surface --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            {{-- Preview Card (Right column on RTL) --}}
            <div class="lg:col-span-7">
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs">
                    {{-- Visual Card Frame --}}
                    <div class="relative w-full aspect-[16/10] sm:aspect-[1.586/1] rounded-2xl bg-[#F3F1EB] border border-slate-200/80 shadow-sm overflow-hidden flex items-center justify-center p-6 group">
                        @if ($selectedVariant)
                            <img
                                src="{{ asset('storage/' . ltrim($selectedVariant['image_path'], '/')) }}"
                                alt="{{ $selectedVariant['alt_text'] }}"
                                class="relative z-10 max-h-full max-w-full object-contain drop-shadow-2xl"
                            >
                        @else
                            {{-- Graceful Empty State for Design with no valid active variants --}}
                            <div class="relative z-10 flex flex-col items-center justify-center text-slate-500 p-8 text-center">
                                <x-icons.palette class="w-12 h-12 text-slate-400 mb-3" />
                                <p class="text-sm font-bold text-slate-700">در حال حاضر نسخه تصویری فعالی برای این طرح ثبت نشده است</p>
                                <p class="text-xs text-slate-500 mt-1">به‌زودی نسخه‌های گرافیکی این طرح افزوده خواهد شد.</p>
                            </div>
                        @endif

                        {{-- Category Chip on top-start --}}
                        @if ($design->category)
                            <div class="absolute top-4 start-4 z-20">
                                <a href="{{ route('catalog.designs.index', ['category' => $design->category->slug]) }}" wire:navigate
                                   class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-white/95 text-slate-800 backdrop-blur-sm shadow-sm hover:bg-white transition">
                                    {{ $design->category->name }}
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer Details --}}
                    <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>طرح آماده برای حکاکی و چاپ روی کارت فلزی</span>
                        </span>
                        <span class="font-mono text-slate-400" dir="ltr">#{{ $design->slug }}</span>
                    </div>
                </div>
            </div>

            {{-- Design Info & Variant Selector (Left column on RTL) --}}
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
                    {{-- Badges & Title --}}
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @if ($design->is_popular)
                                <a href="{{ route('catalog.designs.popular') }}" wire:navigate
                                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/40">
                                    <span class="text-amber-500">★</span>
                                    <span>طرح پرطرفدار</span>
                                </a>
                            @endif

                            @if ($design->category)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                                    دسته: {{ $design->category->name }}
                                </span>
                            @endif
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black text-[#010619] tracking-tight">
                            {{ $design->name }}
                        </h1>

                        @if ($design->description)
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                                {{ $design->description }}
                            </p>
                        @endif
                    </div>

                    {{-- Available Variants Information (Non-interactive) --}}
                    @if ($variants->isNotEmpty())
                        <div class="border-t border-slate-100 pt-6 space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-bold text-slate-900">رنگ‌بندی‌های قابل سفارش</h2>
                                <span class="text-xs text-slate-400">{{ fa_digits($variants->count()) }} رنگ حکاکی</span>
                            </div>

                            {{-- Informational Badges (Non-interactive) --}}
                            <div class="flex flex-wrap gap-2">
                                @foreach ($variants as $variant)
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200/90 bg-slate-50/80 text-xs font-medium text-slate-700">
                                        <span
                                            class="h-3 w-3 rounded-full shrink-0 border border-black/15 shadow-2xs"
                                            style="background-color: {{ $variant['color_hex'] }}"
                                        ></span>
                                        <span>{{ $variant['color_name'] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="text-[11px] text-slate-500 leading-relaxed mt-1">
                                انتخاب و تغییر رنگ ورقه فلزی کارت و رنگ حکاکی در مرحله بعد درون محیط طراح کارت انجام می‌شود.
                            </p>
                        </div>
                    @else
                        <div class="border-t border-slate-100 pt-4">
                            <p class="text-xs text-slate-500">رنگ‌بندی ثابتی برای این طرح تعریف نشده است.</p>
                        </div>
                    @endif

                    {{-- Action Links --}}
                    <div class="border-t border-slate-100 pt-6 space-y-3">
                        <a
                            href="{{ route('custom-card.bank', array_filter(['design' => $design->slug, 'variant' => $selectedVariant['id'] ?? null])) }}"
                            wire:navigate
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-[#010619] px-6 py-3.5 text-xs sm:text-sm font-bold text-white hover:bg-slate-800 transition shadow-sm hover:scale-[1.01] active:scale-[0.99]"
                        >
                            <span>طراحی با این طرح</span>
                            <x-icons.arrow-left class="w-4 h-4 text-[#ffde5b]" />
                        </a>

                        <div class="flex items-center justify-between gap-2 text-xs">
                            <a href="{{ route('catalog.designs.index') }}" wire:navigate class="text-slate-600 hover:text-[#010619] underline underline-offset-4">
                                مشاهده همه طرح‌های گالری
                            </a>

                            @if ($design->is_popular)
                                <a href="{{ route('catalog.designs.popular') }}" wire:navigate class="text-amber-700 hover:text-amber-900 underline underline-offset-4 font-bold">
                                    سایر طرح‌های پرطرفدار
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- SEO Content Section (if present) --}}
                @if ($design->seo_content)
                    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-8 shadow-xs">
                        <h3 class="text-sm font-bold text-slate-900 mb-3">توضیحات تکمیلی طرح</h3>
                        <div class="text-xs leading-relaxed text-slate-600 space-y-2 whitespace-pre-line">
                            {{ $design->seo_content }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
