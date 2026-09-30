<div>
    

    {{-- Date Filter --}}
    <div class="admin-card p-5 mb-6">
        <form wire:submit="applyFilter" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="fromDate" class="admin-label">از تاریخ</label>
                <x-jalali-date-input wire:model="fromDate" id="fromDate" />
                @error('fromDate')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="toDate" class="admin-label">تا تاریخ</label>
                <x-jalali-date-input wire:model="toDate" id="toDate" />
                @error('toDate')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-lg transition">
                اعمال فیلتر
            </button>
            <button type="button" wire:click="clearFilter"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition">
                پاک کردن فیلتر
            </button>
        </form>
    </div>

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">درآمد (پرداخت موفق)</div>
            <div class="text-2xl font-bold text-gray-900 font-mono">{{ number_format($summary['revenue'] ?? 0) }} تومان</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">کل سفارشات</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['totalOrders'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">سفارشات تکمیل شده</div>
            <div class="text-2xl font-bold text-green-700">{{ number_format($summary['completedOrders'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">سفارشات لغو شده</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($summary['cancelledOrders'] ?? 0) }}</div>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">پرداخت‌های موفق</div>
            <div class="text-2xl font-bold text-green-700">{{ number_format($summary['successfulPayments'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">پرداخت‌های در انتظار بررسی</div>
            <div class="text-2xl font-bold text-amber-600">{{ number_format($summary['pendingReviewPayments'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-sm text-gray-500 mb-1">مشتریان دارای سفارش</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($summary['activeCustomerCount'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Revenue Trend --}}
    @if(count($revenueTrend) > 0)
    <div class="admin-card p-5 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">روند درآمد</h2>
        <div class="space-y-2 max-h-80 overflow-y-auto">
            @foreach($revenueTrend as $bucket)
            <div class="flex items-center gap-3 text-sm">
                <span class="w-24 shrink-0 text-gray-500 text-left font-mono">{{ fa_digits($bucket['period']) }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                    <div class="bg-yellow-400 h-full rounded-full transition-all"
                         style="width: {{ $bucket['revenue'] > 0 ? max(1, (int) round(($bucket['revenue'] / $maxTrendRevenue) * 100)) : 0 }}%"></div>
                </div>
                <span class="w-36 text-left font-mono text-gray-700">{{ number_format($bucket['revenue']) }}</span>
                <span class="w-10 text-left text-gray-500">{{ $bucket['count'] }}</span>
            </div>
            @endforeach
        </div>
        <div class="mt-3 flex items-center gap-4 text-xs text-gray-400">
            <span>← مبلغ (تومان)</span>
            <span>تعداد پرداخت</span>
        </div>
    </div>
    @endif

    {{-- Top Products --}}
    <div class="admin-card p-5 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">محصولات پرفروش</h2>
        @if(empty($topProducts))
            <p class="text-sm text-gray-500">داده‌ای در این بازه زمانی وجود ندارد.</p>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="admin-th">#</th>
                        <th class="admin-th">نام محصول</th>
                        <th class="admin-th">تعداد فروش</th>
                        <th class="admin-th">مبلغ فروش</th>
                        <th class="admin-th">تعداد سفارش</th>
                        <th class="admin-th">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $idx => $product)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="admin-td text-gray-400">{{ $idx + 1 }}</td>
                        <td class="admin-td font-medium text-gray-900">{{ $product['product_name'] }}</td>
                        <td class="admin-td font-mono">{{ number_format($product['total_qty']) }}</td>
                        <td class="admin-td font-mono">{{ number_format($product['total_amount']) }}</td>
                        <td class="admin-td font-mono">{{ number_format($product['order_count']) }}</td>
                        <td class="admin-td">
                            @if($product['is_active'] === true)
                                <span class="admin-badge bg-green-100 text-green-700">فعال</span>
                            @elseif($product['is_active'] === false)
                                <span class="admin-badge bg-red-100 text-red-700">غیرفعال</span>
                            @else
                                <span class="admin-badge bg-gray-100 text-gray-500">حذف شده</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Payment Method Breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        @foreach($paymentMethodBreakdown as $methodKey => $method)
        <div class="admin-card p-5">
            <h3 class="font-bold text-gray-900 mb-3">{{ $method['label'] }}</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">درآمد موفق</span>
                    <span class="font-mono font-bold">{{ number_format($method['success_revenue']) }} تومان</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">تعداد پرداخت موفق</span>
                    <span class="font-mono">{{ number_format($method['success_count']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">در انتظار بررسی</span>
                    <span class="font-mono text-amber-600">{{ number_format($method['pending_review_count']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ناموفق</span>
                    <span class="font-mono text-red-600">{{ number_format($method['failed_count']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">لغو شده</span>
                    <span class="font-mono text-gray-400">{{ number_format($method['cancelled_count']) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Order Status Breakdown --}}
    <div class="admin-card p-5 mb-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">وضعیت سفارشات</h2>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            @foreach($orderStatusBreakdown as $statusKey => $status)
            <div class="text-center p-4 rounded-lg bg-gray-50 border border-gray-100">
                <div class="text-2xl font-bold font-mono text-gray-900">{{ number_format($status['count']) }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $status['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    @if(empty($summary))
    <div class="text-center py-12 text-gray-400">
        <p>داده‌ای برای نمایش وجود ندارد.</p>
    </div>
    @endif
</div>
