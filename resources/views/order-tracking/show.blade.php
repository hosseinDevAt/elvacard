@extends('layouts.app')

@section('title', 'نتیجه پیگیری سفارش - الواکارت')

@section('content')
    <div class="min-h-[70vh] bg-slate-50/60 py-10 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'پیگیری سفارش', 'url' => route('order-tracking.index')],
                ['label' => 'سفارش ' . $order->reference]
            ]" />

            <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-sm">
                {{-- Card Header --}}
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-6 sm:px-8 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ffde5b]/20 text-[#664d00] border border-[#ffde5b]/60 px-3 py-1 text-xs font-bold mb-2">
                            <span>استعلام موفق</span>
                        </span>
                        <h1 class="text-2xl font-black text-[#010619]">نتیجه پیگیری سفارش</h1>
                    </div>

                    <a href="{{ route('order-tracking.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200 px-4 py-2 rounded-xl transition hover:border-slate-300">
                        <x-icons.arrow-right class="h-4 w-4" />
                        <span>پیگیری سفارش دیگر</span>
                    </a>
                </div>

                <div class="p-6 sm:p-8 space-y-6">
                    {{-- Order Stats Grid --}}
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                            <span class="block text-xs font-medium text-slate-500">شماره سفارش</span>
                            <span class="mt-1 block font-mono text-sm font-black text-[#010619]">{{ $order->reference }}</span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                            <span class="block text-xs font-medium text-slate-500">تاریخ ثبت</span>
                            <span class="mt-1 block text-xs font-bold text-slate-800">{{ jalali_date($order->created_at, 'datetime') }}</span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 col-span-2 sm:col-span-1">
                            <span class="block text-xs font-medium text-slate-500">مبلغ کل سفارش</span>
                            <span class="mt-1 block text-base font-black text-[#010619]">
                                {{ number_format($order->total_price) }} <span class="text-xs font-normal text-slate-500">تومان</span>
                            </span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                            <span class="block text-xs font-medium text-slate-500">وضعیت سفارش</span>
                            <span class="mt-1.5 inline-block rounded-lg bg-slate-200/80 px-2.5 py-1 text-xs font-bold text-slate-800">
                                {{ $order->status?->faLabel() ?? $order->status }}
                            </span>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 col-span-2 sm:col-span-2">
                            <span class="block text-xs font-medium text-slate-500">وضعیت پرداخت</span>
                            <span class="mt-1.5 inline-block rounded-lg bg-slate-200/80 px-2.5 py-1 text-xs font-bold text-slate-800">
                                {{ $order->payment_status?->faLabel() ?? $order->payment_status }}
                            </span>
                        </div>
                    </div>

                    {{-- Order Items Card --}}
                    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                        <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                            <h2 class="text-xs font-bold text-slate-700 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                                <span>آیتم‌های سفارش</span>
                            </h2>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @forelse ($order->items as $item)
                                <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                                    <div class="space-y-1">
                                        <p class="text-sm font-bold text-[#010619] break-words">{{ $item->product_name_snapshot }}</p>
                                        <p class="text-xs text-slate-500">
                                            تعداد: <span class="font-bold text-slate-800">{{ $item->quantity }}</span>
                                        </p>
                                    </div>
                                    <div class="text-left">
                                        <span class="text-sm font-black text-[#010619]">{{ number_format($item->final_price) }}</span>
                                        <span class="text-xs font-medium text-slate-500 mr-1">تومان</span>
                                    </div>
                                </div>
                            @empty
                                <p class="p-6 text-center text-xs text-slate-400">آیتمی برای این سفارش ثبت نشده است.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Bottom Return Links --}}
                    <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                        <a href="{{ route('order-tracking.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-5 py-2.5 text-xs font-bold text-slate-700 transition">
                            <span>پیگیری سفارش دیگر</span>
                        </a>

                        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                            <span>بازگشت به صفحه اصلی</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection