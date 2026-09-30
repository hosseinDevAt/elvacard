<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">مدیریت کاربران سیستم</h2>
            <p class="text-xs text-slate-500 mt-1">مشاهده و مدیریت کاربران، بررسی سفارشات و وضعیت حساب کاربری</p>
        </div>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="mb-6 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <x-icons.check-badge class="mt-0.5 shrink-0 text-green-600" />
            <div class="min-w-0">{{ session('success') }}</div>
        </div>
    @endif

    @if ($selectedUser)
        <div class="mb-6 admin-card overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 min-w-0">مشخصات مشتری <span class="text-gray-500">{{ $selectedUser->displayName() }}</span>
                    @if ($selectedUser->is_active)
                        <span class="admin-badge ms-2 bg-green-100 text-green-800">فعال</span>
                    @else
                        <span class="admin-badge ms-2 bg-red-100 text-red-700">مسدود</span>
                    @endif
                </h2>
                <button wire:click="closeUserDetail" class="px-3 py-1.5 rounded-lg text-xs bg-gray-100 text-gray-600 hover:bg-gray-200">بازگشت به لیست</button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 px-6 py-4 text-sm border-b border-gray-100">
                <div>
                    <div class="text-gray-400 text-xs">نام</div>
                    <div class="text-gray-900">{{ $selectedUser->displayName() }}</div>
                    <div class="text-gray-500 text-xs" dir="ltr">{{ $selectedUser->name }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">تلفن</div>
                    <div class="text-gray-900 font-mono" dir="ltr">{{ $selectedUser->phone }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">ایمیل</div>
                    <div class="text-gray-900 text-xs break-all" dir="ltr">{{ $selectedUser->email ?? 'ثبت نشده' }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">تاریخ عضویت</div>
                    <div class="text-gray-900">{{ jalali_date($selectedUser->created_at, 'datetime') }}</div>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-gray-400 text-xs">آدرس</div>
                        <div class="text-gray-900">{{ $selectedUser->address ?? 'ثبت نشده' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">پلاک</div>
                        <div class="text-gray-900">{{ $selectedUser->plaque ?? 'ثبت نشده' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">کد پستی</div>
                        <div class="text-gray-900 font-mono" dir="ltr">{{ $selectedUser->postal_code ?? 'ثبت نشده' }}</div>
                    </div>
                </div>
            </div>

            @if ($customerStats)
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 px-6 py-4 border-b border-gray-100">
                    <div class="rounded-xl bg-amber-50 border border-amber-100 p-4">
                        <div class="text-xs text-gray-500 mb-1">کل سفارشات</div>
                        <div class="text-xl font-bold text-gray-900">{{ number_format($customerStats['totalOrders']) }}</div>
                    </div>
                    <div class="rounded-xl bg-green-50 border border-green-100 p-4">
                        <div class="text-xs text-gray-500 mb-1">سفارشات پرداخت‌شده</div>
                        <div class="text-xl font-bold text-gray-900">{{ number_format($customerStats['paidOrders']) }}</div>
                    </div>
                    <div class="rounded-xl bg-teal-50 border border-teal-100 p-4">
                        <div class="text-xs text-gray-500 mb-1">مجموع خرید</div>
                        <div class="text-xl font-bold text-gray-900 font-mono">{{ number_format($customerStats['totalSpent']) }} تومان</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                        <div class="text-xs text-gray-500 mb-1">آخرین سفارش</div>
                        <div class="text-xl font-bold text-gray-900 text-sm leading-8">
                            {{ $customerStats['lastOrderAt'] ? jalali_date($customerStats['lastOrderAt'], 'date') : '—' }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-sm mb-3">وضعیت حساب</h3>

                @if ($selectedUser->is_active)
                    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">دلیل مسدودسازی (اختیاری)</label>
                            <textarea wire:model="blockReason" rows="2" maxlength="255"
                                class="admin-input"
                                placeholder="اختیاری"></textarea>
                            @error('blockReason')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="button" wire:click="blockUser({{ $selectedUser->id }})"
                            wire:confirm="آیا از مسدودسازی حساب این کاربر مطمئن هستید؟"
                            class="px-4 py-2 rounded-lg text-sm bg-red-600 hover:bg-red-700 text-white">مسدودسازی حساب</button>
                    </div>
                @else
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex-1 text-sm">
                            <div class="text-red-700">حساب این کاربر مسدود است.</div>
                            @if ($selectedUser->blocked_at)
                                <div class="text-gray-600 mt-1">تاریخ مسدودی: {{ jalali_date($selectedUser->blocked_at, 'datetime') }}</div>
                            @endif
                            @if ($selectedUser->blocked_reason)
                                <div class="text-gray-600 mt-1">دلیل: {{ $selectedUser->blocked_reason }}</div>
                            @endif
                        </div>
                        <button type="button" wire:click="unblockUser({{ $selectedUser->id }})"
                            wire:confirm="آیا از رفع مسدودی حساب این کاربر مطمئن هستید؟"
                            class="px-4 py-2 rounded-lg text-sm bg-green-600 hover:bg-green-700 text-white">رفع مسدودی</button>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4">
                <h3 class="font-bold text-gray-900 text-sm mb-3">سابقه سفارشات کاربر</h3>
                <div class="rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="admin-th">کد سفارش</th>
                                <th class="admin-th">وضعیت</th>
                                <th class="admin-th">وضعیت پرداخت</th>
                                <th class="admin-th">مبلغ</th>
                                <th class="admin-th">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentOrders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="admin-td font-mono text-xs" dir="ltr">{{ $order->reference }}</td>
                                    <td class="admin-td">
                                        <span class="admin-badge {{ $order->status->value === 'completed' ? 'bg-green-100 text-green-800' : ($order->status->value === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $order->status->faLabel() }}
                                        </span>
                                    </td>
                                    <td class="admin-td text-xs">{{ $order->payment_status->faLabel() }}</td>
                                    <td class="admin-td font-mono text-xs" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                                    <td class="admin-td text-xs text-gray-500">{{ jalali_date($order->created_at, 'datetime') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="admin-empty">سفارشی برای این کاربر ثبت نشده است</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100">
                <h3 class="font-bold text-gray-900 text-sm mb-3">سفارشات مهمان با این شماره تماس</h3>
                <div class="rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="admin-th">کد سفارش</th>
                                <th class="admin-th">نوع حساب</th>
                                <th class="admin-th">وضعیت</th>
                                <th class="admin-th">وضعیت پرداخت</th>
                                <th class="admin-th">مبلغ</th>
                                <th class="admin-th">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($guestOrders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="admin-td font-mono text-xs" dir="ltr">{{ $order->reference }}</td>
                                    <td class="admin-td">
                                        <span class="admin-badge bg-amber-100 text-amber-800">مهمان</span>
                                    </td>
                                    <td class="admin-td">
                                        <span class="admin-badge {{ $order->status->value === 'completed' ? 'bg-green-100 text-green-800' : ($order->status->value === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $order->status->faLabel() }}
                                        </span>
                                    </td>
                                    <td class="admin-td text-xs">{{ $order->payment_status->faLabel() }}</td>
                                    <td class="admin-td font-mono text-xs" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                                    <td class="admin-td text-xs text-gray-500">{{ jalali_date($order->created_at, 'datetime') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="admin-empty">سفارش مهمانی با این شماره تماس یافت نشد</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="admin-card overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900">لیست کاربران سیستم</h3>
                <p class="text-xs text-slate-400 mt-0.5">مشاهده مشخصات، نقش‌ها و فعالیت کاربران</p>
            </div>
            <div class="w-full sm:w-72">
                <input type="text" wire:model.live="search" placeholder="جستجوی کاربر..."
                       class="admin-input py-2 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-16">#</th>
                        <th class="admin-th">نام کاربر</th>
                        <th class="admin-th">ایمیل</th>
                        <th class="admin-th">تلفن همراه</th>
                        <th class="admin-th">عضویت</th>
                        <th class="admin-th">نقش / وضعیت</th>
                        <th class="admin-th text-center">جزئیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td text-xs text-slate-400 font-mono">{{ $user->id }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                        {{ mb_substr($user->name ?? 'ک', 0, 1) }}
                                    </span>
                                    <div>
                                        <span class="font-bold text-slate-900 block text-xs">{{ $user->name }}</span>
                                        @if($user->address)
                                            <span class="text-[11px] text-slate-400 max-w-[160px] truncate block">{{ $user->address }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="admin-td text-xs text-slate-500 font-mono" dir="ltr">{{ $user->email ?? '-' }}</td>
                            <td class="admin-td font-mono text-xs text-slate-600" dir="ltr">{{ $user->phone }}</td>
                            <td class="admin-td text-slate-400 text-xs">{{ jalali_relative($user->created_at) }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-1.5">
                                    <span class="admin-badge {{ $user->role === 'admin' ? 'admin-badge-info' : 'admin-badge-neutral' }}">
                                        {{ $user->role === 'admin' ? 'مدیر' : 'مشتری' }}
                                    </span>
                                    <span class="admin-badge {{ $user->is_active ? 'admin-badge-success' : 'admin-badge-danger' }}">
                                        {{ $user->is_active ? 'فعال' : 'مسدود' }}
                                    </span>
                                </div>
                            </td>
                            <td class="admin-td text-center">
                                <button wire:click="viewUser({{ $user->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">
                                    جزئیات
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-empty">کاربری یافت نشد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $users->links() }}</div>
    </div>
</div>