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
        {{-- Breadcrumbs & Back Navigation --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <x-breadcrumbs :items="array_filter([
                ['label' => 'فروشگاه محصولات', 'url' => route('catalog.products.index')],
                $product->category ? ['label' => $product->category->name, 'url' => route('catalog.products.index', ['category' => $product->category->slug])] : null,
                ['label' => $product->name]
            ])" />

            <a href="{{ route('catalog.products.index') }}" wire:navigate
               class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200/90 px-3.5 py-2 rounded-xl shadow-xs transition hover:border-slate-300 w-fit">
                <x-icons.arrow-right class="w-4 h-4" />
                <span>بازگشت به فروشگاه</span>
            </a>
        </div>

        {{-- Product Hero Surface --}}
        <div class="mb-12">
            @if($hasCustomization && $customizationAvailable)
                <livewire:catalog.product-customizer :productId="$product->id" />
            @elseif($hasCustomization)
                <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-8 text-center text-sm text-amber-800 shadow-xs">
                    <p class="font-bold">این محصول تا راه‌اندازی رسمی سرویس شخصی‌سازی هنوز قابل خرید نیست.</p>
                    <p class="mt-2 text-xs text-amber-700">به‌زودی امکان سفارش مستقیم این کالا فعال خواهد شد.</p>
                </div>
            @else
                @if($purchasable)
                    <livewire:catalog.product-gallery
                        :productId="$product->id"
                        :colorId="$selectedColorId > 0 ? $selectedColorId : null"
                    />
                @else
                    <div class="rounded-3xl border border-amber-200 bg-amber-50/80 p-8 text-center text-sm text-amber-800 shadow-xs">
                        <p class="font-bold">این محصول در حال حاضر قابل خرید نیست.</p>
                        <p class="mt-2 text-xs text-amber-700">می‌توانید سایر محصولات موجود در فروشگاه را بررسی نمایید.</p>
                    </div>
                @endif
            @endif
        </div>

        {{-- 2. Conversion Improvements: Trust & Benefits Section --}}
        <section class="mt-12 rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.sparkles class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">متریال ۳۱۶L لوکس</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">ضدزنگ، ضدخش و با دوام مادام‌العمر</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.box class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">بسته‌بندی هاردباکس</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">جعبه کادویی لوکس با فوم محافظ</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.check-badge class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">ارسال سریع اکسپرس</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">تحویل ۲۴ تا ۴۸ ساعته به سراسر ایران</p>
                    </div>
                </div>

                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-sm">
                        <x-icons.card class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-[#010619]">ضمانت اصالت و سلامت</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">۷ روز ضمانت تعویض و اصالت فیزیکی</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3. Product Information Architecture (Structured Tabs) --}}
        <section class="mt-12 rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm" x-data="{ activeTab: 'description' }">
            {{-- Tabs Header Bar --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-4 mb-6">
                <button type="button"
                        @click="activeTab = 'description'"
                        :class="activeTab === 'description' ? 'bg-[#010619] text-[#ffde5b] shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                    توضیحات و معرفی محصول
                </button>
                <button type="button"
                        @click="activeTab = 'specs'"
                        :class="activeTab === 'specs' ? 'bg-[#010619] text-[#ffde5b] shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                    مشخصات فنی و متریال
                </button>
                <button type="button"
                        @click="activeTab = 'shipping'"
                        :class="activeTab === 'shipping' ? 'bg-[#010619] text-[#ffde5b] shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                    شرایط ارسال و بسته‌بندی
                </button>
                <button type="button"
                        @click="activeTab = 'warranty'"
                        :class="activeTab === 'warranty' ? 'bg-[#010619] text-[#ffde5b] shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                    گارانتی و ضمانت بازگشت
                </button>
            </div>

            {{-- Tab 1: Description --}}
            <div x-show="activeTab === 'description'" x-cloak class="space-y-4">
                <div class="whitespace-pre-line leading-relaxed text-sm sm:text-base text-slate-700">
                    {{ $product->seo_content ?: ($product->description ?: 'این محصول با بالاترین استانداردهای کیفی برند الواکارت با متریال فلزی استیل ضدزنگ تولید شده است.') }}
                </div>

                <div class="mt-6 rounded-2xl bg-slate-50 border border-slate-200/80 p-5 flex flex-col sm:flex-row items-center gap-4">
                    <div class="h-10 w-10 shrink-0 rounded-xl bg-[#010619] text-[#ffde5b] flex items-center justify-center">
                        <x-icons.sparkles class="w-5 h-5" />
                    </div>
                    <div class="text-xs sm:text-sm text-slate-600 leading-relaxed text-center sm:text-start">
                        تمامی محصولات الواکارت قبل از ارسال از نظر سلامت فیزیکی، دقت ساخت و عدم وجود هرگونه خط و خش، بازبینی کنترل کیفی دوگانه (QC) می‌شوند.
                    </div>
                </div>
            </div>

            {{-- Tab 2: Specifications --}}
            <div x-show="activeTab === 'specs'" x-cloak class="space-y-4">
                <h3 class="text-sm font-black text-slate-900 mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                    <span>مشخصات فنی و ساختار محصول</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs sm:text-sm">
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500 font-medium">جنس بدنه و آلیاژ</span>
                        <span class="text-slate-900 font-bold">استیل ضدزنگ گرید ۳۱۶L / تیتانیوم</span>
                    </div>
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500 font-medium">نوع پرداخت و رنگ‌آمیزی</span>
                        <span class="text-slate-900 font-bold">آبکاری PVD مقاوم در برابر سایش</span>
                    </div>
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500 font-medium">مقاومت در برابر رطوبت و تعریق</span>
                        <span class="text-emerald-700 font-bold">۱۰۰٪ ضدآب و ضدزنگ</span>
                    </div>
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500 font-medium">دقت ساخت و پرداخت لبه‌ها</span>
                        <span class="text-slate-900 font-bold">برش لیزری با لبه‌های پخ‌خورده نرم</span>
                    </div>
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500 font-medium">دسته‌بندی محصول</span>
                        <span class="text-slate-900 font-bold">{{ $product->category?->name ?? 'اکسسوری الواکارت' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-500 font-medium">ضمانت ثبات رنگ</span>
                        <span class="text-slate-900 font-bold">تضمین دائمی ثبات رنگ الواکارت</span>
                    </div>
                </div>
            </div>

            {{-- Tab 3: Shipping & Delivery --}}
            <div x-show="activeTab === 'shipping'" x-cloak class="space-y-4">
                <h3 class="text-sm font-black text-slate-900 mb-2 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                    <span>نحوه پردازش و ارسال سفارش</span>
                </h3>

                <div class="space-y-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#010619] text-[#ffde5b] flex items-center justify-center shrink-0 mt-0.5">
                            <x-icons.box class="w-4 h-4" />
                        </div>
                        <div>
                            <strong class="text-slate-900 block mb-0.5">بسته‌بندی محافظتی هاردباکس:</strong>
                            تمامی سفارش‌ها درون هاردباکس اختصاصی الواکارت همراه با بالشتک فومی جاذب ضربه قرار داده می‌شوند تا در طول فرآیند حمل کوچک‌ترین آسیبی نبینند.
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#010619] text-[#ffde5b] flex items-center justify-center shrink-0 mt-0.5">
                            <x-icons.check-badge class="w-4 h-4" />
                        </div>
                        <div>
                            <strong class="text-slate-900 block mb-0.5">رهگیری لحظه‌ای مرسوله:</strong>
                            بلافاصله پس از تحویل مرسوله به شرکت پست یا تیپاکس، کد رهگیری ۲۴ رقمی پیامک شده و در بخش پیگیری سفارش وب‌سایت در دسترس خواهد بود.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab 4: Warranty & Support --}}
            <div x-show="activeTab === 'warranty'" x-cloak class="space-y-4">
                <h3 class="text-sm font-black text-slate-900 mb-2 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                    <span>شرایط ضمانت و خدمات پس از فروش</span>
                </h3>

                <div class="space-y-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <strong class="text-slate-900 block mb-1">۷ روز ضمانت بازگشت و تعویض:</strong>
                        در صورت مشاهده هرگونه مغایرت، خط و خش یا ایراد تولیدی، کالا بدون هیچ هزینه‌ای تعویض یا وجه آن مرجوع می‌گردد.
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <strong class="text-slate-900 block mb-1">پشتیبانی اختصاصی:</strong>
                        کارشناسان پشتیبانی الواکارت از طریق تماس و چت آنلاین در تمام روزهای کاری آماده پاسخگویی به سوالات شما هستند.
                    </div>
                </div>
            </div>
        </section>

        {{-- 4. Similar Products Section --}}
        @if (isset($similarProducts) && $similarProducts->isNotEmpty())
            <section class="mt-14" aria-label="محصولات مشابه">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-[#010619] tracking-tight">محصولات مشابه و پیشنهادی</h2>
                        <p class="text-xs text-slate-500 mt-1">سایر محصولات باکیفیت فروشگاه الواکارت که کاربران دیگر پسندیده‌اند</p>
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

        {{-- 5. Custom Design CTA Studio Banner --}}
        <section class="mt-14 rounded-3xl bg-[#010619] p-8 sm:p-12 text-white relative overflow-hidden border border-[#152244] shadow-2xl">
            <div class="pointer-events-none absolute -top-32 -left-32 h-80 w-80 rounded-full bg-[#ffde5b]/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="space-y-3 text-center lg:text-start max-w-xl">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/15 text-[#ffde5b] border border-[#ffde5b]/30">
                        <x-icons.sparkles class="w-3.5 h-3.5 text-[#ffde5b]" />
                        <span>استودیوی ساخت کارت شخصی‌سازی‌شده</span>
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">طراحی کارت اختصاصی خودتان</h2>
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

        {{-- 6. Mobile UX: Sticky Bottom Purchase Bar (Mobile only) --}}
        @if($purchasable && !($hasCustomization && $customizationAvailable))
            <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 shadow-2xl flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h4 class="text-xs font-bold text-slate-900 truncate">{{ $product->name }}</h4>
                    <span class="text-xs font-black text-[#010619]">
                        {{ number_format($product->colorPrices?->first()?->price ?? $product->base_price) }} تومان
                    </span>
                </div>

                <button type="button"
                        onclick="const f = document.getElementById('product-add-to-cart-form'); if(f) { f.scrollIntoView({behavior: 'smooth'}); f.requestSubmit(); }"
                        class="btn-brand-primary px-5 py-2.5 text-xs font-bold shadow-md shadow-[#ffde5b]/30 shrink-0 flex items-center gap-1.5 cursor-pointer">
                    <x-icons.cart class="w-4 h-4" />
                    <span>افزودن به سبد</span>
                </button>
            </div>
        @endif
    </div>
@endsection