@extends('layouts.app')

@section('title', 'پیگیری سفارش - الواکارت')

@section('content')
    <div class="min-h-[65vh] bg-slate-50/60 py-12 sm:py-16">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white p-6 sm:p-10 shadow-sm">
                {{-- Header Icon & Title --}}
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#010619] text-[#ffde5b] shadow-md shadow-[#010619]/15">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h1 class="mt-4 text-2xl font-black text-[#010619] sm:text-3xl">پیگیری سفارش</h1>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        برای پیگیری سفارش، کد پیگیری که پس از ثبت سفارش به شما نمایش داده شد را وارد کنید.
                    </p>
                </div>

                {{-- Errors --}}
                @if ($errors->any())
                    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 shadow-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5 mb-1 text-rose-900">
                            <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>خطا در استعلام سفارش</span>
                        </div>
                        @foreach ($errors->all() as $error)
                            <p class="text-xs text-rose-700">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{-- Tracking Form --}}
                <form method="POST" action="{{ route('order-tracking.check') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="token" class="mb-1.5 block text-xs font-bold text-slate-700">کد پیگیری سفارش <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input
                                id="token"
                                name="token"
                                type="text"
                                value="{{ old('token') }}"
                                required
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="مثلاً: 0M1Msl8znVAVii3BWmhh..."
                                dir="ltr"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 font-mono text-sm font-semibold text-slate-800 placeholder-slate-400 text-left focus:border-[#010619] focus:outline-none focus:ring-2 focus:ring-[#ffde5b]/60 transition"
                            />
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">کد توکن ۳۲ الی ۴۰ کاراکتری دریافت شده در صفحه تسویه حساب</p>
                    </div>

                    <button type="submit" class="w-full btn-brand-primary py-3.5 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>پیگیری سفارش</span>
                    </button>
                </form>

                {{-- Hint Callout --}}
                <div class="mt-8 rounded-2xl border border-slate-100 bg-slate-50/70 p-4 text-xs text-slate-500 leading-relaxed">
                    <div class="flex items-start gap-2.5">
                        <svg class="h-4 w-4 shrink-0 text-slate-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>کد پیگیری بدون نیاز به ورود به حساب کاربری، وضعیت پردازش، تولید، ثبت پرداخت و ارسال سفارش شما را نمایش می‌دهد.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection