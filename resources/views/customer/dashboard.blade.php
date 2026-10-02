<x-app-layout>
    <div class="min-h-[70vh] bg-slate-50/50 py-10 sm:py-14">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="mb-8 border-b border-slate-200/80 pb-6">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ffde5b]/20 text-[#664d00] border border-[#ffde5b]/60 px-3 py-1 text-xs font-bold mb-2">
                    <span>حساب کاربری</span>
                </span>
                <h1 class="text-2xl font-black text-[#010619] sm:text-3xl">پیشخوان کاربری</h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-600">سلام، <strong class="text-[#010619]">{{ auth()->user()->displayName() }}</strong>؛ به پنل اختصاصی مشتریان خوش آمدید.</p>
            </div>

            {{-- Stat Cards --}}
            <div class="grid gap-5 sm:grid-cols-3">
                <div class="store-card p-6">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500">تعداد سفارش‌ها</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                            <x-icons.box class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-black text-[#010619]">{{ number_format($orderCount) }}</p>
                </div>

                <div class="store-card p-6">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500">مجموع خرید</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#ffde5b]/20 text-[#010619]">
                            <x-icons.wallet class="h-5 w-5" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-black text-[#010619]">{{ $totalSpent }} <span class="text-xs font-normal text-slate-500">تومان</span></p>
                </div>

                @if ($latestOrder)
                    <div class="store-card p-6">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500">آخرین سفارش</span>
                            <span class="rounded-lg bg-slate-100 px-2.5 py-0.5 font-mono text-xs font-bold text-slate-800">{{ $latestOrder->reference ?? ('#'.$latestOrder->id) }}</span>
                        </div>
                        <p class="mt-3 text-sm font-bold text-[#010619]">{{ number_format($latestOrder->total_price) }} <span class="text-xs font-normal text-slate-500">تومان</span></p>
                        <p class="mt-1 text-xs text-slate-500">{{ $latestOrder->status?->faLabel() ?? $latestOrder->status }}</p>
                    </div>
                @else
                    <div class="store-card p-6">
                        <span class="text-xs font-bold text-slate-500 block mb-2">دسترسی سریع</span>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <a href="{{ route('profile.edit') }}" wire:navigate class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-[#ffde5b] hover:text-[#010619] transition">پروفایل کاربری</a>
                            <a href="{{ route('catalog.products.index') }}" wire:navigate class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-[#ffde5b] hover:text-[#010619] transition">محصولات</a>
                            <a href="{{ route('custom-card.design') }}" wire:navigate class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-[#ffde5b] hover:text-[#010619] transition">طراحی اختصاصی</a>
                            <a href="{{ route('cart.index') }}" wire:navigate class="rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-[#ffde5b] hover:text-[#010619] transition">سبد خرید</a>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Recent Orders Table --}}
            <div class="mt-8 overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-xs">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-[#010619] flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#ffde5b]"></span>
                        <span>آخرین سفارش‌ها</span>
                    </h2>

                    <a href="{{ route('order-tracking.index') }}" wire:navigate class="text-xs font-bold text-slate-500 hover:text-[#010619] transition">
                        پیگیری سفارش با کد
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/70 border-b border-slate-100 text-xs font-bold text-slate-500">
                            <tr>
                                <th class="px-6 py-3.5 text-start">شماره سفارش</th>
                                <th class="px-6 py-3.5 text-start">تاریخ ثبت</th>
                                <th class="px-6 py-3.5 text-start">وضعیت</th>
                                <th class="px-6 py-3.5 text-start">مبلغ کل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($latestOrders as $order)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="px-6 py-4 font-mono font-bold text-xs text-[#010619]">{{ $order->reference ?? ('#'.$order->id) }}</td>
                                    <td class="px-6 py-4 text-slate-500 text-xs">{{ jalali_date($order->created_at, 'datetime') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-800">
                                            {{ $order->status?->faLabel() ?? $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold text-xs text-[#010619]">{{ number_format($order->total_price) }} <span class="text-xs font-normal text-slate-500 font-sans">تومان</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-xs">هنوز سفارشی در سیستم ثبت نکرده‌اید.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>