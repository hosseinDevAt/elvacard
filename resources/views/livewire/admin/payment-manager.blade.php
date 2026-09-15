<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مدیریت پرداخت‌ها</h1>
        <button wire:click="resetFilters" class="px-3 py-1.5 rounded-lg text-xs bg-gray-200 text-gray-700 hover:bg-gray-300 transition">پاک‌سازی فیلترها</button>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
            <div>
                <label class="block text-xs text-gray-400 mb-1">وضعیت</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    <option value="">همه</option>
                    @foreach ($statusCases as $case)
                        <option value="{{ $case->value }}">{{ $case->faLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">روش پرداخت</label>
                <select wire:model.live="methodFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
                    <option value="">همه</option>
                    @foreach ($methodCases as $case)
                        <option value="{{ $case->value }}">{{ $case->faLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">از تاریخ</label>
                <input type="date" wire:model.live="fromDate" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">تا تاریخ</label>
                <input type="date" wire:model.live="toDate" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">کد سفارش</label>
                <input type="text" wire:model.live.debounce.300ms="reference" placeholder="ORD-2026-..." dir="ltr" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-yellow-500 focus:ring-2 focus:ring-yellow-200 transition">
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
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">سفارش</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">مشتری</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">روش</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">وضعیت</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">مبلغ / پرداخت‌شده</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">درگاه / تراکنش</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">کد دلیل</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">زمان</th>
                    <th class="px-4 py-3 text-start font-medium text-gray-500">اقدامات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                    @php
                        $reason = $payment->metadata['reason'] ?? null;
                        $detail = $payment->metadata['detail'] ?? null;
                    @endphp
                    <tr class="hover:bg-gray-50 align-top">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs" dir="ltr">{{ $payment->order?->reference ?? '—' }}</div>
                            <a href="{{ route('admin.orders') }}" wire:navigate class="text-xs text-yellow-600 hover:underline">
                                سفارش #{{ $payment->order_id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $payment->order?->user?->name ?? $payment->order?->customer_name ?? '—' }}</div>
                            <div class="text-gray-500 text-xs font-mono" dir="ltr">{{ $payment->order?->user?->phone ?? $payment->order?->customer_phone ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $payment->method->faLabel() }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $statusColors[$payment->status->value] ?? 'bg-gray-100 text-gray-700' }} text-xs px-2 py-1 rounded-full">
                                {{ $payment->status->faLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs" dir="ltr">
                            <div>{{ number_format($payment->amount) }} تومان</div>
                            @if ($payment->paid_amount !== null)
                                <div class="text-green-700">{{ number_format($payment->paid_amount) }} تومان (پرداخت‌شده)</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs" dir="ltr">
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
                        <td class="px-4 py-3">
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
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <div>ایجاد: {{ $payment->created_at->format('Y-m-d H:i') }}</div>
                            @if ($payment->paid_at)
                                <div class="text-green-700">پرداخت: {{ $payment->paid_at->format('Y-m-d H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col items-start gap-2">
                                @if ($payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER && $payment->receipt_path)
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.payments.receipt', ['order' => $payment->order, 'payment' => $payment]) }}" target="_blank" class="px-2 py-1 rounded-lg text-xs bg-yellow-500 hover:bg-yellow-600 text-white">رسید</a>
                                        <a href="{{ route('admin.payments.receipt', ['order' => $payment->order, 'payment' => $payment, 'download' => 1]) }}" class="px-2 py-1 rounded-lg text-xs bg-gray-800 hover:bg-gray-900 text-white">دانلود</a>
                                    </div>
                                @endif
                                @if ($payment->status === \App\Enums\PaymentStatus::PENDING_REVIEW && $payment->method === \App\Enums\PaymentMethod::MANUAL_TRANSFER)
                                    <div class="flex items-center gap-2">
                                        <button wire:click="approvePayment({{ $payment->id }})" wire:confirm="آیا از تأیید این پرداخت مطمئن هستید؟" class="px-3 py-1.5 rounded-lg text-xs bg-green-600 hover:bg-green-700 text-white">تایید</button>
                                        <button wire:click="rejectPayment({{ $payment->id }})" wire:confirm="آیا از رد این پرداخت مطمئن هستید؟" class="px-3 py-1.5 rounded-lg text-xs bg-red-600 hover:bg-red-700 text-white">رد</button>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">پرداختی یافت نشد</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $payments->links() }}</div>
    </div>
</div>