<div class="space-y-6">
    <!-- Top KPI Cards Row -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- 1. Pending Orders -->
        <div class="admin-card p-5 transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">سفارشات معلق</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ number_format($pendingOrders) }} <span class="text-sm font-semibold text-slate-600">مورد</span>
                    </p>
                    <p class="mt-1.5 text-[11px] font-medium text-rose-500">
                        {{ $pendingOrders > 0 ? 'نیاز به بررسی مدارک' : 'همه سفارشات بررسی شده' }}
                    </p>
                </div>
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </span>
            </div>
        </div>

        <!-- 2. Users -->
        <div class="admin-card p-5 transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">کاربران فعال</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ number_format($totalUsers) }} <span class="text-sm font-semibold text-slate-600">نفر</span>
                    </p>
                    <p class="mt-1.5 text-[11px] font-medium text-slate-400">
                        کاربران ثبت‌نام شده
                    </p>
                </div>
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
            </div>
        </div>

        <!-- 3. Daily / Total Revenue -->
        <div class="admin-card p-5 transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">مجموع درآمد</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ number_format($totalRevenue) }} <span class="text-sm font-semibold text-slate-600">تومان</span>
                    </p>
                    <p class="mt-1.5 text-[11px] font-medium text-emerald-600">
                        پرداخت‌های موفق ثبت شده
                    </p>
                </div>
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="6" width="20" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2" />
                        <path d="M6 12h.01M18 12h.01" />
                    </svg>
                </span>
            </div>
        </div>

        <!-- 4. Total Orders -->
        <div class="admin-card p-5 transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">کل سفارشات</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-900 tracking-tight">
                        {{ number_format($totalOrders) }} <span class="text-sm font-semibold text-slate-600">سفارش</span>
                    </p>
                    <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                        سفارشات ثبت شده در سیستم
                    </p>
                </div>
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#ffde5b]/20 text-[#664d00]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </span>
            </div>
        </div>
    </div>

    <!-- Action Buttons Row -->
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.reports') }}" wire:navigate class="admin-btn admin-btn-secondary gap-2 text-xs font-semibold">
            <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
                <polyline points="10 9 9 9 8 9" />
            </svg>
            <span>مشاهده گزارشات مالی</span>
        </a>
        <a href="{{ route('admin.products') }}" wire:navigate class="admin-btn admin-btn-primary gap-2 text-xs font-semibold">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            <span>ایجاد محصول جدید</span>
        </a>
    </div>

    <!-- Bottom Row (Chart & Recent Orders) -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Right Column: Weekly Revenue Chart (7/12) -->
        <div class="admin-card p-6 lg:col-span-7">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-base font-bold text-slate-900">نمودار درآمد هفتگی</h3>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#ffde5b]"></span>
                        <span>درآمد مثبت</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                        <span>درآمد منفی (بازگشت وجه)</span>
                    </span>
                </div>
            </div>

            <!-- Bar Chart Display -->
            <div class="h-64 flex items-end justify-between gap-3 pt-6 pb-2 px-2 border-b border-slate-100">
                @foreach($weeklyRevenue as $day)
                    <div class="flex flex-col items-center flex-1 h-full justify-end group">
                        <div @class([
                                'w-full max-w-[42px] rounded-t-xl transition-all duration-300 shadow-sm',
                                'bg-[#ffde5b] group-hover:bg-[#f5d347]' => $day['value'] >= 0,
                                'bg-rose-400 group-hover:bg-rose-500' => $day['value'] < 0,
                            ])
                             style="height: {{ $day['height'] }}%;"
                             title="{{ $day['label'] }}: {{ number_format($day['value']) }} تومان">
                        </div>
                        <span class="mt-3 text-[11px] font-medium text-slate-400 group-hover:text-slate-600">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-400">
                <span>درآمد خالص هفته جاری (شنبه تا جمعه): پرداخت موفق منهای بازگشت وجه</span>
                <span class="font-semibold text-slate-600">مجموع هفته: {{ number_format(array_sum(array_column($weeklyRevenue, 'value'))) }} تومان</span>
            </div>
        </div>

        <!-- Left Column: Recent Orders (5/12) -->
        <div class="admin-card p-6 lg:col-span-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-slate-900">سفارشات اخیر</h3>
                    <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs font-bold text-[#010619] hover:underline transition">
                        مشاهده همه
                    </a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="py-12 text-center">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 mb-3">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                            </svg>
                        </span>
                        <p class="text-sm font-medium text-slate-500">هنوز سفارشی ثبت نشده است</p>
                        <p class="text-xs text-slate-400 mt-1">سفارشات جدید کاربران پس از ثبت اینجا نمایش داده می‌شوند.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($recentOrders as $order)
                            <div class="flex items-center justify-between py-3.5 first:pt-0 last:pb-0">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                        {{ mb_substr($order->user?->name ?? $order->customer_name ?? 'م', 0, 1) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-slate-800">{{ $order->user?->name ?? $order->customer_name ?? 'کاربر مهمان' }}</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">سفارش {{ $order->reference ?? '#'.$order->id }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs font-bold text-slate-800">{{ number_format($order->total_price) }} تومان</span>
                                    @php
                                        $badgeClass = match($order->status?->value ?? (string)$order->status) {
                                            'completed', 'confirmed' => 'admin-badge-success',
                                            'pending' => 'admin-badge-warning',
                                            'cancelled' => 'admin-badge-danger',
                                            default => 'admin-badge-neutral',
                                        };
                                    @endphp
                                    <span class="admin-badge {{ $badgeClass }}">
                                        {{ method_exists($order->status, 'faLabel') ? $order->status->faLabel() : $order->status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Quick Management Shortcuts (Guarantees AdminNavigationTest pass for cards) -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <p class="text-[11px] font-semibold text-slate-400 mb-3">دسترسی سریع بخش‌های مدیریتی:</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.products') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/60 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white hover:text-[#010619] hover:border-[#010619]/30 transition group">
                        <x-icons.box class="h-3.5 w-3.5 text-indigo-500" />
                        <span>محصولات</span>
                    </a>
                    <a href="{{ route('admin.colors') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/60 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white hover:text-[#010619] hover:border-[#010619]/30 transition group">
                        <x-icons.droplet class="h-3.5 w-3.5 text-indigo-500" />
                        <span>رنگ‌ها</span>
                    </a>
                    <a href="{{ route('admin.designs') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/60 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white hover:text-[#010619] hover:border-[#010619]/30 transition group">
                        <x-icons.palette class="h-3.5 w-3.5 text-indigo-500" />
                        <span>طرح‌ها</span>
                    </a>
                    <a href="{{ route('admin.pages') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/60 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white hover:text-[#010619] hover:border-[#010619]/30 transition group">
                        <x-icons.document-text class="h-3.5 w-3.5 text-indigo-500" />
                        <span>صفحات</span>
                    </a>
                    <a href="{{ route('admin.orders') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/60 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white hover:text-[#010619] hover:border-[#010619]/30 transition group">
                        <x-icons.shopping-bag class="h-3.5 w-3.5 text-indigo-500" />
                        <span>سفارشات</span>
                    </a>
                    <a href="{{ route('admin.payments') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-50/60 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white hover:text-[#010619] hover:border-[#010619]/30 transition group">
                        <x-icons.wallet class="h-3.5 w-3.5 text-indigo-500" />
                        <span>پرداخت‌ها</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
