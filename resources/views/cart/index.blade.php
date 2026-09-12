@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">سبد خرید</h1>
                <p class="mt-1 text-sm text-gray-600">مدیریت آیتم‌های انتخابی قبل از ثبت سفارش اولیه</p>
            </div>

                            <a href="{{ route('catalog.products.index') }}" wire:navigate class="text-sm text-primary-600 hover:text-primary-800 transition">ادامه خرید</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (empty($cart['items']))
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
                سبد خرید شما خالی است.
            </div>
        @else
            <div class="space-y-4">
                @foreach ($cart['items'] as $item)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-1 text-sm text-gray-700">
                                <p class="break-words"><span class="font-semibold">محصول:</span> {{ $item['product_name_snapshot'] }}</p>
                                <p class="break-words"><span class="font-semibold">رنگ:</span> {{ $item['color_name_snapshot'] ?? $item['color_id'] }}</p>
                                <p class="break-words"><span class="font-semibold">طرح:</span> {{ $item['design_name_snapshot'] ?? $item['design_id'] }}</p>
                                @if ($item['design_image_id'] ?? null)
                                    <p><span class="font-semibold">تصویر طرح:</span> {{ $item['design_image_id'] }}</p>
                                @endif
                            </div>

                            <div class="space-y-1 text-sm text-gray-700">
                                <p><span class="font-semibold">قیمت واحد:</span> {{ number_format($item['unit_price_snapshot']) }} تومان</p>
                                <p><span class="font-semibold">تعداد:</span> {{ $item['quantity'] }}</p>
                                <p><span class="font-semibold">قیمت نهایی آیتم:</span> {{ number_format($item['final_price']) }} تومان</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <form method="POST" action="{{ route('cart.update', $item['id']) }}" class="flex items-center gap-2">
                                @csrf
                                <label class="text-sm text-gray-600" for="quantity_{{ $item['id'] }}">تعداد</label>
                                <input id="quantity_{{ $item['id'] }}" name="quantity" type="number" min="1" max="20" value="{{ $item['quantity'] }}" class="w-20 rounded border border-gray-300 px-2 py-1 text-sm" />
                                <button type="submit" class="rounded bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition">به‌روزرسانی</button>
                            </form>

                            <form method="POST" action="{{ route('cart.remove', $item['id']) }}">
                                @csrf
                                <button type="submit" class="rounded bg-red-100 px-3 py-1 text-sm text-red-700 hover:bg-red-200 transition">حذف</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-700"><span class="font-semibold">تعداد کل:</span> {{ $cart['total_quantity'] }}</p>
                <p class="text-sm text-gray-700"><span class="font-semibold">مجموع سبد:</span> {{ number_format($cart['total_price']) }} تومان</p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('cart.empty') }}">
                        @csrf
                        <button type="submit" class="rounded bg-red-100 px-4 py-2 text-sm text-red-700 hover:bg-red-200 transition">پاک کردن سبد</button>
                    </form>

                    <a href="{{ route('checkout.index') }}" wire:navigate class="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">
                        ادامه به تسویه حساب
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection