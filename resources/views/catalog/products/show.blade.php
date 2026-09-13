@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('catalog.products.index') }}" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-800 transition">
                <x-icons.arrow-left class="ms-1" />
                بازگشت به محصولات
            </a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">نوع: {{ $product->type?->faLabel() ?? $product->type }}</p>
            @if($product->description)
                <p class="mt-3 text-sm text-gray-700">{{ $product->description }}</p>
            @endif
        </div>

        <div class="mb-6">
            @if($hasCustomization && $customizationAvailable)
                <livewire:catalog.product-customizer :productId="$product->id" />
            @elseif($hasCustomization)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    این محصول تا راه‌اندازی سرویس شخصی‌سازی هنوز قابل خرید نیست.
                </div>
            @else
                <form method="POST" action="{{ route('cart.add') }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="flex flex-wrap items-end gap-4">
                        @if($product->colorPrices->isNotEmpty())
                            <div>
                                <label for="commerce_color" class="block text-sm font-medium text-gray-700 mb-1">رنگ (اختیاری)</label>
                                <select name="color_id" id="commerce_color" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                                    <option value="">بدون انتخاب رنگ</option>
                                    @foreach($product->colorPrices as $colorPrice)
                                        <option value="{{ $colorPrice->color_id }}" {{ $selectedColorId === $colorPrice->color_id ? 'selected' : '' }}>
                                            {{ $colorPrice->color->name }} — {{ number_format($colorPrice->price) }} تومان
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label for="commerce_quantity" class="block text-sm font-medium text-gray-700 mb-1">تعداد</label>
                            <input type="number" name="quantity" id="commerce_quantity" value="1" min="1" max="20" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                        </div>
                        <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm hover:bg-yellow-600 transition">
                            افزودن به سبد خرید
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-1">
                @include('catalog.partials.color-price-list', [
                    'product' => $product,
                    'colorPrices' => $product->colorPrices,
                    'selectedColorId' => $selectedColorId,
                ])
            </div>
        </div>
    </div>
@endsection