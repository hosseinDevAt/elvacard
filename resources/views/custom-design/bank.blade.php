@extends('layouts.app')

@section('meta')
    <title>طراحی کارت بانکی - {{ site_setting('site_name', config('app.name')) }}</title>
    <meta name="description" content="کارت بانکی خود را با طرح دلخواه‌تان طراحی کنید؛ رنگ، طرح و متن را انتخاب کرده و سفارش دهید.">
    <link rel="canonical" href="{{ route('custom-card.bank') }}">
@endsection

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('custom-card.design') }}" wire:navigate
               class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 transition">
                <x-icons.arrow-left class="ms-1" />
                بازگشت به طراحی کارت اختصاصی
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">طراحی کارت بانکی</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $product->name }}</p>
        </div>

        <livewire:catalog.product-customizer :productId="$product->id" />
    </div>
@endsection