@extends('layouts.app')

@section('title', 'فروشگاه محصولات - ' . site_setting('site_name', config('app.name')))

@section('content')
@php
    $activeFilterCount = ($search ? 1 : 0)
        + ($selectedType ? 1 : 0)
        + ($selectedCategory ? 1 : 0)
        + ($selectedColorId ? 1 : 0)
        + ($minPrice > 0 || $maxPrice > 0 ? 1 : 0)
        + ($sort !== 'newest' ? 1 : 0);
@endphp

<div class="min-h-screen bg-slate-50/50 py-8 sm:py-12" x-data="{ viewMode: 'grid', mobileFiltersOpen: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-breadcrumbs :items="[
            ['label' => 'فروشگاه محصولات']
        ]" />

        {{-- Commerce Header & Small Hero Banner --}}
        <section class="mb-10 rounded-3xl bg-[#010619] p-8 sm:p-10 text-white relative overflow-hidden border border-[#152244] shadow-xl">
            <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-[#ffde5b]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="space-y-3 max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/15 text-[#ffde5b] border border-[#ffde5b]/30">
                            <x-icons.shopping-bag class="w-3.5 h-3.5 text-[#ffde5b]" />
                            <span>کالکشن رسمی الواکارت</span>
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-slate-300 border border-white/10">
                            {{ $products->total() }} محصول موجود
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-tight">
                        فروشگاه محصولات و اکسسوری‌های فلزی
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        محصولات فیزیکی، هولدرها و کارت‌های استاندارد با متریال استیل و تیتانیوم ضدخش، آماده سفارش و ارسال سریع به سراسر کشور.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('custom-card.design') }}" wire:navigate
                       class="btn-brand-primary px-5 py-3 text-xs sm:text-sm font-bold shadow-lg shadow-[#ffde5b]/20 hover:scale-[1.02] active:scale-[0.98] transition">
                        استودیوی طراحی اختصاصی
                    </a>
                </div>
            </div>
        </section>

        {{-- Toolbar: Search, Sort, View Toggle, Mobile Filter Trigger --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 rounded-2xl bg-white p-4 border border-slate-200/90 shadow-sm">
            {{-- Left: Filter Trigger & Active Badges --}}
            <div class="flex items-center gap-3">
                <button type="button"
                        @click="mobileFiltersOpen = true"
                        class="lg:hidden inline-flex items-center gap-2 rounded-xl bg-[#010619] px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 transition cursor-pointer">
                    <x-icons.list class="w-4 h-4 text-[#ffde5b]" />
                    <span>فیلتر و دسته‌بندی</span>
                    @if($activeFilterCount > 0)
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#ffde5b] text-[10px] font-black text-[#010619]">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </button>

                <span class="text-xs font-bold text-slate-500 hidden sm:inline">
                    نمایش {{ $products->count() }} از {{ $products->total() }} محصول
                </span>
            </div>

            {{-- Right: Sort & Layout Toggle --}}
            <div class="flex flex-wrap items-center gap-3 ms-auto">
                {{-- Sort Dropdown / Form --}}
                <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                    <span class="hidden sm:inline">مرتب‌سازی:</span>
                    <form method="GET" action="{{ route('catalog.products.index') }}" id="sortForm">
                        @if($search) <input type="hidden" name="search" value="{{ $search }}"> @endif
                        @if($selectedType) <input type="hidden" name="type" value="{{ $selectedType }}"> @endif
                        @if($selectedCategory) <input type="hidden" name="category" value="{{ $selectedCategory }}"> @endif
                        @if($selectedColorId) <input type="hidden" name="color_id" value="{{ $selectedColorId }}"> @endif
                        @if($minPrice) <input type="hidden" name="min_price" value="{{ $minPrice }}"> @endif
                        @if($maxPrice) <input type="hidden" name="max_price" value="{{ $maxPrice }}"> @endif

                        <select name="sort"
                                onchange="document.getElementById('sortForm').submit()"
                                class="rounded-xl border border-slate-200 bg-slate-50/70 py-2 ps-3.5 pe-8 text-xs font-bold text-slate-800 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b] cursor-pointer">
                            <option value="newest" @selected($sort === 'newest')>جدیدترین‌ها</option>
                            <option value="cheapest" @selected($sort === 'cheapest')>ارزان‌ترین‌ها</option>
                            <option value="expensive" @selected($sort === 'expensive')>گران‌ترین‌ها</option>
                            <option value="popular" @selected($sort === 'popular')>محبوب‌ترین‌ها</option>
                        </select>
                    </form>
                </div>

                {{-- View Toggle Buttons (Grid vs Compact) --}}
                <div class="flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200">
                    <button type="button"
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white text-[#010619] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            class="p-1.5 rounded-lg transition cursor-pointer"
                            title="نمایش شبکه‌ای">
                        <x-icons.grid class="w-4 h-4" />
                    </button>
                    <button type="button"
                            @click="viewMode = 'compact'"
                            :class="viewMode === 'compact' ? 'bg-white text-[#010619] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            class="p-1.5 rounded-lg transition cursor-pointer"
                            title="نمایش متراکم">
                        <x-icons.list class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Active Filters Badges Strip --}}
        @if($activeFilterCount > 0)
            <div class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl bg-[#010619] px-4 py-3 text-xs text-slate-200 shadow-md">
                <span class="font-bold text-[#ffde5b]">فیلترهای فعال:</span>
                @if($search)
                    <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">جستجو: «{{ $search }}»</span>
                @endif
                @if($selectedType)
                    <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">نوع: {{ $selectedType }}</span>
                @endif
                @if($selectedCategoryName)
                    <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">دسته‌بندی: {{ $selectedCategoryName }}</span>
                @endif
                @if($selectedColorId)
                    @php $colorName = $colors->firstWhere('id', $selectedColorId)?->name; @endphp
                    @if($colorName)
                        <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">رنگ: {{ $colorName }}</span>
                    @endif
                @endif
                @if($minPrice)
                    <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">از {{ number_format($minPrice) }} تومان</span>
                @endif
                @if($maxPrice)
                    <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">تا {{ number_format($maxPrice) }} تومان</span>
                @endif
                @if($sort !== 'newest')
                    <span class="rounded-lg bg-white/10 px-2.5 py-1 text-xs">مرتب‌سازی: {{ $sort }}</span>
                @endif
                <a href="{{ route('catalog.products.index') }}" wire:navigate class="ms-auto text-xs font-bold text-[#ffde5b] hover:underline">
                    پاک کردن همه
                </a>
            </div>
        @endif

        {{-- Main Layout: Desktop Sidebar + Product Grid --}}
        <div class="grid gap-8 lg:grid-cols-[280px_1fr] items-start">
            {{-- Desktop Filters Sidebar --}}
            <aside class="hidden lg:block space-y-6">
                <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm sticky top-24">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h2 class="text-sm font-black text-[#010619] flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                            <span>فیلتر محصولات</span>
                        </h2>
                        @if($activeFilterCount > 0)
                            <a href="{{ route('catalog.products.index') }}" wire:navigate class="text-xs font-bold text-rose-600 hover:underline">
                                پاک کردن
                            </a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('catalog.products.index') }}" class="space-y-6">
                        @if($sort !== 'newest')
                            <input type="hidden" name="sort" value="{{ $sort }}">
                        @endif

                        {{-- Search box --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">جستجو در فروشگاه</label>
                            <div class="relative">
                                <input type="search" name="search" value="{{ $search }}" placeholder="نام یا ویژگی محصول..."
                                       class="w-full rounded-xl border border-slate-200 ps-9 pe-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                                <x-icons.search class="w-4 h-4 text-slate-400 absolute start-3 top-2.5 pointer-events-none" />
                            </div>
                        </div>

                        {{-- Category filter --}}
                        @if($categories->isNotEmpty())
                            <div>
                                <h3 class="mb-2 text-xs font-bold text-slate-700">دسته‌بندی‌ها</h3>
                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none p-1.5 rounded-lg hover:bg-slate-50 transition">
                                        <input type="radio" name="category" value="" @checked(!$selectedCategory) class="text-[#010619] focus:ring-[#ffde5b]">
                                        <span>همه دسته‌ها</span>
                                    </label>
                                    @foreach($categories as $category)
                                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none p-1.5 rounded-lg hover:bg-slate-50 transition">
                                            <input type="radio" name="category" value="{{ $category->slug }}" @checked($selectedCategory === $category->slug) class="text-[#010619] focus:ring-[#ffde5b]">
                                            <span>{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Type filter --}}
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">نوع محصول</h3>
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none p-1.5 rounded-lg hover:bg-slate-50 transition">
                                    <input type="radio" name="type" value="" @checked(!$selectedType) class="text-[#010619] focus:ring-[#ffde5b]">
                                    <span>همه انواع</span>
                                </label>
                                @foreach($types as $typeOption)
                                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer select-none p-1.5 rounded-lg hover:bg-slate-50 transition">
                                        <input type="radio" name="type" value="{{ $typeOption->value }}" @checked($selectedType === $typeOption->value) class="text-[#010619] focus:ring-[#ffde5b]">
                                        <span>{{ $typeOption->faLabel() }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Color Swatches Filter --}}
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">رنگ فلز</h3>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($colors as $color)
                                    @php $isColorSelected = (int)$selectedColorId === (int)$color->id; @endphp
                                    <label class="flex flex-col items-center gap-1 p-2 rounded-xl border {{ $isColorSelected ? 'border-[#010619] bg-slate-100 ring-2 ring-[#ffde5b]' : 'border-slate-200 hover:border-slate-300' }} cursor-pointer text-center transition" title="{{ $color->name }}">
                                        <input type="radio" name="color_id" value="{{ $color->id }}" @checked($isColorSelected) class="sr-only">
                                        <span class="inline-block h-4 w-4 rounded-full border border-black/15 shadow-xs" style="background-color: {{ $color->code_hex }}"></span>
                                        <span class="text-[10px] text-slate-700 truncate w-full">{{ $color->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Price range --}}
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">بازه قیمت (تومان)</h3>
                            <div class="space-y-2">
                                <input type="number" name="min_price" value="{{ $minPrice ?: '' }}" placeholder="از قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                                <input type="number" name="max_price" value="{{ $maxPrice ?: '' }}" placeholder="تا قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full btn-brand-primary py-3 text-xs font-bold shadow-md shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] transition cursor-pointer">
                                اعمال فیلترها
                            </button>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Products Grid / List Container --}}
            <div>
                <div :class="viewMode === 'grid' ? 'grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3' : 'space-y-4'">
                    @forelse($products as $product)
                        @include('catalog.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-4">
                                <x-icons.search class="h-8 w-8" />
                            </div>
                            <h3 class="text-base font-bold text-slate-800 mb-1">محصولی با این مشخصات یافت نشد</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mb-6">می‌توانید فیلترها را حذف کنید یا عبارت دیگری را جستجو نمایید.</p>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate
                               class="inline-flex rounded-xl bg-[#ffde5b] px-6 py-2.5 text-xs sm:text-sm font-bold text-[#010619] shadow-sm shadow-[#ffde5b]/25 hover:bg-[#f5d347] transition">
                                مشاهده همه محصولات
                            </a>
                        </div>
                    @endforelse
                </div>

                @if($products->hasPages())
                    <div class="mt-10">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Mobile Filter Bottom Sheet / Drawer --}}
    <div x-show="mobileFiltersOpen"
         x-cloak
         class="fixed inset-0 z-50 lg:hidden overflow-hidden"
         aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        {{-- Background Backdrop --}}
        <div x-show="mobileFiltersOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileFiltersOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 end-0 max-w-full flex">
            <div x-show="mobileFiltersOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-sm bg-white shadow-2xl flex flex-col justify-between">
                {{-- Header --}}
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                        <h2 class="text-sm font-black text-[#010619]" id="slide-over-title">فیلترهای فروشگاه</h2>
                    </div>
                    <button type="button" @click="mobileFiltersOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Form Content --}}
                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    <form method="GET" action="{{ route('catalog.products.index') }}" id="mobileFilterForm" class="space-y-6">
                        @if($sort !== 'newest')
                            <input type="hidden" name="sort" value="{{ $sort }}">
                        @endif

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">جستجو در نتایج</label>
                            <input type="search" name="search" value="{{ $search }}" placeholder="نام یا ویژگی..."
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400">
                        </div>

                        @if($categories->isNotEmpty())
                            <div>
                                <h3 class="mb-2 text-xs font-bold text-slate-700">دسته‌بندی</h3>
                                <div class="space-y-1.5">
                                    <label class="flex items-center gap-2 text-xs text-slate-700">
                                        <input type="radio" name="category" value="" @checked(!$selectedCategory) class="text-[#010619] focus:ring-[#ffde5b]">
                                        <span>همه دسته‌ها</span>
                                    </label>
                                    @foreach($categories as $category)
                                        <label class="flex items-center gap-2 text-xs text-slate-700">
                                            <input type="radio" name="category" value="{{ $category->slug }}" @checked($selectedCategory === $category->slug) class="text-[#010619] focus:ring-[#ffde5b]">
                                            <span>{{ $category->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">نوع محصول</h3>
                            <div class="space-y-1.5">
                                <label class="flex items-center gap-2 text-xs text-slate-700">
                                    <input type="radio" name="type" value="" @checked(!$selectedType) class="text-[#010619] focus:ring-[#ffde5b]">
                                    <span>همه انواع</span>
                                </label>
                                @foreach($types as $typeOption)
                                    <label class="flex items-center gap-2 text-xs text-slate-700">
                                        <input type="radio" name="type" value="{{ $typeOption->value }}" @checked($selectedType === $typeOption->value) class="text-[#010619] focus:ring-[#ffde5b]">
                                        <span>{{ $typeOption->faLabel() }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">رنگ‌های فلز</h3>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($colors as $color)
                                    @php $isColorSelected = (int)$selectedColorId === (int)$color->id; @endphp
                                    <label class="flex flex-col items-center gap-1 p-2 rounded-xl border {{ $isColorSelected ? 'border-[#010619] bg-slate-100 ring-2 ring-[#ffde5b]' : 'border-slate-200' }} text-center">
                                        <input type="radio" name="color_id" value="{{ $color->id }}" @checked($isColorSelected) class="sr-only">
                                        <span class="inline-block h-4 w-4 rounded-full border border-black/15 shadow-xs" style="background-color: {{ $color->code_hex }}"></span>
                                        <span class="text-[10px] text-slate-700 truncate w-full">{{ $color->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">بازه قیمت (تومان)</h3>
                            <div class="space-y-2">
                                <input type="number" name="min_price" value="{{ $minPrice ?: '' }}" placeholder="از قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                                <input type="number" name="max_price" value="{{ $maxPrice ?: '' }}" placeholder="تا قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs">
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Bottom Drawer Footer Buttons --}}
                <div class="p-5 border-t border-slate-100 flex gap-3 bg-slate-50">
                    <button type="button"
                            onclick="document.getElementById('mobileFilterForm').submit()"
                            class="flex-1 btn-brand-primary py-3 text-xs font-bold shadow-md shadow-[#ffde5b]/25 text-center">
                        اعمال فیلترها
                    </button>
                    <a href="{{ route('catalog.products.index') }}" wire:navigate
                       class="px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-100 text-center">
                        پاک کردن
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection