<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت کاربران</h1>
        <div class="w-full sm:w-72">
            <input type="text" wire:model.live="search" placeholder="جستجو بر اساس نام یا شماره..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition text-sm">
        </div>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($selectedUser)
        <div class="mb-6 rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 min-w-0">مشخصات مشتری <span class="text-gray-500">{{ $selectedUser->displayName() }}</span></h2>
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
                    <div class="text-gray-900">{{ $selectedUser->created_at->format('Y-m-d H:i') }}</div>
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
                            {{ $customerStats['lastOrderAt'] ? \Illuminate\Support\Carbon::parse($customerStats['lastOrderAt'])->format('Y-m-d') : '—' }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="px-6 py-4">
                <h3 class="font-bold text-gray-900 text-sm mb-3">سابقه سفارشات کاربر</h3>
                <div class="rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">کد سفارش</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت پرداخت</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">مبلغ</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentOrders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs" dir="ltr">{{ $order->reference }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs px-2 py-1 rounded-full {{ $order->status->value === 'completed' ? 'bg-green-100 text-green-800' : ($order->status->value === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $order->status->faLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs">{{ $order->payment_status->faLabel() }}</td>
                                    <td class="px-4 py-3 font-mono text-xs" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">سفارشی برای این کاربر ثبت نشده است</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">نام</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تلفن</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">آدرس</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تعداد سفارش</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ عضویت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">جزئیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $user->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 font-mono" dir="ltr">{{ $user->phone }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs max-w-[200px] truncate">{{ $user->address ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $user->orders_count }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $user->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="viewUser({{ $user->id }})" class="text-xs px-2 py-1 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white">جزئیات</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">کاربری یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $users->links() }}</div>
    </div>
</div>