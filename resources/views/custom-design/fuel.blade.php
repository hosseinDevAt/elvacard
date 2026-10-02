@extends('layouts.app')

@section('title')طراحی کارت سوخت - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    <meta name="description" content="شخصی‌سازی کارت سوخت اختصاصی با تمام مشخصات خودرو، شماره شاسی و سامانه سوخت.">
    <link rel="canonical" href="{{ route('custom-card.fuel') }}">
@endsection

@section('content')
    <div class="min-h-screen bg-slate-50/60 py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <a href="{{ route('custom-card.design') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-[#010619] transition">
                        <x-icons.arrow-left class="h-4 w-4" />
                        <span>بازگشت به صفحه طراحی کارت اختصاصی</span>
                    </a>
                    <h1 class="mt-2 text-2xl font-black text-[#010619] sm:text-3xl">طراحی کارت سوخت</h1>
                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $product->name }}</p>
                </div>
            </div>

            <livewire:catalog.product-customizer :productId="$product->id" />
        </div>
    </div>
@endsection