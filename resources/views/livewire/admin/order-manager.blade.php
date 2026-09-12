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

            {{-- Order Items & Customization Snapshot --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 space-y-4">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    آیتم‌ها و مشخصات سفارشی‌سازی کارت (Snapshot)
                </h3>

                @foreach ($selectedOrder->items as $item)
                    @php
                        $custom = is_array($item->customization_json) ? $item->customization_json : [];
                        $positions = is_array($custom['positions'] ?? null) ? $custom['positions'] : [];
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                            <div>
                                <span class="font-bold text-gray-900 text-sm">{{ $item->product_name_snapshot }}</span>
                                <span class="text-xs text-gray-500 ms-2">(تعداد: {{ $item->quantity }})</span>
                            </div>
                            <div class="text-xs font-mono font-bold text-amber-600">
                                قیمت واحد: {{ number_format($item->unit_price_snapshot) }} تومان | جمع: {{ number_format($item->final_price) }} تومان
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            {{-- Details List --}}
                            <div class="space-y-1.5 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <div class="font-bold text-gray-700 mb-1 border-b border-gray-200 pb-1">پارامترهای حکاکی کاربر:</div>
                                <div><span class="text-gray-400">شماره کارت:</span> <span class="font-mono text-gray-900 font-bold" dir="ltr">{{ !empty($custom['card_number']) ? \App\Livewire\Catalog\ProductCustomizer::presentCardNumber($custom['card_number']) : 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">نام دارنده کارت:</span> <span class="text-gray-900 font-bold">{{ $custom['card_holder_name'] ?? 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">متن دلخواه پشت:</span> <span class="text-gray-900 font-bold">{{ $custom['back_text'] ?? 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">وضعیت CVV2:</span> <span class="text-gray-900 font-bold">{{ !empty($custom['security_cvv_enabled']) ? 'فعال (مقدار: ' . ($custom['cvv2'] ?? 'مشخص نشده') . ')' : 'غیرفعال' }}</span></div>
                                <div><span class="text-gray-400">وضعیت تاریخ انقضا:</span> <span class="text-gray-900 font-bold">{{ !empty($custom['security_expiry_enabled']) ? 'فعال (تاریخ: ' . ($custom['expiry_month'] ?? '--') . '/' . ($custom['expiry_year'] ?? '--') . ')' : 'غیرفعال' }}</span></div>
                                <div><span class="text-gray-400">وضعیت QR Code:</span> <span class="text-gray-900 font-bold">{{ !empty($custom['qr_code_enabled']) ? 'فعال' : 'غیرفعال' }}</span></div>
                                @if (!empty($custom['qr_code_path']))
                                    <div class="mt-2 pt-2 border-t border-gray-200 flex items-center gap-2">
                                        <span class="text-gray-400">تصویر QR:</span>
                                        <a href="{{ route('admin.orders.qr.show', ['path' => $custom['qr_code_path']]) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                            <img src="{{ route('admin.orders.qr.show', ['path' => $custom['qr_code_path']]) }}" alt="QR" class="h-6 w-6 object-contain bg-white border rounded">
                                            <span>مشاهده تصویر QR کامل</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Mini Visual Snapshot Card Preview --}}
                            <div class="bg-gray-900 text-amber-100 rounded-lg p-3 relative h-40 border border-gray-800 flex flex-col justify-between overflow-hidden select-none" dir="ltr">
                                <div class="text-[9px] font-mono text-gray-400 mb-1">2D Snapshot Preview:</div>
                                <div class="absolute top-7 inset-x-0 h-6 bg-gray-950 shadow-inner"></div>

                                <div class="relative h-full w-full mt-4 text-[10px]">
                                    @if (!empty($custom['card_number']))
                                        <div class="absolute font-mono font-bold" style="left: {{ ($positions['card_number']['x'] ?? 0.08) * 100 }}%; top: {{ ($positions['card_number']['y'] ?? 0.42) * 100 }}%;">
                                            {{ \App\Livewire\Catalog\ProductCustomizer::presentCardNumber($custom['card_number']) }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['card_holder_name']))
                                        <div class="absolute font-serif italic bg-white/90 text-gray-900 px-1 rounded text-[9px] font-bold" style="left: {{ ($positions['card_holder_name']['x'] ?? 0.08) * 100 }}%; top: {{ ($positions['card_holder_name']['y'] ?? 0.78) * 100 }}%;">
                                            {{ $custom['card_holder_name'] }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['back_text']))
                                        <div class="absolute italic text-[9px]" style="left: {{ ($positions['back_text']['x'] ?? 0.08) * 100 }}%; top: {{ ($positions['back_text']['y'] ?? 0.62) * 100 }}%;">
                                            {{ $custom['back_text'] }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['security_cvv_enabled']) && !empty($custom['cvv2']))
                                        <div class="absolute font-mono text-[9px]" style="left: {{ ($positions['cvv2']['x'] ?? 0.72) * 100 }}%; top: {{ ($positions['cvv2']['y'] ?? 0.78) * 100 }}%;">
                                            CVV2: {{ $custom['cvv2'] }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['security_expiry_enabled']) && (!empty($custom['expiry_month']) || !empty($custom['expiry_year'])))
                                        <div class="absolute font-mono text-[9px]" style="left: {{ ($positions['expiry']['x'] ?? 0.48) * 100 }}%; top: {{ ($positions['expiry']['y'] ?? 0.78) * 100 }}%;">
                                            EXP: {{ $custom['expiry_month'] ?? '--' }}/{{ $custom['expiry_year'] ?? '--' }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['qr_code_enabled']) && !empty($custom['qr_code_path']))
                                        <div class="absolute" style="left: {{ ($positions['qr_code']['x'] ?? 0.76) * 100 }}%; top: {{ ($positions['qr_code']['y'] ?? 0.15) * 100 }}%;">
                                            <img src="{{ route('admin.orders.qr.show', ['path' => $custom['qr_code_path']]) }}" class="h-6 w-6 bg-white p-0.5 rounded">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
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