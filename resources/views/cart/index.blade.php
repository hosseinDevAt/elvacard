@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <x-breadcrumbs :items="[
            ['label' => 'سبد خرید']
        ]" />

        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl tracking-tight">سبد خرید</h1>
                <p class="mt-1 text-sm text-slate-500">مدیریت آیتم‌های انتخابی قبل از ثبت سفارش اولیه</p>
            </div>

            <a href="{{ route('catalog.products.index') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-[#010619] bg-white border border-slate-200/90 px-4 py-2 rounded-xl shadow-xs transition hover:border-slate-300">
                <x-icons.arrow-right class="w-4 h-4" />
                <span>ادامه خرید</span>
            </a>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200/80 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl bg-rose-50 border border-rose-200/80 px-4 py-3 text-sm font-semibold text-rose-800 shadow-xs space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        <span>{{ $error }}</span>
                    </p>
                @endforeach
            </div>
        @endif

        @if (empty($cart['items']))
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-4">
                    <x-icons.cart class="h-8 w-8" />
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">سبد خرید شما خالی است.</h3>
                <p class="text-xs sm:text-sm text-slate-500 mb-6">هنوز هیچ محصول یا سفارشی به سبد خرید خود اضافه نکرده‌اید.</p>
                <a href="{{ route('catalog.products.index') }}" wire:navigate
                   class="inline-flex rounded-xl bg-[#ffde5b] px-6 py-2.5 text-xs sm:text-sm font-bold text-[#010619] shadow-sm shadow-[#ffde5b]/25 hover:bg-[#f5d347] transition">
                    مشاهده فروشگاه و شروع خرید
                </a>
            </div>
        @else
            <div class="grid gap-8 lg:grid-cols-3">
                {{-- Items list --}}
                <div class="space-y-4 lg:col-span-2">
                    @foreach ($cart['items'] as $item)
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition hover:border-slate-300">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-slate-100">
                                <div class="space-y-2">
                                    <h3 class="text-base font-bold text-slate-900 break-words">
                                        {{ $item['product_name_snapshot'] }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        @if (! empty($item['color_name_snapshot']))
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-slate-700 font-medium">
                                                <span>رنگ:</span>
                                                <span class="font-bold">{{ $item['color_name_snapshot'] }}</span>
                                            </span>
                                        @endif
                                        @if (! empty($item['design_name_snapshot']))
                                            <span class="inline-flex items-center gap-1 rounded-lg bg-[#ffde5b]/20 text-[#664d00] border border-[#ffde5b]/50 px-2.5 py-1 font-bold">
                                                <span>طرح:</span>
                                                <span>{{ $item['design_name_snapshot'] }}</span>
                                            </span>
                                        @endif
                                        @if (! empty($item['design_image_path_snapshot']))
                                            <span class="text-slate-400 text-[11px] truncate max-w-xs" title="{{ $item['design_image_path_snapshot'] }}">
                                                {{ basename($item['design_image_path_snapshot']) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-start sm:text-end shrink-0">
                                    <div class="text-xs text-slate-500">قیمت نهایی آیتم</div>
                                    <div class="text-lg font-extrabold text-slate-900 mt-0.5">
                                        {{ number_format($item['final_price']) }}
                                        <span class="text-xs font-semibold text-slate-500">تومان</span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        واحد: {{ number_format($item['unit_price_snapshot']) }} تومان
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-1">
                                <form method="POST" action="{{ route('cart.update', $item['id']) }}" class="flex items-center gap-2">
                                    @csrf
                                    <label class="text-xs font-bold text-slate-600" for="quantity_{{ $item['id'] }}">تعداد:</label>
                                    <input id="quantity_{{ $item['id'] }}" name="quantity" type="number" min="1" max="20" value="{{ $item['quantity'] }}"
                                           dir="ltr"
                                           class="w-16 rounded-xl border border-slate-200 px-2.5 py-1.5 text-center text-sm font-bold text-slate-900 focus:border-[#010619] focus:ring-2 focus:ring-[#ffde5b]/60" />
                                    <button type="submit" class="rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition">
                                        به‌روزرسانی
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('cart.remove', $item['id']) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 px-3.5 py-1.5 text-xs font-bold transition">
                                        <x-icons.trash class="w-3.5 h-3.5" />
                                        <span>حذف</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Cart summary sidebar --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-24 rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xs space-y-5">
                        <h2 class="text-base font-bold text-slate-900 pb-3 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                            <span>خلاصه فاکتور سبد</span>
                        </h2>

                        <div class="space-y-3 text-sm text-slate-600">
                            <div class="flex items-center justify-between">
                                <span>تعداد کل اقلام:</span>
                                <span class="font-bold text-slate-900">{{ $cart['total_quantity'] }} عدد</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-slate-900 font-bold">مجموع مبلغ سبد:</span>
                                <div class="text-end">
                                    <span class="text-xl font-extrabold text-slate-900">{{ number_format($cart['total_price']) }}</span>
                                    <span class="text-xs font-semibold text-slate-500">تومان</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 space-y-3">
                            <a href="{{ route('checkout.index') }}" wire:navigate
                               class="w-full btn-brand-primary py-3.5 text-sm font-bold shadow-lg shadow-[#ffde5b]/25 hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2">
                                <span>ادامه به تسویه حساب</span>
                                <x-icons.arrow-left class="w-4 h-4" />
                            </a>

                            <form method="POST" action="{{ route('cart.empty') }}" data-confirm="آیا از پاک کردن تمام اقلام موجود در سبد خرید مطمئن هستید؟" data-confirm-title="خالی کردن سبد خرید" data-confirm-variant="warning" data-confirm-btn="بله، سبد خالی شود">
                                @csrf
                                <button type="submit" class="w-full rounded-xl border border-slate-200 bg-white hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200 py-2.5 text-xs font-bold text-slate-500 transition">
                                    پاک کردن سبد خرید
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection