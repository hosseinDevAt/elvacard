@extends('layouts.app')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative flex min-h-[44vh] items-center justify-center overflow-hidden bg-gradient-to-b from-primary-900 via-primary-800 to-primary-600">
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-20 lg:px-8">
                <h1 class="mb-4 text-3xl font-bold text-white sm:text-5xl lg:text-6xl leading-tight">
                    طراحی کارت اختصاصی
                </h1>
                <p class="mx-auto mb-8 max-w-3xl text-lg leading-relaxed text-white/90 sm:text-xl">
                    کارت شخصی خود را با طرح دلخواهتان بسازید؛ از انتخاب رنگ تا حکاکی طرح بر روی کارت. همه‌چیز با کیفیت عالی و توسط ما انجام می‌شود.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ route('catalog.products.index') }}" wire:navigate
                       class="inline-flex items-center rounded-full bg-white px-6 py-3 text-lg font-semibold text-gray-900 shadow-lg transition hover:bg-gray-100 sm:px-8 sm:py-4">
                        انتخاب محصول
                    </a>
                    <a href="{{ route('catalog.designs.index') }}" wire:navigate
                       class="inline-flex items-center rounded-full border-2 border-white/40 px-6 py-3 text-lg font-semibold text-white transition hover:border-white hover:bg-white/10 sm:px-8 sm:py-4">
                        مشاهده طرح‌ها
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <section class="border-b border-gray-100 bg-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-3xl font-bold text-primary-600">{{ $totalDesigns }}</p>
                <p class="mt-1 text-sm text-gray-600">طرح آماده</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-primary-600">{{ $totalColors }}</p>
                <p class="mt-1 text-sm text-gray-600">رنگ متنوع</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-primary-600">۲ نوع</p>
                <p class="mt-1 text-sm text-gray-600">کارت بانکی و سوخت</p>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-gray-50 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-bold text-gray-900 sm:text-3xl">مراحل سفارش</h2>
            <p class="mx-auto mt-3 max-w-2xl text-center text-gray-600">طراحی و سفارش کارت اختصاصی شما فقط چند قدم ساده دارد.</p>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">۱</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">انتخاب محصول</h3>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">کارت بانکی یا سوخت خود را انتخاب و رنگ موردنظر را تعیین کنید.</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">۲</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">انتخاب طرح</h3>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">از میان طرح‌های آماده، طرح دلخواه‌تان را انتخاب یا آپلود کنید.</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">۳</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">تحویل سفارش</h3>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">سفارش شما با دقت تولید و در سریع‌ترین زمان ارسال می‌شود.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Categories --}}
    <section class="bg-white py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex items-end justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">دسته‌بندی طرح‌ها</h2>
                    <p class="mt-2 text-gray-600">بر اساس سلیقه و سبک مورد علاقه‌تان جستجو کنید.</p>
                </div>
            </div>

            @if($categories->isNotEmpty())
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($categories as $category)
                        <a href="{{ route('catalog.designs.index', ['category' => $category->slug]) }}"
                           class="group rounded-2xl border border-gray-100 bg-white p-6 transition hover:border-primary-200 hover:shadow-lg">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white">
                                <x-icons.grid class="h-6 w-6" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $category->name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ number_format($category->designs_count) }} طرح فعال</p>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-sm text-gray-500">
                    هنوز دسته‌بندی طرحی ثبت نشده است.
                </div>
            @endif
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-primary-700 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-white sm:text-3xl">آماده ساختن کارت اختصاصی خود هستید؟</h2>
            <p class="mx-auto mt-3 max-w-2xl text-white/85">همین حالا شروع کنید و کارت شخصی منحصربه‌فرد خود را بسازید.</p>
            <div class="mt-8">
                <a href="{{ route('catalog.products.index') }}"
                   class="inline-flex items-center rounded-full bg-white px-8 py-4 text-lg font-semibold text-primary-700 shadow-lg transition hover:bg-gray-100">
                    شروع طراحی
                    <x-icons.arrow-left class="me-0 ms-2 h-5 w-5" />
                </a>
            </div>
        </div>
    </section>
@endsection