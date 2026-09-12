@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($lastFailedPayment)
            <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">پرداخت قبلی شما رد شده است.</p>
                @if ($setting)
                    <p>لطفاً رسید پرداخت جدید ارسال کنید.</p>
                @else
                    <p>پرداخت کارت به کارت در حال حاضر فعال نیست. لطفاً بعداً تلاش کنید.</p>
                @endif
            </div>
        @endif

        <h1 class="text-2xl font-bold text-gray-900">مرحله پرداخت</h1>
        <p class="mt-1 text-sm text-gray-600">مبلغ سفارش را واریز و رسید پرداخت را آپلود کنید.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">جزئیات پرداخت</h2>
                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        <p><span class="font-semibold">شماره سفارش:</span> {{ $order->reference }}</p>
                        <p><span class="font-semibold">مبلغ قابل پرداخت:</span> {{ number_format($order->total_price) }} تومان</p>
                    </div>
                </div>

                @if ($setting)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">اطلاعات پرداخت کارت‌به‌کارت</h2>
                        <div class="mt-3 space-y-2 text-sm text-gray-700">
                            @if ($setting->card_number)
                                <p><span class="font-semibold">شماره کارت:</span> <span dir="ltr" class="font-mono break-all">{{ $setting->card_number }}</span></p>
                            @endif
                            @if ($setting->iban)
                                <p><span class="font-semibold">شماره شبا:</span> <span dir="ltr" class="font-mono break-all">{{ $setting->iban }}</span></p>
                            @endif
                            @if ($setting->account_name)
                                <p><span class="font-semibold">به نام:</span> {{ $setting->account_name }}</p>
                            @endif
                            @if ($setting->instruction_message)
                                <p class="rounded bg-gray-50 p-2 text-gray-600">{{ $setting->instruction_message }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="rounded-md bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        پرداخت کارت‌به‌کارت در حال حاضر فعال نیست. سفارش شما ثبت شده و به‌زودی هماهنگ خواهد شد.
                    </div>
                @endif
            </div>

            <div>
                @if ($guestRetryBlocked)
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">پرداخت مجدد</h2>
                        <p class="mt-2 text-sm text-gray-600">پرداخت مجدد برای سفارش مهمان امکان‌پذیر نیست. لطفاً برای ادامه پرداخت با پشتیبانی تماس بگیرید یا حساب کاربری ایجاد کنید.</p>
                    </div>
                @elseif ($setting)
                    <form method="POST" action="{{ route('checkout.payment.store', $order->token) }}" enctype="multipart/form-data" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm space-y-4">
                        @csrf
                        <h2 class="text-lg font-semibold text-gray-900">ثبت رسید پرداخت</h2>

                        <div>
                            <label for="receipt_image" class="mb-1 block text-sm font-medium text-gray-700">تصویر رسید (اجباری)</label>
                            <input id="receipt_image" name="receipt_image" type="file" accept="image/jpeg,image/png,image/webp" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm" />
                            <p class="mt-1 text-xs text-gray-500">فقط تصویر با فرمت JPG، PNG یا WEBP و حداکثر حجم ۵ مگابایت.</p>
                        </div>

                        <div>
                            <label for="tracking_number" class="mb-1 block text-sm font-medium text-gray-700">شماره پیگیری پرداخت (اختیاری)</label>
                            <input id="tracking_number" name="tracking_number" type="text" value="{{ old('tracking_number') }}" maxlength="100" class="w-full rounded border border-gray-300 px-3 py-2 text-sm" dir="ltr" placeholder="1234567890" />
                        </div>

                        <div>
                            <label for="note" class="mb-1 block text-sm font-medium text-gray-700">توضیحات (اختیاری)</label>
                            <textarea id="note" name="note" rows="3" maxlength="1000" class="w-full rounded border border-gray-300 px-3 py-2 text-sm">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit" class="w-full rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">ثبت رسید</button>
                    </form>
                @else
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <a href="{{ route('checkout.success', $order->token) }}" class="w-full inline-block rounded bg-gray-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-gray-700 transition">مشاهده وضعیت سفارش</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection