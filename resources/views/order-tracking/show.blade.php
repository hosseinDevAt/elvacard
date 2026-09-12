@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-gray-900">نتیجه پیگیری سفارش</h1>

            <div class="mt-6 grid gap-2 text-sm text-gray-700 sm:grid-cols-2">
                <p><span class="font-semibold">شماره سفارش:</span> {{ $order->reference }}</p>
                <p><span class="font-semibold">تاریخ ثبت:</span> {{ $order->created_at?->format('Y-m-d H:i') }}</p>
                <p><span class="font-semibold">وضعیت سفارش:</span> {{ $order->status?->faLabel() ?? $order->status }}</p>
                <p><span class="font-semibold">وضعیت پرداخت:</span> {{ $order->payment_status?->faLabel() ?? $order->payment_status }}</p>
                <p><span class="font-semibold">مبلغ کل:</span> {{ number_format($order->total_price) }} تومان</p>
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

            <div class="mt-6">
                <a href="{{ route('order-tracking.index') }}" class="text-sm text-primary-600 hover:text-primary-800 transition">پیگیری سفارش دیگر</a>
            </div>
        </div>
    </div>
@endsection