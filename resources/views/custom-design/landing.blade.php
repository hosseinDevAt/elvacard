@extends('layouts.app')

@section('title')طراحی کارت اختصاصی - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    <meta name="description" content="کارت شخصی خود را با طرح دلخواه‌تان بسازید؛ نوع کارت را انتخاب کنید و وارد فرآیند طراحی اختصاصی شوید.">
    <link rel="canonical" href="{{ route('custom-card.design') }}">
@endsection

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-[#010619] py-16 sm:py-24 text-white">
        {{-- Background Gradients & Glows --}}
        <div class="pointer-events-none absolute -top-24 right-1/4 h-96 w-96 rounded-full bg-[#ffde5b]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/4 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl"></div>

        <div class="relative z-10 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex justify-center sm:justify-start mb-6">
                <x-breadcrumbs :dark="true" :items="[
                    ['label' => 'طراحی کارت اختصاصی']
                ]" />
            </div>

            <span class="inline-flex items-center gap-2 rounded-full border border-[#ffde5b]/40 bg-[#ffde5b]/10 px-4 py-1.5 text-xs font-bold text-[#ffde5b] mb-6">
                <span class="h-2 w-2 rounded-full bg-[#ffde5b] animate-pulse"></span>
                <span>استودیوی ساخت کارت فلزی شخصی‌سازی‌شده</span>
            </span>

            <h1 class="text-3xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl leading-tight">
                طراحی کارت اختصاصی
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-sm sm:text-base leading-relaxed text-slate-300">
                کارت شخصی خود را با طرح دلخواه‌تان بسازید؛ نوع کارت را انتخاب کنید و رنگ، طرح و متن را تعیین کنید.
            </p>
        </div>
    </section>

    {{-- Cards Chooser Grid --}}
    <section class="bg-slate-50/60 py-12 sm:py-20">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                {{-- Bank Card Option --}}
                <a href="{{ route('custom-card.bank') }}" wire:navigate
                   class="group relative store-card overflow-hidden p-8 transition-all duration-300 hover:-translate-y-1 hover:border-[#ffde5b]">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-md shadow-[#010619]/15 group-hover:scale-105 transition">
                            <x-icons.card class="h-7 w-7" />
                        </div>
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 border border-amber-200/60">محبوب‌ترین</span>
                    </div>

                    <h2 class="text-xl font-black text-[#010619] group-hover:text-slate-900 transition">طراحی کارت بانکی</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">
                        برای کارت بانکی خود طرح اختصاصی بسازید؛ رنگ، طرح و متن دلخواه را انتخاب کنید و سفارش دهید.
                    </p>

                    <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="inline-flex items-center gap-2 text-sm font-bold text-[#010619] group-hover:text-[#664d00] transition">
                            <span>شروع طراحی</span>
                            <x-icons.arrow-left class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                        </span>
                        <span class="text-xs text-slate-400 font-medium">حکاکی دوطرفه</span>
                    </div>
                </a>

                {{-- Fuel Card Option --}}
                <a href="{{ route('custom-card.fuel') }}" wire:navigate
                   class="group relative store-card overflow-hidden p-8 transition-all duration-300 hover:-translate-y-1 hover:border-[#ffde5b]">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-md shadow-[#010619]/15 group-hover:scale-105 transition">
                            <x-icons.grid class="h-7 w-7" />
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 border border-slate-200">کارت سوخت هوشمند</span>
                    </div>

                    <h2 class="text-xl font-black text-[#010619] group-hover:text-slate-900 transition">طراحی کارت سوخت</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">
                        مشخصات خودرو، شماره شاسی و سامانه سوخت خود را وارد کنید و کارت سوخت اختصاصی سفارش دهید.
                    </p>

                    <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="inline-flex items-center gap-2 text-sm font-bold text-[#010619] group-hover:text-[#664d00] transition">
                            <span>شروع طراحی</span>
                            <x-icons.arrow-left class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                        </span>
                        <span class="text-xs text-slate-400 font-medium">جایگذاری چیپست</span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    {{-- Order Steps Section --}}
    <section class="bg-white py-14 sm:py-20 border-t border-slate-100">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl font-black text-[#010619] sm:text-3xl">مراحل سفارش</h2>
                <p class="mt-2 text-sm text-slate-500">طراحی و سفارش کارت اختصاصی شما فقط چند قدم ساده دارد.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="rounded-3xl border border-slate-100 bg-slate-50/60 p-7 text-center transition hover:bg-slate-50">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#ffde5b] text-base font-black text-[#010619] shadow-sm">۱</span>
                    <h3 class="mt-4 text-base font-bold text-[#010619]">انتخاب نوع کارت</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">کارت بانکی یا سوخت خود را انتخاب کنید.</p>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-slate-50/60 p-7 text-center transition hover:bg-slate-50">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#ffde5b] text-base font-black text-[#010619] shadow-sm">۲</span>
                    <h3 class="mt-4 text-base font-bold text-[#010619]">انتخاب طرح و رنگ</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">رنگ، طرح و متن دلخواه خود را روی کارت تعیین کنید.</p>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-slate-50/60 p-7 text-center transition hover:bg-slate-50">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#ffde5b] text-base font-black text-[#010619] shadow-sm">۳</span>
                    <h3 class="mt-4 text-base font-bold text-[#010619]">تحویل سفارش</h3>
                    <p class="mt-2 text-xs leading-relaxed text-slate-500">سفارش شما با دقت تولید و در سریع‌ترین زمان ارسال می‌شود.</p>
                </div>
            </div>
        </div>
    </section>
@endsection