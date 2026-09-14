@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">فروشگاه</h1>
            <p class="mt-2 text-sm text-gray-600 sm:text-base">محصولات فعال و رنگ‌های موجود را مشاهده کنید.</p>
        </header>

        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
            {{-- Filters Sidebar --}}
            <aside class="lg:block" x-data="{ open: false }">
                <div class="lg:hidden mb-4">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:border-gray-300">
                        <span>فیلتر محصولات</span>
                        <x-icons.chevron-down class="h-4 w-4 transition" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                </div>

                <div x-show="open" x-collapse class="lg:block rounded-xl border border-gray-200 bg-white p-5 lg:border-0 lg:bg-transparent lg:p-0 lg:x-cloak">
                    <form method="GET" action="{{ route('catalog.products.index') }}" class="space-y-6">
                        @if($search)
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif

                        {{-- Search box (kept when filtering from sidebar) --}}
                        <div class="lg:hidden">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">جستجو</label>
                            <input type="search" name="search" value="{{ $search }}" placeholder="جستجو در محصولات..."
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200">
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">مرتب‌سازی</label>
                            <select name="sort" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200">
                                <option value="newest" @selected($sort === 'newest')>جدیدترین</option>
                                <option value="cheapest" @selected($sort === 'cheapest')>ارزان‌ترین</option>
                                <option value="expensive" @selected($sort === 'expensive')>گران‌ترین</option>
                                <option value="popular" @selected($sort === 'popular')>محبوب‌ترین</option>
                            </select>
                        </div>

                        {{-- Type filter --}}
                        <div>
                            <h3 class="mb-1.5 text-sm font-medium text-gray-700">نوع کارت</h3>
                            <div class="space-y-2">
                                @foreach($types as $typeOption)
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                        <input type="radio" name="type" value="{{ $typeOption->value }}"
                                               @checked($selectedType === $typeOption->value)
                                               class="rounded-full border-gray-300 text-primary-600 focus:ring-primary-200">
                                        {{ $typeOption->faLabel() }}
                                    </label>
                                @endforeach
                            </div>
                            @if($selectedType)
                                <a href="{{ route('catalog.products.index', array_filter(['search' => $search ?: null, 'sort' => $sort])) }}" wire:navigate
                                   class="mt-2 inline-block text-xs text-primary-600 underline">حذف فیلتر نوع</a>
                            @endif
                        </div>

                        {{-- Color filter --}}
                        <div>
                            <h3 class="mb-1.5 text-sm font-medium text-gray-700">رنگ موجود</h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @forelse($colors as $color)
                                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                        <input type="radio" name="color_id" value="{{ $color->id }}"
                                               @checked($selectedColorId === $color->id)
                                               class="rounded-full border-gray-300 text-primary-600 focus:ring-primary-200">
                                        <span class="inline-block h-3 w-3 rounded-full border border-gray-200" style="background-color: {{ $color->code_hex }}"></span>
                                        {{ $color->name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">رنگی ثبت نشده است</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- Price range --}}
                        <div>
                            <h3 class="mb-1.5 text-sm font-medium text-gray-700">بازه قیمت (تومان)</h3>
                            <div class="space-y-2">
                                <input type="number" name="min_price" value="{{ $minPrice ?: '' }}" placeholder="از" min="0" step="10000"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200">
                                <input type="number" name="max_price" value="{{ $maxPrice ?: '' }}" placeholder="تا" min="0" step="10000"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200">
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-primary-700">
                                اعمال فیلتر
                            </button>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate
                               class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:border-gray-400">
                                پاک کردن
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Product grid --}}
            <div>
                {{-- Active filters summary --}}
                @if($search || $selectedType || $selectedColorId || $minPrice || $maxPrice || $sort !== 'newest')
                    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg bg-primary-50 px-3 py-2.5 text-sm text-primary-700">
                        <span>نتایج فیلتر شده:</span>
                        @if($search)
                            <span class="rounded-full bg-white px-2.5 py-0.5">جستجو: «{{ $search }}»</span>
                        @endif
                        @if($selectedType)
                            <span class="rounded-full bg-white px-2.5 py-0.5">نوع: {{ $selectedType }}</span>
                        @endif
                        @if($selectedColorId)
                            @php $colorName = $colors->firstWhere('id', $selectedColorId)?->name; @endphp
                            @if($colorName)
                                <span class="rounded-full bg-white px-2.5 py-0.5">رنگ: {{ $colorName }}</span>
                            @endif
                        @endif
                        @if($minPrice)
                            <span class="rounded-full bg-white px-2.5 py-0.5">از {{ number_format($minPrice) }} تومان</span>
                        @endif
                        @if($maxPrice)
                            <span class="rounded-full bg-white px-2.5 py-0.5">تا {{ number_format($maxPrice) }} تومان</span>
                        @endif
                        <a href="{{ route('catalog.products.index') }}" wire:navigate class="ms-auto underline hover:text-primary-800">پاک کردن همه</a>
                    </div>
                @endif

                @if($search)
                    <p class="mb-4 text-sm text-gray-600">
                        {{ $products->total() }} نتیجه برای «{{ $search }}»
                    </p>
                @endif

                <div class="grid gap-5 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse($products as $product)
                        @include('catalog.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
                            <p class="text-sm text-gray-500 mb-4">محصولی مطابق با فیلترهای شما یافت نشد.</p>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate
                               class="inline-flex rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
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