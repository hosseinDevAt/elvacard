@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        @if (session('info'))
            <div class="mb-4 rounded-md bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                {{ session('info') }}
            </div>
        @endif

        <div class="rounded-lg border border-green-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-green-700">سفارش با موفقیت ثبت شد</h1>
            <p class="mt-2 text-sm text-gray-600">سفارش شما ثبت شده و در انتظار مراحل بعدی است.</p>

            @if ($payment && in_array($payment->status?->value, ['pending_review', 'success', 'pending'], true))
                <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">
                    <p class="font-semibold">{{ $activeSetting?->success_message ?? 'پرداخت شما دریافت شد.' }}</p>
                    <p class="mt-1">شماره سفارش: {{ $order->reference }} — وضعیت پرداخت: {{ $payment->status?->faLabel() }}</p>
                </div>
            @elseif ($payment && $payment->status?->value === 'failed')
                <div class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">پرداخت شما رد شده است.</p>
                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="mt-1 inline-block font-medium text-red-900 underline">پرداخت مجدد</a>
                </div>
            @else
                <div class="mt-4 rounded-md bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    پرداخت هنوز ثبت نشده است.
                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="font-medium text-yellow-900 underline">تکمیل مرحله پرداخت</a>
                </div>
            @endif

            <div class="mt-6 space-y-2 text-sm text-gray-700">
                <p><span class="font-semibold">شماره سفارش:</span> {{ $order->reference }}</p>
                <p><span class="font-semibold">نام مشتری:</span> {{ $order->customer_name }}</p>
                <p><span class="font-semibold">مبلغ کل:</span> {{ number_format($order->total_price) }} تومان</p>
                <p><span class="font-semibold">وضعیت سفارش:</span> {{ $order->status?->faLabel() ?? $order->status }}</p>
                <p><span class="font-semibold">وضعیت پرداخت:</span> {{ $order->payment_status?->faLabel() ?? $order->payment_status }}</p>
            </div>

            <div class="mt-6 rounded-lg border border-gray-100">
                <h2 class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-900">آیتم‌های سفارش</h2>
                <div class="divide-y divide-gray-100">
                    @forelse ($order->items as $item)
                        <div class="px-4 py-3 text-sm">
                            <p class="font-medium text-gray-800 break-words">{{ $item->product_name_snapshot }}</p>
                            <p class="mt-1 text-gray-600">
                                تعداد: {{ $item->quantity }} — مبلغ: {{ number_format($item->final_price) }} تومان
                            </p>
                        </div>
                    @empty
                        <p class="px-4 py-3 text-sm text-gray-400">آیتمی برای این سفارش ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('catalog.products.index') }}" wire:navigate class="rounded bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition">بازگشت به محصولات</a>
                <a href="{{ route('home') }}" wire:navigate class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">بازگشت به صفحه اصلی</a>
            </div>
        </div>
    </div>
@endsection