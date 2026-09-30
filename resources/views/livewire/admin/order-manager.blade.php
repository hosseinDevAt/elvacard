<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900">مدیریت سفارشات کاربران</h2>
            <p class="text-xs text-slate-500 mt-1">مشاهده سفارشات، بررسی فیش‌های واریزی و تغییر وضعیت سفارش</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('statusFilter', null)" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ !$statusFilter ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">همه</button>
            <button wire:click="$set('statusFilter', 'pending')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/20' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">معلق / در انتظار</button>
            <button wire:click="$set('statusFilter', 'confirmed')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'confirmed' ? 'bg-indigo-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">تأیید شده</button>
            <button wire:click="$set('statusFilter', 'processing')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'processing' ? 'bg-indigo-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">در حال پردازش</button>
            <button wire:click="$set('statusFilter', 'completed')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'completed' ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">تکمیل شده</button>
            <button wire:click="$set('statusFilter', 'cancelled')" class="px-4 py-2 rounded-xl text-xs font-semibold transition {{ $statusFilter === 'cancelled' ? 'bg-rose-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">لغو شده</button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <x-icons.check-badge class="mt-0.5 shrink-0 text-green-600" />
            <div class="min-w-0">{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($selectedOrder)
        <div class="mb-6 admin-card overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 min-w-0">جزئیات سفارش <span class="text-gray-500 max-w-[160px] truncate" dir="ltr" title="{{ $selectedOrder->reference }}">{{ $selectedOrder->reference }}</span></h2>
                <button wire:click="closeOrderDetail" class="px-3 py-1.5 rounded-lg text-xs bg-gray-100 text-gray-600 hover:bg-gray-200">بازگشت به لیست</button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 px-6 py-4 text-sm">
                <div>
                    <div class="text-gray-400 text-xs">مشتری</div>
                    <div class="text-gray-900">{{ $selectedOrder->user->name ?? $selectedOrder->customer_name }}</div>
                    <div class="text-gray-500 text-xs" dir="ltr">{{ $selectedOrder->user->phone ?? $selectedOrder->customer_phone }}</div>
                    @if ($selectedOrder->user)
                        <div class="text-gray-400 text-[10px] mt-1">
                            <span class="text-gray-500">کاربر ثبت‌نام‌شده</span>
                        </div>
                    @else
                        <div class="text-gray-400 text-[10px] mt-1">
                            <span>سفارش مهمان</span>
                        </div>
                    @endif
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

            {{-- Shipping / Notes --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 text-sm">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2 mb-3">
                    <svg class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    اطلاعات تحویل
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <div class="text-gray-400">آدرس</div>
                        <div class="text-gray-900">{{ $selectedOrder->shipping_address ?? 'ثبت نشده' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">پلاک</div>
                        <div class="text-gray-900">{{ $selectedOrder->shipping_plaque ?? 'ثبت نشده' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">کد پستی</div>
                        <div class="text-gray-900 font-mono" dir="ltr">{{ $selectedOrder->shipping_postal_code ?? 'ثبت نشده' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400">توضیح تحویل</div>
                        <div class="text-gray-900">{{ $selectedOrder->shipping_description ?? 'ثبت نشده' }}</div>
                    </div>
                </div>
                @if (filled($selectedOrder->notes))
                    <div class="mt-3 text-xs">
                        <span class="text-gray-400">یادداشت سفارش:</span>
                        <span class="text-amber-700 font-bold">{{ $selectedOrder->notes }}</span>
                    </div>
                @endif
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
                        $slots = \App\Services\Customization\CardPresenter::fixedSlots();
                        $itemWorkflow = $item->customization_workflow;
                        if ($itemWorkflow === null) {
                            $itemWorkflow = \App\Services\Customization\CustomizationWorkflowRegistry::classifyLegacyCustomization($custom);
                        }
                        $isFuel = $itemWorkflow === \App\Enums\CustomizationWorkflowEnum::FUEL_CARD;
                    @endphp
                    <div class="admin-card p-4 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                            <div>
                                <span class="font-bold text-gray-900 text-sm">{{ $item->product_name_snapshot }}</span>
                                <span class="text-xs text-gray-500 ms-2">(تعداد: {{ $item->quantity }})</span>
                            </div>
                            <div class="text-xs font-mono font-bold text-amber-600">
                                قیمت واحد: {{ number_format($item->unit_price_snapshot) }} تومان | جمع: {{ number_format($item->final_price) }} تومان
                            </div>
                        </div>

                        @if ($isFuel)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <div class="space-y-1.5 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <div class="font-bold text-gray-700 mb-1 border-b border-gray-200 pb-1">مشخصات کارت سوخت:</div>
                                    <div><span class="text-gray-400">روش شخصی‌سازی:</span> <span class="text-gray-900 font-bold">{{ $itemWorkflow->faLabel() }}</span></div>
                                    <div><span class="text-gray-400">رنگ کارت:</span> <span class="text-gray-900 font-bold">{{ $item->color_name_snapshot ?? 'ثبت نشده' }}</span></div>
                                    <div><span class="text-gray-400">طرح کارت:</span> <span class="text-gray-900 font-bold">{{ $item->design_name_snapshot ?? 'ثبت نشده' }}</span></div>
                                    <div><span class="text-gray-400">تصویر طرح:</span> <span class="text-gray-900 font-bold">{{ $item->design_image_path_snapshot ?? 'ثبت نشده' }}</span></div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 flex items-center justify-center text-gray-400 text-[10px]">
                                    پیش‌نمایش پشت کارت: تعریف نشده
                                </div>
                            </div>
                        @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            {{-- Details List --}}
                            <div class="space-y-1.5 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <div class="font-bold text-gray-700 mb-1 border-b border-gray-200 pb-1">پارامترهای حکاکی کاربر:</div>
                                <div><span class="text-gray-400">رنگ کارت:</span> <span class="text-gray-900 font-bold">{{ $item->color_name_snapshot ?? 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">طرح کارت:</span> <span class="text-gray-900 font-bold">{{ $item->design_name_snapshot ?? 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">شماره کارت:</span> <span class="font-mono text-gray-900 font-bold" dir="ltr">{{ !empty($custom['card_number']) ? \App\Services\Customization\CardPresenter::presentCardNumber($custom['card_number']) : 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">نام دارنده کارت:</span> <span class="text-gray-900 font-bold">{{ $custom['card_holder_name'] ?? 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">متن دلخواه پشت:</span> <span class="text-gray-900 font-bold">{{ $custom['back_text'] ?? 'ثبت نشده' }}</span></div>
                                <div><span class="text-gray-400">وضعیت CVV2:</span> <span class="text-gray-900 font-bold">{{ !empty($custom['security_cvv_enabled']) ? 'فعال (مقدار: ' . ($custom['cvv2'] ?? 'مشخص نشده') . ')' : 'غیرفعال' }}</span></div>
                                <div><span class="text-gray-400">وضعیت تاریخ انقضا:</span> <span class="text-gray-900 font-bold">{{ !empty($custom['security_expiry_enabled']) ? 'فعال (تاریخ: ' . ($custom['expiry_month'] ?? '--') . '/' . ($custom['expiry_year'] ?? '--') . ')' : 'غیرفعال' }}</span></div>
                            </div>

                            {{-- Mini Visual Snapshot Card Preview --}}
                            <div class="bg-gray-900 text-amber-100 rounded-lg p-3 relative h-40 border border-gray-800 flex flex-col justify-between overflow-hidden select-none" dir="ltr">
                                <div class="text-[9px] font-mono text-gray-400 mb-1">2D Snapshot Preview:</div>
                                <div class="absolute top-7 inset-x-0 h-6 bg-gray-950 shadow-inner"></div>

                                <div class="relative h-full w-full mt-4 text-[10px]">
                                    @if (!empty($custom['card_number']))
                                        <div class="absolute font-mono font-bold" style="left: {{ $slots['card_number']['x'] * 100 }}%; top: {{ $slots['card_number']['y'] * 100 }}%;" dir="ltr">
                                            <span style="direction: ltr; unicode-bidi: isolate;">{{ \App\Services\Customization\CardPresenter::presentCardNumber($custom['card_number']) }}</span>
                                        </div>
                                    @endif
                                    @if (!empty($custom['card_holder_name']))
                                        <div class="absolute font-serif italic bg-white/90 text-gray-900 px-1 rounded text-[9px] font-bold" style="left: {{ $slots['card_holder_name']['x'] * 100 }}%; top: {{ $slots['card_holder_name']['y'] * 100 }}%;">
                                            {{ $custom['card_holder_name'] }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['back_text']))
                                        <div class="absolute italic text-[9px]" style="left: {{ $slots['back_text']['x'] * 100 }}%; top: {{ $slots['back_text']['y'] * 100 }}%;">
                                            {{ $custom['back_text'] }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['security_cvv_enabled']) && !empty($custom['cvv2']))
                                        <div class="absolute font-mono text-[9px]" style="left: {{ $slots['cvv2']['x'] * 100 }}%; top: {{ $slots['cvv2']['y'] * 100 }}%;" dir="ltr">
                                            CVV2: {{ $custom['cvv2'] }}
                                        </div>
                                    @endif
                                    @if (!empty($custom['security_expiry_enabled']) && (!empty($custom['expiry_month']) || !empty($custom['expiry_year'])))
                                        <div class="absolute font-mono text-[9px]" style="left: {{ $slots['expiry']['x'] * 100 }}%; top: {{ $slots['expiry']['y'] * 100 }}%;" dir="ltr">
                                            EXP: {{ $custom['expiry_month'] ?? '--' }}/{{ $custom['expiry_year'] ?? '--' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @php
                $allPayments = $selectedOrder->payments->sortByDesc('created_at')->values();
                $reasonLabels = [
                    'provider_exception' => 'خطای فراهم‌کننده',
                    'provider_verification_failed' => 'تأیید ناموفق درگاه',
                    'amount_mismatch' => 'مغایرت مبلغ',
                    'order_not_payable' => 'سفارش قابل پرداخت نبود',
                    'admin_rejected' => 'رد توسط ادمین',
                ];
            @endphp

            @if ($allPayments->isNotEmpty())
                @foreach ($allPayments as $index => $payment)
                    @php
                        $reason = $payment->metadata['reason'] ?? null;
                        $detail = $payment->metadata['detail'] ?? null;
                    @endphp
                    <div class="px-6 py-4 border-t border-gray-100">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h3 class="font-bold text-gray-900">پرداخت #{{ $allPayments->count() - $index }}</h3>
                            <span class="admin-badge {{ $payment->status === \App\Enums\PaymentStatus::SUCCESS ? 'bg-green-100 text-green-800' : ($payment->status === \App\Enums\PaymentStatus::PENDING_REVIEW ? 'bg-amber-100 text-amber-700' : ($payment->status === \App\Enums\PaymentStatus::PENDING ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-700')) }}">
                                {{ $payment->status->faLabel() }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-gray-400 text-xs">روش پرداخت</div>
                                <div class="text-gray-900">{{ $payment->method->faLabel() }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">مبلغ / پرداخت‌شده</div>
                                <div class="text-gray-900 font-mono">{{ number_format($payment->amount) }} / {{ $payment->paid_amount !== null ? number_format($payment->paid_amount) : '—' }} تومان</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">درگاه</div>
                                <div class="text-gray-900" dir="ltr">{{ $payment->gateway ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">شناسه تراکنش</div>
                                <div class="text-gray-900 break-all font-mono" dir="ltr">{{ $payment->transaction_id ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">کد رهگیری</div>
                                <div class="text-gray-900 break-all font-mono" dir="ltr">{{ $payment->tracking_code ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">تاریخ</div>
                                <div class="text-gray-900">ایجاد: {{ jalali_date($payment->created_at, 'datetime') }}</div>
                                @if ($payment->paid_at)
                                    <div class="text-green-700 text-xs">پرداخت: {{ jalali_date($payment->paid_at, 'datetime') }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">توضیح مشتری / دلیل</div>
                                <div class="text-gray-900">
                                    {{ $payment->metadata['note'] ?? '—' }}
                                    @if ($reason)
                                        <span class="inline-block ms-2 text-xs px-2 py-0.5 rounded-lg bg-red-50 text-red-700">{{ $reasonLabels[$reason] ?? $reason }}</span>
                                        @if ($detail)
                                            <div class="text-xs text-gray-500 mt-1" title="{{ $detail }}">{{ $detail }}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-400 text-xs">بررسی</div>
                                <div class="text-gray-900 text-xs">
                                    @if (isset($payment->metadata['reviewed_by'], $payment->metadata['reviewed_at']))
                                        توسط #{{ $payment->metadata['reviewed_by'] }} در {{ jalali_date($payment->metadata['reviewed_at'], 'datetime') }}
                                    @else
                                        — 
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($payment->receipt_path && $payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                            <div class="mt-4">
                                <div class="text-gray-400 text-xs mb-2">رسید</div>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('admin.payments.receipt', ['order' => $selectedOrder, 'payment' => $payment]) }}" target="_blank" class="px-3 py-1.5 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">مشاهده رسید</a>
                                    <a href="{{ route('admin.payments.receipt', ['order' => $selectedOrder, 'payment' => $payment, 'download' => 1]) }}" class="px-3 py-1.5 rounded-lg text-xs bg-gray-800 hover:bg-gray-900 text-white">دانلود رسید</a>
                                </div>
                            </div>
                        @endif

                        @if ($payment->status === \App\Enums\PaymentStatus::PENDING_REVIEW && $payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                            <div class="mt-4 flex items-center gap-2">
                                <button wire:click="approvePayment({{ $payment->id }})" wire:confirm="آیا از تأیید این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-success">تایید پرداخت</button>
                                <button wire:click="rejectPayment({{ $payment->id }})" wire:confirm="آیا از رد این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-danger">رد پرداخت</button>
                            </div>
                        @endif

                        @if ($payment->status === \App\Enums\PaymentStatus::SUCCESS)
                            @php
                                $refundableAmount = $payment->paid_amount - $payment->refunds->whereIn('status', ['completed', 'pending', 'review'])->sum('amount');
                                $refundableAmount = max(0, $refundableAmount);
                            @endphp
                            @if ($refundableAmount > 0)
                                <div class="mt-4 flex items-center gap-2">
                                    <button wire:click="refundPayment({{ $payment->id }}, {{ $refundableAmount }})" wire:confirm="آیا از بازگشت {{ number_format($refundableAmount) }} تومان مطمئن هستید؟" class="px-4 py-2 rounded-lg text-sm bg-amber-600 hover:bg-amber-700 text-white">بازگشت وجه ({{ number_format($refundableAmount) }} تومان)</button>
                                </div>
                            @endif
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

    <div class="admin-card overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">سفارشات اخیر سیستم</h3>
            <span class="text-xs text-slate-400">نمایش وضعیت سفارشات و تغییرات</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/70 border-b border-slate-100">
                    <tr>
                        <th class="admin-th w-24">کد سفارش</th>
                        <th class="admin-th">مشتری</th>
                        <th class="admin-th">مبلغ سفارش</th>
                        <th class="admin-th">وضعیت</th>
                        <th class="admin-th">تاریخ ثبت</th>
                        <th class="admin-th text-center">جزئیات</th>
                        <th class="admin-th">تغییر وضعیت</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $statusColors = [
                                'pending' => 'admin-badge-warning',
                                'confirmed' => 'admin-badge-info',
                                'processing' => 'admin-badge-info',
                                'completed' => 'admin-badge-success',
                                'cancelled' => 'admin-badge-danger',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="admin-td font-mono text-xs text-slate-500" dir="ltr">{{ $order->reference ?? '#'.$order->id }}</td>
                            <td class="admin-td">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                        {{ mb_substr($order->user?->name ?? $order->customer_name ?? 'م', 0, 1) }}
                                    </span>
                                    <div>
                                        <span class="font-bold text-slate-900 block text-xs">{{ $order->user?->name ?? ($order->customer_name ?? 'کاربر مهمان') }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono" dir="ltr">{{ $order->user?->phone ?? ($order->customer_phone ?? '-') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="admin-td font-bold text-slate-900 text-xs">{{ number_format($order->total_price) }} تومان</td>
                            <td class="admin-td">
                                <span class="{{ $statusColors[$order->status->value] ?? 'admin-badge-neutral' }} admin-badge">
                                    {{ $order->status->faLabel() }}
                                </span>
                            </td>
                            <td class="admin-td text-slate-400 text-xs">{{ jalali_relative($order->created_at) }}</td>
                            <td class="admin-td text-center">
                                <button wire:click="viewOrder({{ $order->id }})" class="admin-btn admin-btn-secondary admin-btn-sm font-semibold">
                                    جزئیات
                                </button>
                            </td>
                            <td class="admin-td">
                                @php $targets = $transitions[$order->id] ?? []; @endphp
                                @if (empty($targets))
                                    <span class="text-slate-400 text-xs">—</span>
                                @else
                                    <select wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-700 transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-100">
                                        <option value="">تغییر وضعیت...</option>
                                        @foreach ($targets as $target)
                                            <option value="{{ $target['value'] }}">{{ $target['label'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-empty">سفارشی وجود ندارد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-100">{{ $orders->links() }}</div>
    </div>
</div>