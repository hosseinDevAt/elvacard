@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-breadcrumbs :items="[
            ['label' => 'فروشگاه محصولات']
        ]" />

        <header class="mb-8">
            <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl tracking-tight">فروشگاه محصولات</h1>
            <p class="mt-2 text-sm text-slate-500 sm:text-base">محصولات فیزیکی، اکسسوری‌ها و کارت‌های استاندارد موجود را بررسی و انتخاب کنید.</p>
        </header>

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
            {{-- Filters Sidebar --}}
            <aside class="lg:block" x-data="{ open: false }">
                <div class="lg:hidden mb-4">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="w-full flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-sm transition hover:border-slate-300">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                            <span>فیلتر و مرتب‌سازی</span>
                        </span>
                        <x-icons.chevron-down class="h-4 w-4 transition-transform duration-200 text-slate-500" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                </div>

                <div x-show="open" x-collapse class="lg:block rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm lg:p-6 lg:x-cloak">
                    <form method="GET" action="{{ route('catalog.products.index') }}" class="space-y-6">
                        @if($search)
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif

                        {{-- Search box on mobile --}}
                        <div class="lg:hidden">
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">جستجو در نتایج</label>
                            <input type="search" name="search" value="{{ $search }}" placeholder="نام محصول..."
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60">
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">مرتب‌سازی</label>
                            <select name="sort" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition">
                                <option value="newest" @selected($sort === 'newest')>جدیدترین‌ها</option>
                                <option value="cheapest" @selected($sort === 'cheapest')>ارزان‌ترین‌ها</option>
                                <option value="expensive" @selected($sort === 'expensive')>گران‌ترین‌ها</option>
                                <option value="popular" @selected($sort === 'popular')>محبوب‌ترین‌ها</option>
                            </select>
                        </div>

                        {{-- Type filter --}}
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">نوع محصول</h3>
                            <div class="space-y-2">
                                @foreach($types as $typeOption)
                                    <label class="flex items-center gap-2.5 text-xs sm:text-sm text-slate-700 cursor-pointer select-none">
                                        <input type="radio" name="type" value="{{ $typeOption->value }}"
                                               @checked($selectedType === $typeOption->value)
                                               class="h-4 w-4 border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                                        <span>{{ $typeOption->faLabel() }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if($selectedType)
                                <a href="{{ route('catalog.products.index', array_filter(['search' => $search ?: null, 'sort' => $sort])) }}" wire:navigate
                                   class="mt-2.5 inline-block text-xs font-bold text-rose-600 hover:underline">حذف فیلتر نوع</a>
                            @endif
                        </div>

                        {{-- Category filter --}}
                        @if($categories->isNotEmpty())
                            <div>
                                <h3 class="mb-1.5 text-xs font-bold text-slate-700">دسته‌بندی</h3>
                                <select name="category" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition">
                                    <option value="">همه دسته‌بندی‌ها</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Color filter --}}
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">رنگ‌های موجود</h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto ps-1 pe-1">
                                @forelse($colors as $color)
                                    <label class="flex items-center gap-2.5 text-xs sm:text-sm text-slate-700 cursor-pointer select-none">
                                        <input type="radio" name="color_id" value="{{ $color->id }}"
                                               @checked($selectedColorId === $color->id)
                                               class="h-4 w-4 border-slate-300 text-[#010619] focus:ring-[#ffde5b]">
                                        <span class="inline-block h-3.5 w-3.5 rounded-full border border-black/15 shadow-xs" style="background-color: {{ $color->code_hex }}"></span>
                                        <span>{{ $color->name }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-slate-400">رنگی ثبت نشده است</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Price range --}}
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">بازه قیمت (تومان)</h3>
                            <div class="space-y-2">
                                <input type="number" name="min_price" value="{{ $minPrice ?: '' }}" placeholder="از قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60">
                                <input type="number" name="max_price" value="{{ $maxPrice ?: '' }}" placeholder="تا قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60">
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 rounded-xl bg-[#ffde5b] px-4 py-2.5 text-xs sm:text-sm font-bold text-[#010619] shadow-sm shadow-[#ffde5b]/25 hover:bg-[#f5d347] transition">
                                اعمال فیلتر
                            </button>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate
                               class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                                پاک کردن
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Product grid --}}
            <div>
                {{-- Active filters summary --}}
                @if($search || $selectedType || $selectedCategory || $selectedColorId || $minPrice || $maxPrice || $sort !== 'newest')
                    <div class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl bg-[#010619] px-4 py-3 text-xs sm:text-sm text-slate-200 shadow-md">
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
                        <a href="{{ route('catalog.products.index') }}" wire:navigate class="ms-auto text-xs font-bold text-[#ffde5b] hover:underline">پاک کردن همه</a>
                    </div>
                @endif

                @if($search)
                    <p class="mb-4 text-sm text-slate-600">
                        {{ $products->total() }} نتیجه برای «{{ $search }}»
                    </p>
                @endif

                <div class="grid gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($products as $product)
                        @include('catalog.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-4">
                                <x-icons.search class="h-6 w-6" />
                            </div>
                            <h3 class="text-base font-bold text-slate-800 mb-1">محصولی یافت نشد</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mb-5">محصولی مطابق با فیلترهای انتخابی شما در دسترس نیست.</p>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate
                               class="inline-flex rounded-xl bg-[#ffde5b] px-5 py-2.5 text-xs sm:text-sm font-bold text-[#010619] shadow-sm shadow-[#ffde5b]/25 hover:bg-[#f5d347] transition">
                                مشاهده همه محصولات
                            </a>
                        </div>
                    @endforelse
                </div>

                @if($products->hasPages())
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection