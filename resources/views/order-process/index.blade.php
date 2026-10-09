@extends('layouts.app')

@section('title')راهنمای مراحل سفارش - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    <meta name="description" content="راهنمای کامل و گام‌به‌گام مراحل ثبت، تولید و تحویل کارت‌های بانکی فلزی و کارت سوخت در {{ site_setting('site_name', config('app.name')) }}">
@endsection

@section('content')
<main class="min-h-[75vh] bg-slate-50/60 py-10 sm:py-16">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumbs --}}
        <x-breadcrumbs :items="[
            ['label' => 'راهنمای مراحل سفارش']
        ]" />

        {{-- Page Header --}}
        <header class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-[#ffde5b]/20 text-[#010619] border border-[#ffde5b]/60 mb-3.5">
                <x-icons.sparkles class="w-3.5 h-3.5 text-[#010619]" />
                <span>شفافیت و امنیت فرآیند</span>
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-[#010619] tracking-tight leading-tight">
                مراحل ثبت، تولید و تحویل سفارش
            </h1>
            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                از انتخاب متریال و طراحی دلخواه تا ساخت صنعتی دقیق و تحویل امن درب منزل شما
            </p>
        </header>

        {{-- Interactive Tabs Container --}}
        <div x-data="{ activeTab: '{{ $activeWorkflow }}' }" class="space-y-8">
            {{-- Tabs Switcher --}}
            <div class="flex justify-center">
                <div class="inline-flex p-1.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs max-w-md w-full sm:w-auto">
                    <button type="button"
                            @click="activeTab = 'bank_card'"
                            :class="activeTab === 'bank_card'
                                ? 'bg-[#010619] text-[#ffde5b] shadow-sm font-bold'
                                : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm transition-all duration-200 cursor-pointer">
                        <x-icons.credit-card class="w-4 h-4 shrink-0" />
                        <span>کارت بانکی فلزی</span>
                    </button>

                    <button type="button"
                            @click="activeTab = 'fuel_card'"
                            :class="activeTab === 'fuel_card'
                                ? 'bg-[#010619] text-[#ffde5b] shadow-sm font-bold'
                                : 'text-slate-600 hover:text-slate-900 font-medium'"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm transition-all duration-200 cursor-pointer">
                        <x-icons.card class="w-4 h-4 shrink-0" />
                        <span>کارت سوخت</span>
                    </button>
                </div>
            </div>

            {{-- 1. Metallic Bank Card Workflow Tab --}}
            <div x-show="activeTab === 'bank_card'" x-cloak class="space-y-6">
                @php
                    $isBankPublished = $bankGuide && $bankGuide->is_published && $bankGuide->activeStages->isNotEmpty();
                @endphp

                @if($isBankPublished)
                    {{-- Guide Intro Card --}}
                    @if($bankGuide->description)
                        <div class="rounded-2xl bg-white border border-slate-200 p-6 sm:p-7 shadow-xs">
                            <h2 class="text-lg sm:text-xl font-extrabold text-[#010619] mb-2">
                                {{ $bankGuide->title }}
                            </h2>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                {!! nl2br(e($bankGuide->description)) !!}
                            </p>
                        </div>
                    @endif

                    {{-- Stages Timeline --}}
                    <div class="space-y-4">
                        @foreach($bankGuide->activeStages as $index => $stage)
                            <div class="rounded-2xl bg-white border border-slate-200/90 p-5 sm:p-6 shadow-xs hover:border-[#ffde5b]/60 transition-colors">
                                <div class="flex items-start gap-4 sm:gap-5">
                                    {{-- Step Number & Icon --}}
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-[#010619] text-[#ffde5b] flex items-center justify-center shadow-sm">
                                            @if($stage->icon)
                                                <x-dynamic-component :component="'icons.' . $stage->icon" class="w-5 h-5 sm:w-6 sm:h-6" />
                                            @else
                                                <span class="text-base font-black font-mono">{{ $index + 1 }}</span>
                                            @endif
                                        </div>
                                        <span class="mt-1.5 text-[11px] font-bold text-slate-400 font-mono">
                                            مرحله {{ $index + 1 }}
                                        </span>
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0 pt-0.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                            <h3 class="text-base sm:text-lg font-bold text-[#010619]">
                                                {{ $stage->title }}
                                            </h3>
                                            @if($stage->duration_hint)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>{{ $stage->duration_hint }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                            {!! nl2br(e($stage->description)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Next Step CTA --}}
                    <div class="rounded-2xl bg-gradient-to-r from-[#010619] to-[#152244] p-6 sm:p-8 text-white flex flex-col sm:flex-row items-center justify-between gap-5 shadow-md">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-white mb-1">
                                آماده‌اید کارت بانکی لوکس و منحصربه‌فرد خود را بسازید؟
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-300">
                                وارد محیط طراح آنلاین شوید، رنگ کارت و طرح دلخواه را برگزینید.
                            </p>
                        </div>
                        <a href="{{ route('custom-card.bank') }}" wire:navigate class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#ffde5b] text-[#010619] font-bold text-sm shadow-md hover:bg-[#ffe680] hover:scale-105 transition-all shrink-0">
                            <span>ورود به طراح کارت بانکی</span>
                            <x-icons.arrow-left class="w-4 h-4" />
                        </a>
                    </div>
                @else
                    {{-- Safe Draft / Maintenance Notice --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-10 sm:p-14 text-center shadow-xs">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-4 border border-amber-200/60">
                            <x-icons.credit-card class="h-7 w-7" />
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-[#010619] mb-2">
                            راهنمای این بخش در حال آماده‌سازی و به‌روزرسانی است
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mb-6">
                            جزییات دقیق مراحل ساخت کارت بانکی فلزی توسط تیم الواکارت در حال تدوین است. برای طراحی مستقیم می‌توانید وارد بخش اختصاصی شوید.
                        </p>
                        <a href="{{ route('custom-card.bank') }}" wire:navigate class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#010619] text-[#ffde5b] font-bold text-xs hover:bg-slate-800 transition">
                            <span>طراحی کارت بانکی فلزی</span>
                            <x-icons.arrow-left class="w-3.5 h-3.5" />
                        </a>
                    </div>
                @endif
            </div>

            {{-- 2. Fuel Card Workflow Tab --}}
            <div x-show="activeTab === 'fuel_card'" x-cloak class="space-y-6">
                @php
                    $isFuelPublished = $fuelGuide && $fuelGuide->is_published && $fuelGuide->activeStages->isNotEmpty();
                @endphp

                @if($isFuelPublished)
                    {{-- Guide Intro Card --}}
                    @if($fuelGuide->description)
                        <div class="rounded-2xl bg-white border border-slate-200 p-6 sm:p-7 shadow-xs">
                            <h2 class="text-lg sm:text-xl font-extrabold text-[#010619] mb-2">
                                {{ $fuelGuide->title }}
                            </h2>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                {!! nl2br(e($fuelGuide->description)) !!}
                            </p>
                        </div>
                    @endif

                    {{-- Stages Timeline --}}
                    <div class="space-y-4">
                        @foreach($fuelGuide->activeStages as $index => $stage)
                            <div class="rounded-2xl bg-white border border-slate-200/90 p-5 sm:p-6 shadow-xs hover:border-[#ffde5b]/60 transition-colors">
                                <div class="flex items-start gap-4 sm:gap-5">
                                    {{-- Step Number & Icon --}}
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-[#010619] text-[#ffde5b] flex items-center justify-center shadow-sm">
                                            @if($stage->icon)
                                                <x-dynamic-component :component="'icons.' . $stage->icon" class="w-5 h-5 sm:w-6 sm:h-6" />
                                            @else
                                                <span class="text-base font-black font-mono">{{ $index + 1 }}</span>
                                            @endif
                                        </div>
                                        <span class="mt-1.5 text-[11px] font-bold text-slate-400 font-mono">
                                            مرحله {{ $index + 1 }}
                                        </span>
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0 pt-0.5">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                            <h3 class="text-base sm:text-lg font-bold text-[#010619]">
                                                {{ $stage->title }}
                                            </h3>
                                            @if($stage->duration_hint)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>{{ $stage->duration_hint }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                            {!! nl2br(e($stage->description)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Next Step CTA --}}
                    <div class="rounded-2xl bg-gradient-to-r from-[#010619] to-[#152244] p-6 sm:p-8 text-white flex flex-col sm:flex-row items-center justify-between gap-5 shadow-md">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-white mb-1">
                                مایل به شخصی‌سازی یا ارتقای کارت سوخت خود هستید؟
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-300">
                                وارد طراح آنلاین کارت سوخت شوید و از میان گزینه‌ها طرح مورد نظر را برگزینید.
                            </p>
                        </div>
                        <a href="{{ route('custom-card.fuel') }}" wire:navigate class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#ffde5b] text-[#010619] font-bold text-sm shadow-md hover:bg-[#ffe680] hover:scale-105 transition-all shrink-0">
                            <span>ورود به طراح کارت سوخت</span>
                            <x-icons.arrow-left class="w-4 h-4" />
                        </a>
                    </div>
                @else
                    {{-- Safe Draft / Maintenance Notice --}}
                    <div class="rounded-3xl border border-slate-200 bg-white p-10 sm:p-14 text-center shadow-xs">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-4 border border-amber-200/60">
                            <x-icons.card class="h-7 w-7" />
                        </div>
                        <h3 class="text-base sm:text-lg font-bold text-[#010619] mb-2">
                            راهنمای این بخش در حال آماده‌سازی و به‌روزرسانی است
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mb-6">
                            جزییات دقیق مراحل سفارش کارت سوخت توسط تیم الواکارت در حال تدوین است. برای طراحی مستقیم می‌توانید وارد بخش اختصاصی شوید.
                        </p>
                        <a href="{{ route('custom-card.fuel') }}" wire:navigate class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#010619] text-[#ffde5b] font-bold text-xs hover:bg-slate-800 transition">
                            <span>طراحی کارت سوخت</span>
                            <x-icons.arrow-left class="w-3.5 h-3.5" />
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
