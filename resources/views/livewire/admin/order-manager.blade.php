<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت سفارشات</h1>
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('statusFilter', null)" class="px-3 py-1.5 rounded-lg text-xs transition {{ !$statusFilter ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">همه</button>
            <button wire:click="$set('statusFilter', 'pending')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">در انتظار</button>
            <button wire:click="$set('statusFilter', 'confirmed')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'confirmed' ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">تأیید شده</button>
            <button wire:click="$set('statusFilter', 'processing')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">در حال پردازش</button>
            <button wire:click="$set('statusFilter', 'completed')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'completed' ? 'bg-green-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">تکمیل شده</button>
            <button wire:click="$set('statusFilter', 'cancelled')" class="px-3 py-1.5 rounded-lg text-xs transition {{ $statusFilter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">لغو شده</button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($selectedOrder)
        <div class="mb-6 rounded-xl border border-gray-200 bg-white overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 min-w-0">جزئیات سفارش <span class="text-gray-500 max-w-[160px] truncate" dir="ltr" title="{{ $selectedOrder->reference }}">{{ $selectedOrder->reference }}</span></h2>
                <button wire:click="closeOrderDetail" class="px-3 py-1.5 rounded-lg text-xs bg-gray-100 text-gray-600 hover:bg-gray-200">بازگشت به لیست</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 px-6 py-4 text-sm">
                <div>
                    <div class="text-gray-400 text-xs">مشتری</div>
                    <div class="text-gray-900">{{ $selectedOrder->user->name ?? $selectedOrder->customer_name }}</div>
                    <div class="text-gray-500 text-xs" dir="ltr">{{ $selectedOrder->user->phone ?? $selectedOrder->customer_phone }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">مبلغ</div>
                    <div class="text-gray-900 font-mono">{{ number_format($selectedOrder->total_price) }} تومان</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">وضعیت</div>
                    <div class="text-gray-900">{{ $selectedOrder->status->faLabel() }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">وضعیت پرداخت</div>
                    <div class="text-gray-900">{{ $selectedOrder->payment_status->faLabel() }}</div>
                </div>
            </div>

            @php
                $manualPayments = $selectedOrder->payments
                    ->where('method', \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                    ->sortByDesc('created_at')
                    ->values();
            @endphp

            @if ($manualPayments->isNotEmpty())
                @foreach ($manualPayments as $index => $manualPayment)
                    <div class="px-6 py-4 border-t border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-3">پرداخت #{{ $manualPayments->count() - $index }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-gray-400 text-xs">روش پرداخت</div>
                                <div class="text-gray-900">{{ $manualPayment->method->faLabel() }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">مبلغ</div>
                                <div class="text-gray-900 font-mono">{{ number_format($manualPayment->amount) }} تومان</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">وضعیت</div>
                                <div class="text-gray-900">{{ $manualPayment->status->faLabel() }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">کد رهگیری</div>
                                <div class="text-gray-900 break-all" dir="ltr">{{ $manualPayment->tracking_code ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">توضیح مشتری</div>
                                <div class="text-gray-900">{{ $manualPayment->metadata['note'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">تاریخ پرداخت</div>
                                <div class="text-gray-900">{{ $manualPayment->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                        </div>

                        @if ($manualPayment->receipt_path)
                            <div class="mt-4">
                                <div class="text-gray-400 text-xs mb-2">رسید</div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.payments.receipt', ['order' => $selectedOrder, 'payment' => $manualPayment]) }}" target="_blank" class="px-3 py-1.5 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">مشاهده رسید</a>
                                    <a href="{{ route('admin.payments.receipt', ['order' => $selectedOrder, 'payment' => $manualPayment, 'download' => 1]) }}" class="px-3 py-1.5 rounded-lg text-xs bg-gray-800 hover:bg-gray-900 text-white">دانلود رسید</a>
                                </div>
                            </div>
                        @endif

                        @if ($manualPayment->status === \App\Enums\PaymentStatus::PENDING_REVIEW)
                            <div class="mt-4 flex items-center gap-2">
                                <button wire:click="approvePayment({{ $manualPayment->id }})" wire:confirm="آیا از تأیید این پرداخت مطمئن هستید؟" class="px-4 py-2 rounded-lg text-sm bg-green-600 hover:bg-green-700 text-white">تایید پرداخت</button>
                                <button wire:click="rejectPayment({{ $manualPayment->id }})" wire:confirm="آیا از رد این پرداخت مطمئن هستید؟" class="px-4 py-2 rounded-lg text-sm bg-red-600 hover:bg-red-700 text-white">رد پرداخت</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="px-6 py-4 border-t border-gray-100">
                    <div class="text-gray-400 text-sm">پرداختی برای این سفارش ثبت نشده است.</div>
                </div>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">#</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">کاربر</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">مبلغ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تاریخ</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">جزئیات</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">تغییر وضعیت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-100 text-amber-700',
                            'confirmed' => 'bg-teal-100 text-teal-700',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $order->reference ?? $order->id }}</td>
                        <td class="px-4 py-3">{{ $order->user->name ?? ($order->customer_name ?? '-') }} <span class="text-gray-400 text-xs">({{ $order->user->phone ?? ($order->customer_phone ?? '-') }})</span></td>
                        <td class="px-4 py-3 font-mono" dir="ltr">{{ number_format($order->total_price) }} تومان</td>
                        <td class="px-4 py-3">
                            <span class="{{ $statusColors[$order->status->value] ?? 'bg-gray-100 text-gray-700' }} text-xs px-2 py-1 rounded-full">
                                {{ $order->status->faLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="viewOrder({{ $order->id }})" class="text-xs px-2 py-1 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white">جزئیات</button>
                        </td>
                        <td class="px-4 py-3">
                            @php $targets = $transitions[$order->id] ?? []; @endphp
                            @if (empty($targets))
                                <span class="text-gray-400 text-xs">—</span>
                            @else
                                <select wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                                    class="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:border-yellow-500">
                                    <option value="">تغییر وضعیت...</option>
                                    @foreach ($targets as $target)
                                        <option value="{{ $target['value'] }}">{{ $target['label'] }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">سفارشی وجود ندارد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</div>