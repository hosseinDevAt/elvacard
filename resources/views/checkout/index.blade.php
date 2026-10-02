@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl tracking-tight">تسویه حساب</h1>
                <p class="mt-1 text-sm text-slate-500">مشخصات دریافت‌کننده را تکمیل و سفارش خود را ثبت کنید</p>
            </div>

            <a href="{{ route('cart.index') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200/90 px-4 py-2 rounded-xl shadow-xs transition hover:border-slate-300">
                <x-icons.arrow-left class="w-4 h-4" />
                <span>بازگشت به سبد خرید</span>
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200/80 px-5 py-4 text-sm font-semibold text-rose-800 shadow-xs space-y-1.5">
                <div class="font-bold flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                    <span>لطفاً خطاهای زیر را بررسی و برطرف نمایید:</span>
                </div>
                @foreach ($errors->all() as $error)
                    <p class="text-xs text-rose-700 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                        <span>{{ $error }}</span>
                    </p>
                @endforeach
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-3">
            {{-- Order Summary List --}}
            <div class="space-y-4 lg:col-span-1 lg:order-2">
                <div class="sticky top-24 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs space-y-5">
                    <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                        <span>خلاصه فاکتور سفارش</span>
                    </h2>

                    <div class="space-y-3 max-h-96 overflow-y-auto ps-1 pe-1">
                        @foreach ($cart['items'] as $item)
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3.5 text-xs space-y-2">
                                <div class="font-bold text-slate-900 text-sm leading-snug">
                                    {{ $item['product_name_snapshot'] }}
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 text-slate-600">
                                    @if (! empty($item['color_name_snapshot']))
                                        <span class="rounded bg-white px-2 py-0.5 border border-slate-200">رنگ: {{ $item['color_name_snapshot'] }}</span>
                                    @endif
                                    @if (! empty($item['design_name_snapshot']))
                                        <span class="rounded bg-[#ffde5b]/20 text-[#664d00] px-2 py-0.5 border border-[#ffde5b]/50 font-bold">طرح: {{ $item['design_name_snapshot'] }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-slate-500">
                                    <span>تعداد: <strong class="text-slate-800">{{ $item['quantity'] }}</strong></span>
                                    <span class="font-bold text-slate-900 text-xs">{{ number_format($item['final_price']) }} تومان</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-3 border-t border-slate-100 space-y-2 text-sm text-slate-600">
                        <div class="flex items-center justify-between">
                            <span>تعداد کل اقلام:</span>
                            <span class="font-bold text-slate-900">{{ $cart['total_quantity'] }} عدد</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <span class="text-slate-900 font-bold">مبلغ نهایی سفارش:</span>
                            <div class="text-end">
                                <span class="text-xl font-extrabold text-slate-900">{{ number_format($cart['total_price']) }}</span>
                                <span class="text-xs font-semibold text-slate-500">تومان</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Customer & Shipping Form --}}
            <div class="lg:col-span-2 lg:order-1">
                <form method="POST" action="{{ route('checkout.store') }}" class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ $submissionToken }}" />

                    <div class="pb-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                            <span>اطلاعات تماس و نشانی ارسال</span>
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">اطلاعات جهت هماهنگی، ارسال مرسوله و پیگیری سفارش استفاده خواهد شد.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="customer_name" class="mb-1.5 block text-xs font-bold text-slate-700">نام و نام خانوادگی <span class="text-rose-500">*</span></label>
                            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" required
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                                   placeholder="علیرضا محمدی" />
                            @error('customer_name') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-1.5 block text-xs font-bold text-slate-700">شماره موبایل <span class="text-rose-500">*</span></label>
                            <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone') }}" required dir="ltr"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                                   placeholder="09123456789" />
                            @error('customer_phone') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="shipping_address" class="mb-1.5 block text-xs font-bold text-slate-700">نشانی کامل پستی <span class="text-rose-500">*</span></label>
                        <textarea id="shipping_address" name="shipping_address" rows="3" required
                                  class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                                  placeholder="استان، شهر، خیابان اصلی، کوچه...">{{ old('shipping_address') }}</textarea>
                        @error('shipping_address') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="shipping_postal_code" class="mb-1.5 block text-xs font-bold text-slate-700">کد پستی ۱۰ رقمی <span class="text-rose-500">*</span></label>
                            <input id="shipping_postal_code" name="shipping_postal_code" type="text" value="{{ old('shipping_postal_code') }}" required maxlength="10" dir="ltr"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 text-left focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                                   placeholder="1234567890" />
                            @error('shipping_postal_code') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="shipping_plaque" class="mb-1.5 block text-xs font-bold text-slate-700">پلاک</label>
                            <input id="shipping_plaque" name="shipping_plaque" type="text" value="{{ old('shipping_plaque') }}" maxlength="50"
                                   class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                                   placeholder="مثلاً: ۱۲" />
                            @error('shipping_plaque') <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="shipping_description" class="mb-1.5 block text-xs font-bold text-slate-700">توضیحات آدرس (اختیاری)</label>
                        <input id="shipping_description" name="shipping_description" type="text" value="{{ old('shipping_description') }}" maxlength="1000"
                               class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                               placeholder="مثلاً: زنگ سوم، طبقه دوم" />
                    </div>

                    <div>
                        <label for="notes" class="mb-1.5 block text-xs font-bold text-slate-700">یادداشت سفارش (اختیاری)</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                                  placeholder="نکته خاص در رابطه با بسته‌بندی یا زمان تحویل...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <button type="submit" class="w-full btn-brand-primary py-3.5 text-base font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2">
                            <span>ثبت سفارش</span>
                            <x-icons.arrow-left class="w-5 h-5" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection