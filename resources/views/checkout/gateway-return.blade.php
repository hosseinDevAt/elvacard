@extends('layouts.app')

@section('title', 'وضعیت پرداخت سفارش')

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($payment && $payment->status === \App\Enums\PaymentStatus::SUCCESS)
            <div class="store-card p-6 sm:p-8 text-center border-emerald-200/80 bg-white">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 mb-4 ring-8 ring-emerald-50/50">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900">پرداخت با موفقیت انجام شد</h1>
                <p class="mt-2 text-sm text-slate-600">پرداخت شما تأیید شده و سفارش در انتظار مراحل بعدی است.</p>
            </div>
        @elseif ($payment && $payment->status === \App\Enums\PaymentStatus::FAILED)
            <div class="store-card p-6 sm:p-8 text-center border-rose-200/80 bg-white">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 mb-4 ring-8 ring-rose-50/50">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900">پرداخت ناموفق بود</h1>
                <p class="mt-2 text-sm text-slate-600">مبلغی از حساب شما کسر نشده است. می‌توانید دوباره تلاش کنید.</p>
                <div class="mt-6">
                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="btn-brand-primary">
                        تلاش مجدد
                    </a>
                </div>
            </div>
        @elseif ($payment && $payment->status === \App\Enums\PaymentStatus::PENDING)
            <div class="store-card p-6 sm:p-8 text-center border-amber-200/80 bg-white">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-4 ring-8 ring-amber-50/50">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900">پرداخت در انتظار تأیید است</h1>
                <p class="mt-2 text-sm text-slate-600">وضعیت پرداخت شما هنوز نهایی نشده است. لطفاً کمی بعد وضعیت سفارش را بررسی کنید.</p>
            </div>
        @else
            <div class="store-card p-6 sm:p-8 text-center border-amber-200/80 bg-white">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-4 ring-8 ring-amber-50/50">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900">وضعیت پرداخت مشخص نیست</h1>
                <p class="mt-2 text-sm text-slate-600">برای ادامه، مرحله پرداخت را دوباره باز کنید.</p>
                <div class="mt-6">
                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="btn-brand-primary">
                        مرحله پرداخت
                    </a>
                </div>
            </div>
        @endif

        {{-- Order summary card --}}
        <div class="mt-6 store-card p-6 bg-white">
            <h3 class="text-xs font-bold text-slate-400 mb-4">اطلاعات سفارش</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="flex items-center justify-between sm:justify-start sm:gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs text-slate-500">شماره سفارش:</span>
                    <span class="font-bold text-slate-900 font-mono" dir="ltr">{{ $order->reference }}</span>
                </div>
                <div class="flex items-center justify-between sm:justify-start sm:gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs text-slate-500">مبلغ کل:</span>
                    <span class="font-extrabold text-slate-900 font-mono">{{ number_format($order->total_price) }} <span class="text-xs font-sans text-slate-500">تومان</span></span>
                </div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('catalog.products.index') }}" wire:navigate class="btn-brand-primary">
                بازگشت به محصولات
            </a>
            <a href="{{ route('home') }}" wire:navigate class="btn-brand-secondary">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection