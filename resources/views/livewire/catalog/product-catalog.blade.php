<div class="min-h-screen bg-slate-50/50 py-8 sm:py-12" x-data="{ viewMode: @entangle('viewMode').live, mobileFiltersOpen: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-breadcrumbs :items="[
            ['label' => 'فروشگاه محصولات']
        ]" />

        {{-- Commerce Header & Brand Banner --}}
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
                            {{ $totalActiveProducts }} محصول موجود
                        </span>
                        @if($selectedCategory)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b] text-[#010619] shadow-xs">
                                <span>دسته: {{ $selectedCategory->name }}</span>
                            </span>
                        @endif
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
                    <span>فیلترها و دسته‌بندی</span>
                    @if($activeFiltersCount > 0)
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#ffde5b] text-[10px] font-black text-[#010619]">
                            {{ $activeFiltersCount }}
                        </span>
                    @endif
                </button>

                <span class="text-xs font-bold text-slate-500 hidden sm:inline">
                    نمایش {{ $products->count() }} از {{ $products->total() }} محصول
                </span>
            </div>

            {{-- Right: Sort & Layout Toggle --}}
            <div class="flex flex-wrap items-center gap-3 ms-auto">
                {{-- Livewire Sort Dropdown --}}
                <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                    <span class="hidden sm:inline">مرتب‌سازی:</span>
                    <select wire:model.live="sort"
                            class="rounded-xl border border-slate-200 bg-slate-50/70 py-2 ps-3.5 pe-8 text-xs font-bold text-slate-800 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b] cursor-pointer">
                        <option value="newest">جدیدترین‌ها</option>
                        <option value="cheapest">ارزان‌ترین‌ها</option>
                        <option value="expensive">گران‌ترین‌ها</option>
                        <option value="popular">محبوب‌ترین‌ها</option>
                    </select>
                </div>

                {{-- View Toggle Buttons (Grid vs Compact) --}}
                <div class="flex items-center rounded-xl bg-slate-100 p-1 border border-slate-200">
                    <button type="button"
                            wire:click="setViewMode('grid')"
                            :class="viewMode === 'grid' ? 'bg-white text-[#010619] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            class="p-1.5 rounded-lg transition cursor-pointer"
                            title="نمایش شبکه‌ای">
                        <x-icons.grid class="w-4 h-4" />
                    </button>
                    <button type="button"
                            wire:click="setViewMode('compact')"
                            :class="viewMode === 'compact' ? 'bg-white text-[#010619] shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                            class="p-1.5 rounded-lg transition cursor-pointer"
                            title="نمایش فشرده">
                        <x-icons.list class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Active Filters Badges Strip (Part 3) --}}
        @if($activeFiltersCount > 0)
            <div class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl bg-[#010619] px-4 py-3 text-xs text-slate-200 shadow-md border border-[#152244]">
                <span class="font-bold text-[#ffde5b] flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                    <span>فیلترهای فعال:</span>
                </span>

                @if(trim($search) !== '')
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs text-slate-100 border border-white/10">
                        <span>جستجو: «{{ $search }}»</span>
                        <button type="button" wire:click="clearSearch" class="text-slate-400 hover:text-rose-400 transition" title="حذف فیلتر">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if($selectedCategory)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs text-slate-100 border border-white/10">
                        <span>دسته‌بندی: {{ $selectedCategory->name }}</span>
                        <button type="button" wire:click="clearCategory" class="text-slate-400 hover:text-rose-400 transition" title="حذف فیلتر">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if($selectedColor)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs text-slate-100 border border-white/10">
                        <span class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: {{ $selectedColor->code_hex }}"></span>
                        <span>رنگ: {{ $selectedColor->name }}</span>
                        <button type="button" wire:click="clearColor" class="text-slate-400 hover:text-rose-400 transition" title="حذف فیلتر">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if($minPrice > 0 || $maxPrice > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs text-slate-100 border border-white/10">
                        <span>
                            @if($minPrice > 0 && $maxPrice > 0)
                                قیمت: {{ number_format($minPrice) }} تا {{ number_format($maxPrice) }} تومان
                            @elseif($minPrice > 0)
                                از {{ number_format($minPrice) }} تومان
                            @else
                                تا {{ number_format($maxPrice) }} تومان
                            @endif
                        </span>
                        <button type="button" wire:click="clearPrice" class="text-slate-400 hover:text-rose-400 transition" title="حذف فیلتر">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                @if($sort !== 'newest')
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs text-slate-100 border border-white/10">
                        <span>مرتب‌سازی: {{ match($sort) { 'cheapest' => 'ارزان‌ترین‌ها', 'expensive' => 'گران‌ترین‌ها', 'popular' => 'محبوب‌ترین‌ها', default => 'جدیدترین‌ها' } }}</span>
                        <button type="button" wire:click="clearSort" class="text-slate-400 hover:text-rose-400 transition" title="حذف فیلتر">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                @endif

                <button type="button" wire:click="clearAllFilters" class="ms-auto text-xs font-bold text-[#ffde5b] hover:underline cursor-pointer">
                    حذف همه فیلترها
                </button>
            </div>
        @endif

        {{-- Main Layout: Desktop Sidebar + Product Grid --}}
        <div class="grid gap-8 lg:grid-cols-[280px_1fr] items-start">
            {{-- Desktop Filters Sidebar (Part 1 & 2) --}}
            <aside class="hidden lg:block space-y-6">
                <div class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm sticky top-24">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                        <h2 class="text-sm font-black text-[#010619] flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                            <span>فیلتر محصولات</span>
                        </h2>
                        @if($activeFiltersCount > 0)
                            <button type="button" wire:click="clearAllFilters" class="text-xs font-bold text-rose-600 hover:underline cursor-pointer">
                                پاک کردن همه
                            </button>
                        @endif
                    </div>

                    <div class="space-y-6">
                        {{-- Search box --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">جستجو در فروشگاه</label>
                            <div class="relative">
                                <input type="search"
                                       wire:model.live.debounce.350ms="search"
                                       placeholder="نام یا ویژگی محصول..."
                                       class="w-full rounded-xl border border-slate-200 ps-9 pe-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                                <x-icons.search class="w-4 h-4 text-slate-400 absolute start-3 top-2.5 pointer-events-none" />
                            </div>
                        </div>

                        {{-- Dynamic Category filter (Part 1) --}}
                        @if($categories->isNotEmpty())
                            <div>
                                <h3 class="mb-2 text-xs font-bold text-slate-700 flex items-center justify-between">
                                    <span>دسته‌بندی‌ها</span>
                                    <span class="text-[10px] text-slate-400">CMS</span>
                                </h3>
                                <div class="space-y-1">
                                    <button type="button"
                                            wire:click="selectCategory('')"
                                            class="w-full flex items-center justify-between text-xs font-semibold select-none p-2 rounded-xl transition cursor-pointer {{ empty($category) ? 'bg-[#010619] text-[#ffde5b] shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full {{ empty($category) ? 'bg-[#ffde5b]' : 'bg-slate-300' }}"></span>
                                            <span>همه دسته‌بندی‌ها</span>
                                        </div>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ empty($category) ? 'bg-white/10 text-slate-200' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $totalActiveProducts }}
                                        </span>
                                    </button>
                                    @foreach($categories as $cat)
                                        @php $isActiveCat = $category === $cat->slug; @endphp
                                        <button type="button"
                                                wire:click="selectCategory('{{ $cat->slug }}')"
                                                class="w-full flex items-center justify-between text-xs font-semibold select-none p-2 rounded-xl transition cursor-pointer {{ $isActiveCat ? 'bg-[#010619] text-[#ffde5b] shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                            <div class="flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $isActiveCat ? 'bg-[#ffde5b]' : 'bg-slate-300' }}"></span>
                                                <span>{{ $cat->name }}</span>
                                            </div>
                                            @if(isset($cat->active_products_count))
                                                <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isActiveCat ? 'bg-white/10 text-slate-200' : 'bg-slate-100 text-slate-500' }}">
                                                    {{ $cat->active_products_count }}
                                                </span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Color Swatches Filter --}}
                        @if($colors->isNotEmpty())
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-xs font-bold text-slate-700">رنگ فلز</h3>
                                    @if($colorId)
                                        <button type="button" wire:click="clearColor" class="text-[11px] font-bold text-rose-500 hover:underline">
                                            حذف
                                        </button>
                                    @endif
                                </div>
                                <div class="grid grid-cols-4 gap-2">
                                    @foreach($colors as $color)
                                        @php $isColorSelected = (int)$colorId === (int)$color->id; @endphp
                                        <button type="button"
                                                wire:click="selectColor({{ $color->id }})"
                                                class="flex flex-col items-center gap-1.5 p-2 rounded-xl border transition cursor-pointer {{ $isColorSelected ? 'border-[#010619] bg-slate-100 ring-2 ring-[#ffde5b]' : 'border-slate-200 hover:border-slate-300' }}"
                                                title="{{ $color->name }}">
                                            <span class="inline-block h-5 w-5 rounded-full border border-black/15 shadow-xs relative" style="background-color: {{ $color->code_hex }}">
                                                @if($isColorSelected)
                                                    <span class="absolute inset-0 flex items-center justify-center text-white text-[10px] font-black drop-shadow">✓</span>
                                                @endif
                                            </span>
                                            <span class="text-[10px] text-slate-700 truncate w-full text-center">{{ $color->name }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Price range --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold text-slate-700">بازه قیمت (تومان)</h3>
                                @if($minPrice || $maxPrice)
                                    <button type="button" wire:click="clearPrice" class="text-[11px] font-bold text-rose-500 hover:underline">
                                        حذف
                                    </button>
                                @endif
                            </div>
                            <div class="space-y-2">
                                <input type="number"
                                       wire:model.live.debounce.400ms="minPrice"
                                       placeholder="از قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                                <input type="number"
                                       wire:model.live.debounce.400ms="maxPrice"
                                       placeholder="تا قیمت..." min="0" step="10000"
                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Products Container with Livewire Reactive States (Part 2 & 3) --}}
            <div class="relative min-h-[400px]">
                {{-- Livewire Loading Overlay --}}
                <div wire:loading.delay.shorter class="absolute inset-0 bg-white/70 backdrop-blur-[1px] z-30 flex items-center justify-center rounded-3xl transition-opacity">
                    <div class="inline-flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#010619] text-[#ffde5b] text-xs font-bold shadow-2xl border border-[#152244]">
                        <svg class="animate-spin h-4 w-4 text-[#ffde5b]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>در حال به‌روزرسانی محصولات...</span>
                    </div>
                </div>

                {{-- Products Grid / List --}}
                <div wire:loading.delay.shorter.class="opacity-50 pointer-events-none" class="transition-opacity duration-200">
                    <div :class="viewMode === 'grid' ? 'grid gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3' : 'space-y-4'">
                        @forelse($products as $product)
                            @include('catalog.partials.product-card', ['product' => $product])
                        @empty
                            {{-- Premium Empty Results State (Part 3) --}}
                            <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] mb-4 shadow-sm">
                                    <x-icons.search class="h-8 w-8" />
                                </div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900 mb-1">
                                    محصولی با این فیلتر پیدا نشد
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-500 mb-6 max-w-md mx-auto">
                                    می‌توانید فیلترهای انتخابی را حذف کرده یا عبارت جستجوی دیگری را وارد نمایید.
                                </p>
                                <div class="flex flex-wrap items-center justify-center gap-3">
                                    <button type="button"
                                            wire:click="clearAllFilters"
                                            class="inline-flex items-center gap-2 rounded-xl bg-[#ffde5b] px-6 py-2.5 text-xs sm:text-sm font-bold text-[#010619] shadow-sm shadow-[#ffde5b]/25 hover:bg-[#f5d347] transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>حذف همه فیلترها</span>
                                    </button>
                                    <button type="button"
                                            wire:click="clearAllFilters"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                                        <span>مشاهده همه محصولات</span>
                                    </button>
                                </div>
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
    </div>

    {{-- Mobile Filter Bottom Sheet / Drawer (Part 3) --}}
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
                    <button type="button" @click="mobileFiltersOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 bg-slate-100 cursor-pointer" aria-label="بستن فیلترها">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Content --}}
                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    {{-- Search --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700">جستجو در نتایج</label>
                        <input type="search"
                               wire:model.live.debounce.350ms="search"
                               placeholder="نام یا ویژگی..."
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-1 focus:ring-[#ffde5b]">
                    </div>

                    {{-- Dynamic Categories (Part 1) --}}
                    @if($categories->isNotEmpty())
                        <div>
                            <h3 class="mb-2 text-xs font-bold text-slate-700">دسته‌بندی</h3>
                            <div class="space-y-1">
                                <button type="button"
                                        wire:click="selectCategory('')"
                                        class="w-full flex items-center justify-between text-xs font-semibold select-none p-2 rounded-xl transition cursor-pointer {{ empty($category) ? 'bg-[#010619] text-[#ffde5b] shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full {{ empty($category) ? 'bg-[#ffde5b]' : 'bg-slate-300' }}"></span>
                                        <span>همه دسته‌ها</span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full {{ empty($category) ? 'bg-white/10 text-slate-200' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $totalActiveProducts }}
                                    </span>
                                </button>
                                @foreach($categories as $cat)
                                    @php $isActiveCat = $category === $cat->slug; @endphp
                                    <button type="button"
                                            wire:click="selectCategory('{{ $cat->slug }}')"
                                            class="w-full flex items-center justify-between text-xs font-semibold select-none p-2 rounded-xl transition cursor-pointer {{ $isActiveCat ? 'bg-[#010619] text-[#ffde5b] shadow-xs' : 'text-slate-700 hover:bg-slate-100' }}">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isActiveCat ? 'bg-[#ffde5b]' : 'bg-slate-300' }}"></span>
                                            <span>{{ $cat->name }}</span>
                                        </div>
                                        @if(isset($cat->active_products_count))
                                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isActiveCat ? 'bg-white/10 text-slate-200' : 'bg-slate-100 text-slate-500' }}">
                                                {{ $cat->active_products_count }}
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Colors --}}
                    @if($colors->isNotEmpty())
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-bold text-slate-700">رنگ‌های فلز</h3>
                                @if($colorId)
                                    <button type="button" wire:click="clearColor" class="text-[11px] font-bold text-rose-500 hover:underline">
                                        حذف
                                    </button>
                                @endif
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($colors as $color)
                                    @php $isColorSelected = (int)$colorId === (int)$color->id; @endphp
                                    <button type="button"
                                            wire:click="selectColor({{ $color->id }})"
                                            class="flex flex-col items-center gap-1.5 p-2 rounded-xl border transition cursor-pointer {{ $isColorSelected ? 'border-[#010619] bg-slate-100 ring-2 ring-[#ffde5b]' : 'border-slate-200' }}"
                                            title="{{ $color->name }}">
                                        <span class="inline-block h-5 w-5 rounded-full border border-black/15 shadow-xs relative" style="background-color: {{ $color->code_hex }}">
                                            @if($isColorSelected)
                                                <span class="absolute inset-0 flex items-center justify-center text-white text-[10px] font-black drop-shadow">✓</span>
                                            @endif
                                        </span>
                                        <span class="text-[10px] text-slate-700 truncate w-full text-center">{{ $color->name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Price range --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700">بازه قیمت (تومان)</h3>
                            @if($minPrice || $maxPrice)
                                <button type="button" wire:click="clearPrice" class="text-[11px] font-bold text-rose-500 hover:underline">
                                    حذف
                                </button>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <input type="number"
                                   wire:model.live.debounce.400ms="minPrice"
                                   placeholder="از قیمت..." min="0" step="10000"
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs">
                            <input type="number"
                                   wire:model.live.debounce.400ms="maxPrice"
                                   placeholder="تا قیمت..." min="0" step="10000"
                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs">
                        </div>
                    </div>
                </div>

                {{-- Bottom Drawer Footer Buttons --}}
                <div class="p-5 border-t border-slate-100 flex gap-3 bg-slate-50">
                    <button type="button"
                            @click="mobileFiltersOpen = false"
                            class="flex-1 btn-brand-primary py-3 text-xs font-bold shadow-md shadow-[#ffde5b]/25 text-center cursor-pointer">
                        مشاهده {{ $products->total() }} محصول
                    </button>
                    @if($activeFiltersCount > 0)
                        <button type="button"
                                wire:click="clearAllFilters"
                                class="px-4 py-3 rounded-xl border border-slate-200 bg-white text-xs font-bold text-rose-600 hover:bg-slate-100 text-center cursor-pointer">
                            حذف فیلترها
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
