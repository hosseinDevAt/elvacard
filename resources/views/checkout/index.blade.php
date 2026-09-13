@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">تسویه حساب</h1>
                <p class="mt-1 text-sm text-gray-600">بررسی نهایی سبد خرید و ثبت سفارش اولیه</p>
            </div>
                            <a href="{{ route('cart.index') }}" wire:navigate class="text-sm text-primary-600 hover:text-primary-800 transition">بازگشت به سبد خرید</a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">خلاصه سبد خرید</h2>

                    <div class="space-y-3">
                        @foreach ($cart['items'] as $item)
                            <div class="rounded border border-gray-100 p-3 text-sm">
                                <p><span class="font-semibold">محصول:</span> {{ $item['product_name_snapshot'] }}</p>
                                <p><span class="font-semibold">رنگ:</span> {{ $item['color_name_snapshot'] ?? 'ثبت نشده' }}</p>
                                <p><span class="font-semibold">طرح:</span> {{ $item['design_name_snapshot'] ?? 'ثبت نشده' }}</p>
                                @if (! empty($item['design_image_path_snapshot']))
                                    <p><span class="font-semibold">تصویر طرح:</span> {{ $item['design_image_path_snapshot'] }}</p>
                                @endif
                                <p><span class="font-semibold">تعداد:</span> {{ $item['quantity'] }}</p>
                                <p><span class="font-semibold">قیمت واحد:</span> {{ number_format($item['unit_price_snapshot']) }} تومان</p>
                                <p><span class="font-semibold">قیمت نهایی:</span> {{ number_format($item['final_price']) }} تومان</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <form method="POST" action="{{ route('checkout.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm space-y-4">
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ $submissionToken }}" />
                    <h2 class="text-lg font-semibold text-gray-900">اطلاعات مشتری</h2>

                    <div>
                        <label for="customer_name" class="mb-1 block text-sm font-medium text-gray-700">نام کامل</label>
                        <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $customer['name'] ?? '') }}" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="نام و نام خانوادگی" />
                    </div>

                    <div>
                        <label for="customer_phone" class="mb-1 block text-sm font-medium text-gray-700">شماره تماس</label>
                        <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone', $customer['phone'] ?? '') }}" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="09123456789" dir="ltr" />
                        @error('customer_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="shipping_address" class="mb-1 block text-sm font-medium text-gray-700">آدرس ارسال</label>
                        <textarea id="shipping_address" name="shipping_address" rows="3" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="استان، شهر، خیابان...">{{ old('shipping_address', $customer['address'] ?? '') }}</textarea>
                        @error('shipping_address') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="shipping_postal_code" class="mb-1 block text-sm font-medium text-gray-700">کد پستی</label>
                            <input id="shipping_postal_code" name="shipping_postal_code" type="text" value="{{ old('shipping_postal_code', $customer['postal_code'] ?? '') }}" required maxlength="10" class="w-full rounded border border-gray-300 px-3 py-2 text-sm text-left" placeholder="1234567890" dir="ltr" />
                            @error('shipping_postal_code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="shipping_plaque" class="mb-1 block text-sm font-medium text-gray-700">پلاک</label>
                            <input id="shipping_plaque" name="shipping_plaque" type="text" value="{{ old('shipping_plaque', $customer['plaque'] ?? '') }}" maxlength="50" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="۱۲" />
                            @error('shipping_plaque') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="shipping_description" class="mb-1 block text-sm font-medium text-gray-700">توضیحات آدرس (اختیاری)</label>
                        <input id="shipping_description" name="shipping_description" type="text" value="{{ old('shipping_description') }}" maxlength="1000" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" placeholder="مثلاً: طبقه دوم، واحد ۳" />
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-sm font-medium text-gray-700">توضیحات سفارش (اختیاری)</label>
                        <textarea id="notes" name="notes" rows="4" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="border-t border-gray-200 pt-3 text-sm text-gray-700">
                        <p><span class="font-semibold">تعداد کل:</span> {{ $cart['total_quantity'] }}</p>
                        <p class="mt-1"><span class="font-semibold">مجموع نهایی:</span> {{ number_format($cart['total_price']) }} تومان</p>
                    </div>

                    <button type="submit" class="w-full rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">ثبت سفارش</button>
                </form>
            </div>
        </div>
    </div>
@endsection