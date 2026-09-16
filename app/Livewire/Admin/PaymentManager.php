<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentReviewException;
use App\Exceptions\RefundConstraintViolationException;
use App\Models\Payment;
use App\Services\ManualPaymentReviewService;
use App\Services\ManualRefundService;
use App\Services\RefundCore;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Dates\DateService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Payment operations surface for the admin panel.
 *
 * Read-only exploration of every payment attempt (manual and gateway) with
 * filtering. The only mutating actions are approve/reject, which delegate to
 * the ManualPaymentReviewService; this component never writes payment status
 * directly.
 */
class PaymentManager extends Component
{
    use AuthorizesAdminActions;
    use WithPagination;

    public ?string $statusFilter = null;

    public ?string $methodFilter = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public ?string $reference = null;

    public function resetFilters(): void
    {
        $this->reset('statusFilter', 'methodFilter', 'fromDate', 'toDate', 'reference');
    }

    public function approvePayment(int $paymentId, ManualPaymentReviewService $service): void
    {
        $this->reviewPayment($paymentId, $service, 'approve');
    }

    public function rejectPayment(int $paymentId, ManualPaymentReviewService $service): void
    {
        $this->reviewPayment($paymentId, $service, 'reject');
    }

    public function refundPayment(int $paymentId, int $amount, ?string $reason = null): void
    {
        $payment = Payment::with('order')->find($paymentId);

        if (! $payment || ! Gate::allows('updateStatus', $payment->order)) {
            session()->flash('error', 'شما مجاز به بازگشت وجه نیستید');

            return;
        }

        if ($payment->status !== PaymentStatus::SUCCESS) {
            session()->flash('error', 'تنها پرداخت‌های موفق قابل بازگشت هستند');

            return;
        }

        try {
            if ($payment->method === PaymentMethod::MANUAL_TRANSFER) {
                $service = app(ManualRefundService::class);
                $service->refund($payment, $amount, $reason);
            } else {
                $service = app(RefundCore::class);
                $service->processRefund($payment, $amount, $reason);
            }

            session()->flash('success', 'بازگشت وجه با موفقیت انجام شد');
        } catch (RefundConstraintViolationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function reviewPayment(int $paymentId, ManualPaymentReviewService $service, string $action): void
    {
        $payment = Payment::with('order')->find($paymentId);

        if (! $payment || ! Gate::allows('updateStatus', $payment->order)) {
            session()->flash('error', 'شما مجاز به بررسی این پرداخت نیستید');

            return;
        }

        try {
            if ($action === 'approve') {
                $service->approve($payment);
                session()->flash('success', 'پرداخت تأیید شد');
            } else {
                $service->reject($payment);
                session()->flash('success', 'پرداخت رد شد');
            }
        } catch (PaymentReviewException|PaymentConstraintViolationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = Payment::query()->with(['order.user']);

        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            if (PaymentStatus::tryFrom($this->statusFilter)) {
                $query->where('status', $this->statusFilter);
            }
        }

        if ($this->methodFilter !== null && $this->methodFilter !== '') {
            if (PaymentMethod::tryFrom($this->methodFilter)) {
                $query->where('method', $this->methodFilter);
            }
        }

        if ($this->fromDate !== null && $this->fromDate !== '') {
            $dates = app(DateService::class);

            if (! $dates->isValidDate($this->fromDate)) {
                $this->addError('fromDate', 'تاریخ شروع نامعتبر است');
            } else {
                $query->where('created_at', '>=', $dates->fromJalali($this->fromDate));
            }
        }

        if ($this->toDate !== null && $this->toDate !== '') {
            $dates = app(DateService::class);

            if (! $dates->isValidDate($this->toDate)) {
                $this->addError('toDate', 'تاریخ پایان نامعتبر است');
            } else {
                $query->where('created_at', '<=', $dates->dayEndCanonical($dates->fromJalali($this->toDate)));
            }
        }

        if ($this->reference !== null && trim($this->reference) !== '') {
            $reference = trim($this->reference);
            $query->whereHas('order', fn ($q) => $q->where('reference', 'like', "%{$reference}%"));
        }

        $payments = $query->latest()->paginate(15);

        return view('livewire.admin.payment-manager', [
            'payments' => $payments,
            'statusCases' => PaymentStatus::cases(),
            'methodCases' => PaymentMethod::cases(),
        ])->layout('layouts.admin')->title('مدیریت پرداخت‌ها');
    }
}
