@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('catalog.products.index') }}" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 transition">
                <svg class="w-4 h-4 ms-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8l-4 4m0 0l4 4m-4-4h17" />
                </svg>
                بازگشت به محصولات
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">نوع: {{ $product->type?->faLabel() ?? $product->type }}</p>
            @if($product->description)
                <p class="mt-3 text-sm text-gray-700">{{ $product->description }}</p>
            @endif
        </div>

        <div class="mb-6">
            <livewire:catalog.product-customizer :productId="$product->id" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-1">
                @include('catalog.partials.color-price-list', [
                    'product' => $product,
                    'colorPrices' => $product->colorPrices,
                    'selectedColorId' => $selectedColorId,
                ])
            </div>

            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">فهرست طرح‌ها</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($selectedColorId)
                            نمایش طرح‌های سازگار با رنگ انتخاب‌شده.
                        @else
                            نمایش تمام طرح‌های فعال.
                        @endif
                    </p>
                </div>

                @include('catalog.partials.design-grid', ['catalog' => $designCatalog])
            </div>
        </div>
    </div>
@endsection