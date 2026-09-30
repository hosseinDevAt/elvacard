@extends('layouts.app')

@section('title')طراحی کارت سوخت - {{ site_setting('site_name', config('app.name')) }}@endsection

@section('meta')
    <meta name="description" content="شخصی‌سازی کارت سوخت اختصاصی با تمام مشخصات خودرو، شماره شاسی و سامانه سوخت.">
    <link rel="canonical" href="{{ route('custom-card.fuel') }}">
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('custom-card.design') }}" wire:navigate
               class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 transition">
                <x-icons.arrow-left class="ms-1" />
                بازگشت به صفحه طراحی کارت اختصاصی
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">طراحی کارت سوخت</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $product->name }}</p>
        </div>

        <livewire:catalog.product-customizer :productId="$product->id" />
    </div>
@endsection