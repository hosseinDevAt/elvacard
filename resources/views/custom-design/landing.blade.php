@extends('layouts.app')

@section('meta')
    <title>طراحی کارت اختصاصی - {{ site_setting('site_name', config('app.name')) }}</title>
    <meta name="description" content="کارت شخصی خود را با طرح دلخواه‌تان بسازید؛ نوع کارت را انتخاب کنید و وارد فرآیند طراحی اختصاصی شوید.">
    <link rel="canonical" href="{{ route('custom-card.design') }}">
@endsection

@section('content')
    <section class="relative overflow-hidden">
        <div class="relative flex min-h-[40vh] items-center justify-center overflow-hidden bg-gradient-to-b from-primary-900 via-primary-800 to-primary-600">
            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-20 lg:px-8">
                <h1 class="mb-4 text-3xl font-bold text-white sm:text-5xl lg:text-6xl leading-tight">
                    طراحی کارت اختصاصی
                </h1>
                <p class="mx-auto max-w-3xl text-lg leading-relaxed text-white/90 sm:text-xl">
                    کارت شخصی خود را با طرح دلخواه‌تان بسازید؛ نوع کارت را انتخاب کنید و رنگ، طرح و متن را تعیین کنید.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-gray-50 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <a href="{{ route('custom-card.bank') }}" wire:navigate
                   class="group rounded-2xl border border-gray-100 bg-white p-8 transition hover:border-primary-200 hover:shadow-lg">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white">
                        <x-icons.card class="h-7 w-7" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">طراحی کارت بانکی</h2>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">
                        برای کارت بانکی خود طرح اختصاصی بسازید؛ رنگ، طرح و متن دلخواه را انتخاب کنید و سفارش دهید.
                    </p>
                    <span class="mt-5 inline-flex items-center text-sm font-semibold text-primary-600 group-hover:text-primary-700">
                        شروع طراحی
                        <x-icons.arrow-left class="me-0 ms-2 h-4 w-4" />
                    </span>
                </a>

                <a href="{{ route('custom-card.fuel') }}" wire:navigate
                   class="group rounded-2xl border border-gray-100 bg-white p-8 transition hover:border-primary-200 hover:shadow-lg">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white">
                        <x-icons.grid class="h-7 w-7" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">طراحی کارت سوخت</h2>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">
                        سرویس طراحی کارت سوخت اختصاصی در دست راه‌اندازی است و به‌زودی فعال می‌شود.
                    </p>
                    <span class="mt-5 inline-flex items-center text-sm font-semibold text-primary-600 group-hover:text-primary-700">
                        مشاهده جزئیات
                        <x-icons.arrow-left class="me-0 ms-2 h-4 w-4" />
                    </span>
                </a>
            </div>
        </div>
    </section>

    <section class="bg-white py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center text-2xl font-bold text-gray-900 sm:text-3xl">مراحل سفارش</h2>
            <p class="mx-auto mt-3 max-w-2xl text-center text-gray-600">طراحی و سفارش کارت اختصاصی شما فقط چند قدم ساده دارد.</p>

            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">۱</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">انتخاب نوع کارت</h3>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">کارت بانکی یا سوخت خود را انتخاب کنید.</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">۲</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">انتخاب طرح و رنگ</h3>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">رنگ، طرح و متن دلخواه خود را روی کارت تعیین کنید.</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">۳</span>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">تحویل سفارش</h3>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">سفارش شما با دقت تولید و در سریع‌ترین زمان ارسال می‌شود.</p>
                </div>
            </div>
        </div>
    </section>
@endsection