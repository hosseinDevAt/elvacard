@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($payment && $payment->status === \App\Enums\PaymentStatus::SUCCESS)
            <div class="rounded-lg border border-green-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-green-700">پرداخت با موفقیت انجام شد</h1>
                <p class="mt-2 text-sm text-gray-600">پرداخت شما تأیید شده و سفارش در انتظار مراحل بعدی است.</p>
            </div>
        @elseif ($payment && $payment->status === \App\Enums\PaymentStatus::FAILED)
            <div class="rounded-lg border border-red-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-red-700">پرداخت ناموفق بود</h1>
                <p class="mt-2 text-sm text-gray-600">مبلغی از حساب شما کسر نشده است. می‌توانید دوباره تلاش کنید.</p>
                <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="mt-4 inline-block rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">تلاش مجدد</a>
            </div>
        @elseif ($payment && $payment->status === \App\Enums\PaymentStatus::PENDING)
            <div class="rounded-lg border border-yellow-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-yellow-800">پرداخت در انتظار تأیید است</h1>
                <p class="mt-2 text-sm text-gray-600">وضعیت پرداخت شما هنوز نهایی نشده است. لطفاً کمی بعد وضعیت سفارش را بررسی کنید.</p>
            </div>
        @else
            <div class="rounded-lg border border-yellow-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-yellow-800">وضعیت پرداخت مشخص نیست</h1>
                <p class="mt-2 text-sm text-gray-600">برای ادامه، مرحله پرداخت را دوباره باز کنید.</p>
                <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="mt-4 inline-block rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">مرحله پرداخت</a>
            </div>
        @endif

        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm text-sm text-gray-700">
            <p><span class="font-semibold">شماره سفارش:</span> {{ $order->reference }}</p>
            <p class="mt-1"><span class="font-semibold">مبلغ کل:</span> {{ number_format($order->total_price) }} تومان</p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('catalog.products.index') }}" wire:navigate class="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">بازگشت به محصولات</a>
            <a href="{{ route('home') }}" wire:navigate class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">بازگشت به صفحه اصلی</a>
        </div>
    </div>
@endsection