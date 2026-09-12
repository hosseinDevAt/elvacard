<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            سفارش‌های من
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">شماره سفارش</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ ثبت</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت سفارش</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت پرداخت</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">مبلغ کل</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">{{ $order->reference ?? ('#'.$order->id) }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ $order->status?->faLabel() ?? $order->status }}</td>
                                    <td class="px-4 py-3">{{ $order->payment_status?->faLabel() ?? $order->payment_status }}</td>
                                    <td class="px-4 py-3 font-mono" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('orders.show', $order) }}" class="text-primary-600 hover:text-primary-800 text-xs transition">مشاهده سفارش</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">سفارشی ثبت نشده است.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">{{ $orders->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>