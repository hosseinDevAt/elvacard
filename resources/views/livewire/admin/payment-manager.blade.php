<div>
    <div class="mb-6 flex items-center justify-end gap-4">
        <button wire:click="resetFilters" class="admin-btn admin-btn-secondary admin-btn-sm">پاک‌سازی فیلترها</button>
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

    <div class="mb-6 admin-card p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
            <div>
                <label class="block text-xs text-gray-400 mb-1">وضعیت</label>
                <select wire:model.live="statusFilter" class="admin-input">
                    <option value="">همه</option>
                    @foreach ($statusCases as $case)
                        <option value="{{ $case->value }}">{{ $case->faLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">روش پرداخت</label>
                <select wire:model.live="methodFilter" class="admin-input">
                    <option value="">همه</option>
                    @foreach ($methodCases as $case)
                        <option value="{{ $case->value }}">{{ $case->faLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">از تاریخ</label>
                <x-jalali-date-input wire:model.live="fromDate" id="payment_from_date" />
                @error('fromDate')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">تا تاریخ</label>
                <x-jalali-date-input wire:model.live="toDate" id="payment_to_date" />
                @error('toDate')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">کد سفارش</label>
                <input type="text" wire:model.live.debounce.300ms="reference" placeholder="ORD-2026-..." dir="ltr" class="admin-input">
            </div>
        </div>
    </div>

    @php
        $reasonLabels = [
            'provider_exception' => 'خطای فراهم‌کننده',
            'provider_verification_failed' => 'تأیید ناموفق درگاه',
            'amount_mismatch' => 'مغایرت مبلغ',
            'order_not_payable' => 'سفارش قابل پرداخت نبود',
            'admin_rejected' => 'رد توسط ادمین',
        ];
        $statusColors = [
            'pending' => 'bg-gray-100 text-gray-700',
            'pending_review' => 'bg-amber-100 text-amber-700',
            'success' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-red-100 text-red-700',
        ];
        $refundStatusColors = [
            'pending' => 'bg-gray-100 text-gray-700',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-700',
            'review' => 'bg-amber-100 text-amber-700',
            'cancelled' => 'bg-red-100 text-red-700',
        ];
        $refundReasonLabels = [
            'provider_unknown' => 'نتیجه نامشخص هنگام بازگشت وجه',
            'provider_refund_failed' => 'رد شده توسط درگاه',
            'unknown_gateway' => 'درگاه ناشناخته',
            'confirmed_by_lookup' => 'تأیید عدم موفقیت از درگاه',
        ];
    @endphp

    @if ($selectedPayment)
        @php
            $sp = $selectedPayment;
            $spReason = $sp->metadata['reason'] ?? null;
            $spDetail = $sp->metadata['detail'] ?? null;
            $spRefunds = $sp->refunds->sortByDesc('created_at')->values();
        @endphp
        <div class="mb-6 admin-card overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900 min-w-0">جزئیات پرداخت <span class="text-gray-500 text-sm" dir="ltr">#{{ $sp->id }}</span></h2>
                <button wire:click="closePaymentDetail" class="px-3 py-1.5 rounded-lg text-xs bg-gray-100 text-gray-600 hover:bg-gray-200">بازگشت به لیست</button>
            </div>

            <div class="px-6 py-4">
                {{-- Payment summary --}}
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h3 class="font-bold text-gray-900">
                        پرداخت سفارش <span class="font-mono text-sm" dir="ltr">{{ $sp->order?->reference ?? $sp->order_id }}</span>
                    </h3>
                    <span class="admin-badge {{ $statusColors[$sp->status->value] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $sp->status->faLabel() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-400 text-xs">مشتری</div>
                        <div class="text-gray-900">{{ $sp->order?->user?->name ?? $sp->order?->customer_name ?? '—' }}</div>
                        <div class="text-gray-500 text-xs font-mono" dir="ltr">{{ $sp->order?->user?->phone ?? $sp->order?->customer_phone ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">روش پرداخت</div>
                        <div class="text-gray-900">{{ $sp->method->faLabel() }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">مبلغ / پرداخت‌شده</div>
                        <div class="text-gray-900 font-mono" dir="ltr">{{ number_format($sp->amount) }} / {{ $sp->paid_amount !== null ? number_format($sp->paid_amount) : '—' }} تومان</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">درگاه</div>
                        <div class="text-gray-900" dir="ltr">{{ $sp->gateway ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">شناسه تراکنش</div>
                        <div class="text-gray-900 break-all font-mono text-xs" dir="ltr">{{ $sp->transaction_id ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">کد رهگیری</div>
                        <div class="text-gray-900 break-all font-mono text-xs" dir="ltr">{{ $sp->tracking_code ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">زمان</div>
                        <div class="text-gray-900 text-xs">ایجاد: {{ jalali_date($sp->created_at, 'datetime') }}</div>
                        @if ($sp->paid_at)
                            <div class="text-green-700 text-xs">پرداخت: {{ jalali_date($sp->paid_at, 'datetime') }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">کد دلیل / توضیح</div>
                        <div class="text-gray-900 text-xs">
                            {{ $sp->metadata['note'] ?? '—' }}
                            @if ($spReason)
                                <span class="inline-block ms-1 text-xs px-2 py-0.5 rounded-lg bg-red-50 text-red-700">{{ $reasonLabels[$spReason] ?? $spReason }}</span>
                                @if ($spDetail)
                                    <div class="text-xs text-gray-500 mt-1" title="{{ $spDetail }}">{{ $spDetail }}</div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs">بررسی</div>
                        <div class="text-gray-900 text-xs">
                            @if (isset($sp->metadata['reviewed_by'], $sp->metadata['reviewed_at']))
                                توسط #{{ $sp->metadata['reviewed_by'] }} در {{ jalali_date($sp->metadata['reviewed_at'], 'datetime') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if ($sp->receipt_path && $sp->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                    <div class="mt-4">
                        <div class="text-gray-400 text-xs mb-2">رسید</div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.payments.receipt', ['order' => $sp->order, 'payment' => $sp]) }}" target="_blank" class="px-3 py-1.5 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">مشاهده رسید</a>
                            <a href="{{ route('admin.payments.receipt', ['order' => $sp->order, 'payment' => $sp, 'download' => 1]) }}" class="px-3 py-1.5 rounded-lg text-xs bg-gray-800 hover:bg-gray-900 text-white">دانلود رسید</a>
                        </div>
                    </div>
                @endif

                @if ($sp->status === \App\Enums\PaymentStatus::PENDING_REVIEW && $sp->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                    <div class="mt-4 flex items-center gap-2">
                        <button wire:click="approvePayment({{ $sp->id }})" wire:confirm="آیا از تأیید این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-success">تایید پرداخت</button>
                        <button wire:click="rejectPayment({{ $sp->id }})" wire:confirm="آیا از رد این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-danger">رد پرداخت</button>
                    </div>
                @endif

                @if ($sp->status === \App\Enums\PaymentStatus::SUCCESS)
                    @php
                        // Authoritative refundable balance from RefundConstraintService;
                        // never recomputed in the view.
                        $spRefundable = $refundableByPaymentId[(int) $sp->id] ?? 0;
                    @endphp
                    @if ($spRefundable > 0)
                        <div class="mt-4 flex items-center gap-2">
                            <button wire:click="refundPayment({{ $sp->id }}, {{ $spRefundable }})" wire:confirm="آیا از بازگشت {{ number_format($spRefundable) }} تومان مطمئن هستید؟" class="px-4 py-2 rounded-lg text-sm bg-amber-600 hover:bg-amber-700 text-white">بازگشت وجه ({{ number_format($spRefundable) }} تومان)</button>
                        </div>
                    @endif
                @endif

                {{-- Refund rows --}}
                <div class="mt-6 border-t border-gray-100 pt-4">
                    <h3 class="font-bold text-gray-900 text-sm mb-3">بازگشت‌های وجه</h3>

                    @forelse ($spRefunds as $refund)
                        <div class="rounded-xl border border-gray-200 p-4 mb-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="{{ $refundStatusColors[$refund->status->value] ?? 'bg-gray-100 text-gray-700' }} admin-badge">
                                        {{ $refund->status->faLabel() }}
                                    </span>
                                    <span class="font-mono font-bold text-amber-600">{{ number_format($refund->amount) }} تومان</span>
                                </div>
                                @if (in_array($refund->status, [\App\Enums\RefundStatus::PENDING, \App\Enums\RefundStatus::REVIEW], true))
                                    <button wire:click="reconcileReviewRefund({{ $refund->id }})" wire:confirm="آیا از بررسی مجدد نتیجه این بازگشت وجه مطمئن هستید؟" class="px-3 py-1.5 rounded-lg text-xs bg-amber-600 hover:bg-amber-700 text-white">بررسی مجدد نتیجه</button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 mt-3 text-xs">
                                <div>
                                    <span class="text-gray-400">ایجاد:</span>
                                    <span class="text-gray-900">{{ jalali_date($refund->created_at, 'datetime') }}</span>
                                    @if ($refund->metadata['created_by'] ?? null)
                                        <span class="text-gray-500"> (توسط #{{ $refund->metadata['created_by'] }})</span>
                                    @endif
                                </div>
                                @if ($refund->refunded_at)
                                    <div>
                                        <span class="text-green-700">تکمیل: {{ jalali_date($refund->refunded_at, 'datetime') }}</span>
                                    </div>
                                @endif
                                @if ($refund->reason)
                                    <div>
                                        <span class="text-gray-400">دلیل ادمین:</span>
                                        <span class="text-gray-900">{{ $refund->reason }}</span>
                                    </div>
                                @endif
                                @if (isset($refund->metadata['reason']))
                                    <div>
                                        <span class="text-gray-400">کد دلیل:</span>
                                        <span class="inline-block px-2 py-0.5 rounded-lg bg-red-50 text-red-700">{{ $refundReasonLabels[$refund->metadata['reason']] ?? $refund->metadata['reason'] }}</span>
                                        @if ($refund->metadata['detail'] ?? null)
                                            <div class="text-gray-500 mt-0.5 truncate max-w-[260px]" title="{{ $refund->metadata['detail'] }}">{{ $refund->metadata['detail'] }}</div>
                                        @endif
                                    </div>
                                @endif
                                @if ($refund->metadata['provider_refund_id'] ?? null)
                                    <div>
                                        <span class="text-gray-400">شناسه درگاه:</span>
                                        <span class="text-gray-900 font-mono" dir="ltr">{{ $refund->metadata['provider_refund_id'] }}</span>
                                    </div>
                                @endif
                                @if ($refund->metadata['idempotency_key'] ?? null)
                                    <div>
                                        <span class="text-gray-400">کلید یکتایی:</span>
                                        <span class="text-gray-900 font-mono text-[10px] break-all" dir="ltr">{{ $refund->metadata['idempotency_key'] }}</span>
                                    </div>
                                @endif
                                @if (isset($refund->metadata['reconciliation_attempts']))
                                    <div>
                                        <span class="text-gray-400">تعداد بررسی مجدد:</span>
                                        <span class="text-gray-900">{{ $refund->metadata['reconciliation_attempts'] }}</span>
                                    </div>
                                @endif
                                @if (($refund->metadata['reconciled_via'] ?? null) || ($refund->metadata['resolved_by'] ?? null))
                                    <div>
                                        <span class="text-gray-400">نتیجه‌گیری:</span>
                                        <span class="text-gray-900">
                                            {{ ($refund->metadata['reconciled_via'] ?? null) === 'provider_lookup' ? 'بررسی از درگاه' : ($refund->metadata['reconciled_via'] ?? '') }}
                                            @if ($refund->metadata['resolved_by'] ?? null)
                                                توسط #{{ $refund->metadata['resolved_by'] }}
                                            @endif
                                            @if ($refund->metadata['resolved_at'] ?? null)
                                                در {{ jalali_date($refund->metadata['resolved_at'], 'datetime') }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                                @if (($refund->metadata['integrity_violation'] ?? false))
                                    <div>
                                        <span class="inline-block px-2 py-0.5 rounded-lg bg-red-50 text-red-700">⚠ مغایرت یکپارچگی (بیش از مبلغ دریافتی)</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-gray-400 text-sm">بازگشت وجهی برای این پرداخت ثبت نشده است.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div class="admin-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="admin-th">سفارش</th>
                    <th class="admin-th">مشتری</th>
                    <th class="admin-th">روش</th>
                    <th class="admin-th">وضعیت</th>
                    <th class="admin-th">مبلغ / پرداخت‌شده</th>
                    <th class="admin-th">درگاه / تراکنش</th>
                    <th class="admin-th">کد دلیل</th>
                    <th class="admin-th">زمان</th>
                    <th class="admin-th">اقدامات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                    @php
                        $reason = $payment->metadata['reason'] ?? null;
                        $detail = $payment->metadata['detail'] ?? null;
                    @endphp
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="admin-td">
                            <div class="font-mono text-xs" dir="ltr">{{ $payment->order?->reference ?? '—' }}</div>
                            <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs text-yellow-600 hover:underline">
                                سفارش #{{ $payment->order_id }}
                            </a>
                        </td>
                        <td class="admin-td">
                            <div class="text-gray-900">{{ $payment->order?->user?->name ?? $payment->order?->customer_name ?? '—' }}</div>
                            <div class="text-gray-500 text-xs font-mono" dir="ltr">{{ $payment->order?->user?->phone ?? $payment->order?->customer_phone ?? '—' }}</div>
                        </td>
                        <td class="admin-td text-xs">{{ $payment->method->faLabel() }}</td>
                        <td class="admin-td">
                            <span class="{{ $statusColors[$payment->status->value] ?? 'bg-gray-100 text-gray-700' }} admin-badge">
                                {{ $payment->status->faLabel() }}
                            </span>
                        </td>
                        <td class="admin-td font-mono text-xs" dir="ltr">
                            <div>{{ number_format($payment->amount) }} تومان</div>
                            @if ($payment->paid_amount !== null)
                                <div class="text-green-700">{{ number_format($payment->paid_amount) }} تومان (پرداخت‌شده)</div>
                            @endif
                        </td>
                        <td class="admin-td font-mono text-xs" dir="ltr">
                            @if ($payment->gateway)
                                <div>{{ $payment->gateway }}</div>
                            @endif
                            @if ($payment->transaction_id)
                                <div class="text-gray-600">تراکنش: {{ $payment->transaction_id }}</div>
                            @endif
                            @if ($payment->tracking_code)
                                <div class="text-gray-600">رهگیری: {{ $payment->tracking_code }}</div>
                            @endif
                            @if (! $payment->gateway && ! $payment->transaction_id && ! $payment->tracking_code)
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="admin-td">
                            @if ($reason)
                                <span class="inline-block text-xs px-2 py-1 rounded-lg bg-red-50 text-red-700">
                                    {{ $reasonLabels[$reason] ?? $reason }}
                                </span>
                                @if ($detail)
                                    <div class="text-xs text-gray-500 mt-1 max-w-[180px] truncate" title="{{ $detail }}">{{ $detail }}</div>
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="admin-td text-xs text-gray-500">
                            <div>ایجاد: {{ jalali_date($payment->created_at, 'datetime') }}</div>
                            @if ($payment->paid_at)
                                <div class="text-green-700">پرداخت: {{ jalali_date($payment->paid_at, 'datetime') }}</div>
                            @endif
                        </td>
                        <td class="admin-td">
                            <div class="flex flex-col items-start gap-2">
                                <button wire:click="viewPayment({{ $payment->id }})" class="px-2 py-1 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">جزئیات</button>
                                @if ($payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER && $payment->receipt_path)
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.payments.receipt', ['order' => $payment->order, 'payment' => $payment]) }}" target="_blank" class="px-2 py-1 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">رسید</a>
                                        <a href="{{ route('admin.payments.receipt', ['order' => $payment->order, 'payment' => $payment, 'download' => 1]) }}" class="px-2 py-1 rounded-lg text-xs bg-gray-800 hover:bg-gray-900 text-white">دانلود</a>
                                    </div>
                                @endif
                                @if ($payment->status === \App\Enums\PaymentStatus::PENDING_REVIEW && $payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                                    <div class="flex items-center gap-2">
                                        <button wire:click="approvePayment({{ $payment->id }})" wire:confirm="آیا از تأیید این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-success admin-btn-sm">تایید</button>
                                        <button wire:click="rejectPayment({{ $payment->id }})" wire:confirm="آیا از رد این پرداخت مطمئن هستید؟" class="admin-btn admin-btn-danger admin-btn-sm">رد</button>
                                    </div>
                                @endif
                                @if ($payment->status === \App\Enums\PaymentStatus::SUCCESS)
                                    @php
                                        $refundable = $refundableByPaymentId[(int) $payment->id] ?? 0;
                                    @endphp
                                    @if ($refundable > 0)
                                        <button wire:click="refundPayment({{ $payment->id }}, {{ $refundable }})" wire:confirm="آیا از بازگشت {{ number_format($refundable) }} تومان مطمئن هستید؟" class="px-3 py-1.5 rounded-lg text-xs bg-amber-600 hover:bg-amber-700 text-white">بازگشت وجه</button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="admin-empty">پرداختی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $payments->links() }}</div>
    </div>
</div>