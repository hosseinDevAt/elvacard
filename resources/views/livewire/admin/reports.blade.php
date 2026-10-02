<div>
    <x-admin.page-header
        title="گزارشات و تحلیل عملکرد"
        description="تحلیل جامع درآمد، وضعیت سفارشات، پرداخت‌ها و آمار محصولات پرفروش"
    />

    {{-- Date Filter --}}
    <div class="admin-card p-5 mb-6">
        <div class="flex flex-wrap items-center gap-2 mb-4 text-xs">
            <span class="text-slate-500 font-semibold">بازه زمانی سریع:</span>
            <button type="button" wire:click="setPreset('today')" class="admin-btn admin-btn-secondary admin-btn-sm text-xs">امروز</button>
            <button type="button" wire:click="setPreset('yesterday')" class="admin-btn admin-btn-secondary admin-btn-sm text-xs">دیروز</button>
            <button type="button" wire:click="setPreset('7days')" class="admin-btn admin-btn-secondary admin-btn-sm text-xs">۷ روز اخیر</button>
            <button type="button" wire:click="setPreset('30days')" class="admin-btn admin-btn-secondary admin-btn-sm text-xs">۳۰ روز اخیر</button>
        </div>

        <form wire:submit="applyFilter" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="fromDate" class="admin-label">از تاریخ</label>
                <x-jalali-date-input wire:model="fromDate" id="fromDate" />
                @error('fromDate')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="toDate" class="admin-label">تا تاریخ</label>
                <x-jalali-date-input wire:model="toDate" id="toDate" />
                @error('toDate')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm font-semibold">
                    اعمال فیلتر
                </button>
                <button type="button" wire:click="clearFilter" class="admin-btn admin-btn-secondary admin-btn-sm">
                    پاک کردن فیلتر
                </button>
            </div>
        </form>
    </div>

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">درآمد (پرداخت موفق)</div>
            <div class="text-xl font-extrabold text-[#010619] font-mono" dir="ltr">{{ number_format($summary['revenue'] ?? 0) }} تومان</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">کل سفارشات</div>
            <div class="text-xl font-extrabold text-slate-900">{{ number_format($summary['totalOrders'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">سفارشات تکمیل شده</div>
            <div class="text-xl font-extrabold text-emerald-600">{{ number_format($summary['completedOrders'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">سفارشات لغو شده</div>
            <div class="text-xl font-extrabold text-rose-600">{{ number_format($summary['cancelledOrders'] ?? 0) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">پرداخت‌های موفق</div>
            <div class="text-xl font-extrabold text-emerald-600">{{ number_format($summary['successfulPayments'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">پرداخت‌های در انتظار بررسی</div>
            <div class="text-xl font-extrabold text-amber-600">{{ number_format($summary['pendingReviewPayments'] ?? 0) }}</div>
        </div>
        <div class="admin-card p-5">
            <div class="text-xs text-slate-500 font-medium mb-1">مشتریان دارای سفارش</div>
            <div class="text-xl font-extrabold text-slate-900">{{ number_format($summary['activeCustomerCount'] ?? 0) }}</div>
        </div>
    </div>

    {{-- Revenue Trend --}}
    @if(count($revenueTrend) > 0)
    <div class="admin-card p-5 mb-8">
        <h3 class="text-base font-extrabold text-slate-900 mb-4">روند درآمد در بازه زمانی</h3>
        <div class="space-y-2.5 max-h-80 overflow-y-auto">
            @foreach($revenueTrend as $bucket)
            <div class="flex items-center gap-3 text-xs">
                <span class="w-24 shrink-0 text-slate-500 text-left font-mono">{{ fa_digits($bucket['period']) }}</span>
                <div class="flex-1 bg-slate-100 rounded-full h-4 overflow-hidden">
                    <div class="bg-[#ffde5b] h-full rounded-full transition-all shadow-sm"
                         style="width: {{ $bucket['revenue'] > 0 ? max(1, (int) round(($bucket['revenue'] / $maxTrendRevenue) * 100)) : 0 }}%"></div>
                </div>
                <span class="w-36 text-left font-mono font-bold text-slate-700" dir="ltr">{{ number_format($bucket['revenue']) }} تومان</span>
                <span class="w-16 text-left font-mono text-slate-400">({{ $bucket['count'] }} تراکنش)</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Top Products --}}
    <div class="admin-card overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-100">
            <h3 class="text-base font-extrabold text-slate-900">محصولات پرفروش</h3>
        </div>
        @if(empty($topProducts))
            <div class="p-6">
                <x-admin.empty-state title="داده‌ای وجود ندارد" description="در بازه زمانی انتخابی، فروشی برای محصولات ثبت نشده است." />
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">نام محصول</th>
                        <th class="admin-th">تعداد فروش</th>
                        <th class="admin-th">مبلغ فروش</th>
                        <th class="admin-th">تعداد سفارش</th>
                        <th class="admin-th">وضعیت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($topProducts as $idx => $product)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="admin-td text-xs text-slate-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="admin-td font-bold text-slate-900">{{ $product['product_name'] }}</td>
                        <td class="admin-td font-mono font-bold text-slate-700">{{ number_format($product['total_qty']) }}</td>
                        <td class="admin-td font-mono font-extrabold text-[#010619]" dir="ltr">{{ number_format($product['total_amount']) }} تومان</td>
                        <td class="admin-td font-mono text-slate-600">{{ number_format($product['order_count']) }}</td>
                        <td class="admin-td">
                            @if($product['is_active'] === true)
                                <span class="admin-badge admin-badge-success">فعال</span>
                            @elseif($product['is_active'] === false)
                                <span class="admin-badge admin-badge-danger">غیرفعال</span>
                            @else
                                <span class="admin-badge admin-badge-neutral">حذف شده</span>
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
            <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-slate-900 text-sm">{{ $method['label'] }}</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">درآمد موفق</span>
                    <span class="font-mono font-extrabold text-emerald-600" dir="ltr">{{ number_format($method['success_revenue']) }} تومان</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">تعداد پرداخت موفق</span>
                    <span class="font-mono font-bold text-slate-800">{{ number_format($method['success_count']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">در انتظار بررسی</span>
                    <span class="font-mono font-bold text-amber-600">{{ number_format($method['pending_review_count']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">ناموفق</span>
                    <span class="font-mono font-bold text-rose-600">{{ number_format($method['failed_count']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">لغو شده</span>
                    <span class="font-mono text-slate-400">{{ number_format($method['cancelled_count']) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Order Status Breakdown --}}
    <div class="admin-card p-5 mb-8">
        <h3 class="text-base font-extrabold text-slate-900 mb-4">وضعیت سفارشات</h3>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            @foreach($orderStatusBreakdown as $statusKey => $status)
            <div class="text-center p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div class="text-2xl font-extrabold font-mono text-slate-900">{{ number_format($status['count']) }}</div>
                <div class="text-xs font-medium text-slate-500 mt-1">{{ $status['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    @if(empty($summary))
    <div class="text-center py-12 text-slate-400">
        <p>داده‌ای برای نمایش وجود ندارد.</p>
    </div>
    @endif
</div>
