<x-app-layout>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900">حساب کاربری</h1>
        <p class="mt-1 text-sm text-gray-600">سلام، {{ auth()->user()->displayName() }}؛ به حساب کاربری خود خوش آمدید.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">تعداد سفارش‌ها</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($orderCount) }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">مجموع خرید</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalSpent }} <span class="text-sm font-normal text-gray-500">تومان</span></p>
            </div>

            @if ($latestOrder)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">آخرین سفارش</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ $latestOrder->reference ?? ('#'.$latestOrder->id) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $latestOrder->status?->faLabel() ?? $latestOrder->status }} · {{ number_format($latestOrder->total_price) }} تومان</p>
                    <a href="{{ route('orders.show', $latestOrder) }}" wire:navigate class="mt-3 inline-block text-xs font-medium text-primary-600 hover:text-primary-800 transition">مشاهده جزئیات</a>
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">دسترسی سریع</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <a href="{{ route('orders.index') }}" wire:navigate class="rounded bg-primary-50 px-2 py-1 text-primary-700 hover:bg-primary-100 transition">سفارش‌های من</a>
                        <a href="{{ route('profile.edit') }}" wire:navigate class="rounded bg-primary-50 px-2 py-1 text-primary-700 hover:bg-primary-100 transition">پروفایل</a>
                        <a href="{{ route('catalog.products.index') }}" wire:navigate class="rounded bg-primary-50 px-2 py-1 text-primary-700 hover:bg-primary-100 transition">محصولات</a>
                        <a href="{{ route('catalog.designs.index') }}" wire:navigate class="rounded bg-primary-50 px-2 py-1 text-primary-700 hover:bg-primary-100 transition">طرح‌ها</a>
                        <a href="{{ route('cart.index') }}" wire:navigate class="rounded bg-primary-50 px-2 py-1 text-primary-700 hover:bg-primary-100 transition">سبد خرید</a>
                    </div>
                </div>
            @endif
        </div>

        <div class="mt-8 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-lg font-semibold text-gray-900">آخرین سفارش‌ها</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">شماره سفارش</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ ثبت</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">مبلغ کل</th>
                            <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($latestOrders as $order)
                            <tr>
                                <td class="px-4 py-3 text-gray-700">{{ $order->reference ?? ('#'.$order->id) }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">{{ $order->status?->faLabel() ?? $order->status }}</td>
                                <td class="px-4 py-3 font-mono" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('orders.show', $order) }}" wire:navigate class="text-xs text-primary-600 hover:text-primary-800 transition">جزئیات</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">هنوز سفارشی ثبت نکرده‌اید.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>