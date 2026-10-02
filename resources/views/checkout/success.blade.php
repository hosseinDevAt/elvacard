@extends('layouts.app')

@section('content')
    <div class="min-h-[70vh] bg-slate-50/60 py-10 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            {{-- Info message --}}
            @if (session('info'))
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-xs">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Main Success Card --}}
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                {{-- Hero Banner --}}
                <div class="border-b border-slate-100 bg-gradient-to-b from-emerald-50/70 to-white px-6 py-8 text-center sm:px-10">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 shadow-xs">
                        <svg class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="mt-4 text-2xl font-black text-[#010619] sm:text-3xl">سفارش با موفقیت ثبت شد</h1>
                    <p class="mt-2 text-sm text-slate-600">سفارش شما ثبت شده و در انتظار مراحل بعدی است.</p>
                </div>

                <div class="p-6 sm:p-8 space-y-6">
                    {{-- Payment Status Banner --}}
                    @if ($payment && in_array($payment->status?->value, ['pending_review', 'success', 'pending'], true))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 sm:p-5 text-emerald-900">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-sm">
                                    <p class="font-bold">{{ $activeSetting?->success_message ?? 'پرداخت شما دریافت شد.' }}</p>
                                    <p class="mt-1 text-xs text-emerald-800">شماره سفارش: <span class="font-bold font-mono">{{ $order->reference }}</span> — وضعیت پرداخت: <span class="font-bold">{{ $payment->status?->faLabel() }}</span></p>
                                </div>
                            </div>
                        </div>
                    @elseif ($payment && $payment->status?->value === 'failed')
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5 text-rose-900">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="text-sm">
                                    <p class="font-bold">پرداخت شما رد شده است.</p>
                                    <p class="mt-1 text-xs text-rose-700">رسید ارسالی مورد تأیید قرار نگرفت. لطفاً رسید جدید بارگذاری کنید.</p>
                                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white hover:bg-rose-700 transition">
                                        <span>پرداخت مجدد</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5 text-amber-900">
                            <div class="flex items-start gap-3">
                                <svg class="h-5 w-5 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-sm">
                                    <p class="font-bold">پرداخت هنوز ثبت نشده است.</p>
                                    <p class="mt-1 text-xs text-amber-800">برای شروع فرآیند پردازش سفارش، لطفاً مبلغ را واریز و فیش را بارگذاری کنید.</p>
                                    <a href="{{ route('checkout.payment', $order->token) }}" wire:navigate class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-[#010619] px-4 py-2 text-xs font-bold text-[#ffde5b] hover:bg-slate-800 transition">
                                        <span>تکمیل مرحله پرداخت</span>
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Order Overview Stats Grid --}}
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5">
                            <span class="block text-xs font-medium text-slate-500">شماره سفارش</span>
                            <span class="mt-1 block font-mono text-sm font-black text-[#010619]">{{ $order->reference }}</span>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5">
                            <span class="block text-xs font-medium text-slate-500">نام مشتری</span>
                            <span class="mt-1 block text-sm font-bold text-[#010619] truncate">{{ $order->customer_name }}</span>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 col-span-2 sm:col-span-1">
                            <span class="block text-xs font-medium text-slate-500">مبلغ کل</span>
                            <span class="mt-1 block text-sm font-black text-[#010619]">
                                {{ number_format($order->total_price) }} <span class="text-xs font-normal text-slate-500">تومان</span>
                            </span>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5">
                            <span class="block text-xs font-medium text-slate-500">وضعیت سفارش</span>
                            <span class="mt-1 inline-block rounded-md bg-slate-200/70 px-2 py-0.5 text-xs font-bold text-slate-800">
                                {{ $order->status?->faLabel() ?? $order->status }}
                            </span>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3.5 col-span-2 sm:col-span-2">
                            <span class="block text-xs font-medium text-slate-500">وضعیت پرداخت</span>
                            <span class="mt-1 inline-block rounded-md bg-slate-200/70 px-2 py-0.5 text-xs font-bold text-slate-800">
                                {{ $order->payment_status?->faLabel() ?? $order->payment_status }}
                            </span>
                        </div>
                    </div>

                    {{-- Order Tracking Code Card --}}
                    <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-900 to-[#010619] p-5 text-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-[#ffde5b]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                <h2 class="text-sm font-bold text-white">پیگیری سفارش</h2>
                            </div>
                            <span class="rounded-full bg-[#ffde5b]/15 px-2.5 py-0.5 text-[11px] font-bold text-[#ffde5b]">کد اختصاصی</span>
                        </div>
                        <div class="mt-3 text-xs text-slate-300">
                            <p>این کد پیگیری را نگه دارید. با آن می‌توانید بدون ورود به حساب، وضعیت سفارش را ببینید.</p>
                            <div class="mt-3 rounded-xl border border-white/10 bg-white/5 p-3 text-center sm:text-left">
                                <span class="font-mono text-sm sm:text-base font-bold tracking-wider text-[#ffde5b] select-all break-all" dir="ltr">
                                    {{ $order->token }}
                                </span>
                            </div>
                            <div class="mt-3 text-left">
                                <a href="{{ route('order-tracking.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-[#ffde5b] hover:text-[#f5d347] transition">
                                    <span>مشاهده وضعیت سفارش</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Order Items Section --}}
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3">
                            <h2 class="text-xs font-bold text-slate-700">آیتم‌های سفارش</h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse ($order->items as $item)
                                <div class="p-4 flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-bold text-[#010619] break-words">{{ $item->product_name_snapshot }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            تعداد: <span class="font-bold text-slate-700">{{ $item->quantity }}</span>
                                        </p>
                                    </div>
                                    <div class="text-left shrink-0">
                                        <span class="text-sm font-black text-[#010619]">{{ number_format($item->final_price) }}</span>
                                        <span class="text-xs font-medium text-slate-500 mr-1">تومان</span>
                                    </div>
                                </div>
                            @empty
                                <p class="p-5 text-center text-xs text-slate-400">آیتمی برای این سفارش ثبت نشده است.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Navigation Actions --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('catalog.products.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-[#ffde5b] px-5 py-3 text-xs font-bold text-[#010619] shadow-sm shadow-[#ffde5b]/20 hover:bg-[#f5d347] transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>بازگشت به محصولات</span>
                        </a>
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                            <span>بازگشت به صفحه اصلی</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection