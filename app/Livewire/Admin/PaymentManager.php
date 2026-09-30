<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentReviewException;
use App\Exceptions\RefundConstraintViolationException;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\ManualPaymentReviewService;
use App\Services\ManualRefundService;
use App\Services\RefundConstraintService;
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
 * filtering. Mutations delegate to services: approve/reject to
 * ManualPaymentReviewService, refunds to ManualRefundService / RefundCore,
 * and REVIEW reconciliation to RefundCore; this component never writes
 * payment or refund state directly.
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

    public ?int $selectedPaymentId = null;

    public function resetFilters(): void
    {
        $this->reset('statusFilter', 'methodFilter', 'fromDate', 'toDate', 'reference');
    }

    public function viewPayment(int $paymentId): void
    {
        $payment = Payment::find($paymentId);

        if (! $payment) {
            session()->flash('error', 'پرداخت یافت نشد');

            return;
        }

        $this->selectedPaymentId = $payment->id;
    }

    public function closePaymentDetail(): void
    {
        $this->selectedPaymentId = null;
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
            $refund = $payment->method === PaymentMethod::MANUAL_TRANSFER
                ? app(ManualRefundService::class)->refund($payment, $amount, $reason)
                : app(RefundCore::class)->processRefund($payment, $amount, $reason);
        } catch (RefundConstraintViolationException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->flashRefundOutcome($refund);
    }

    public function reconcileReviewRefund(int $refundId): void
    {
        $refund = Refund::with('payment.order')->find($refundId);

        if (! $refund) {
            session()->flash('error', 'بازگشت وجه یافت نشد');

            return;
        }

        if ($refund->status !== RefundStatus::REVIEW) {
            session()->flash('error', 'تنها بازگشت‌های در حال بررسی قابل بررسی مجدد هستند');

            return;
        }

        if (! $refund->payment || ! Gate::allows('updateStatus', $refund->payment->order)) {
            session()->flash('error', 'شما مجاز به بررسی مجدد این بازگشت نیستید');

            return;
        }

        try {
            $result = app(RefundCore::class)->retryReviewRefund($refundId);
        } catch (RefundConstraintViolationException) {
            session()->flash('error', 'نتیجه بازگشت وجه همچنان نامشخص است و نیاز به بررسی مجدد دارد.');

            return;
        }

        match ($result->status) {
            RefundStatus::COMPLETED => session()->flash('success', 'بازگشت وجه با موفقیت تأیید شد.'),
            RefundStatus::FAILED => session()->flash('error', 'بازگشت وجه توسط درگاه ناموفق تأیید شد.'),
            default => session()->flash('error', 'نتیجه بازگشت وجه همچنان نامشخص است و نیاز به بررسی مجدد دارد.'),
        };
    }

    private function flashRefundOutcome(Refund $refund): void
    {
        match ($refund->status) {
            RefundStatus::COMPLETED => session()->flash('success', 'بازگشت وجه با موفقیت انجام شد.'),
            RefundStatus::FAILED => session()->flash('error', 'بازگشت وجه ناموفق بود.'),
            RefundStatus::REVIEW => session()->flash('error', 'نتیجه بازگشت وجه نامشخص است و برای بررسی مجدد ثبت شد.'),
            default => session()->flash('error', 'وضعیت بازگشت وجه نامشخص است.'),
        };
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

    public function render(RefundConstraintService $constraints)
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

        $selectedPayment = $this->selectedPaymentId
            ? Payment::with(['order.user', 'refunds'])->find($this->selectedPaymentId)
            : null;

        if ($this->selectedPaymentId && ! $selectedPayment) {
            $this->selectedPaymentId = null;
        }

        // The refundable balance is always derived from the single
        // authoritative calculation, never recomputed in the view, so the
        // displayed figure can never disagree with what the server will accept.
        $refundableByPaymentId = [];

        foreach ($payments as $listedPayment) {
            $refundableByPaymentId[(int) $listedPayment->id] = $constraints->refundableAmount($listedPayment);
        }

        if ($selectedPayment !== null) {
            $refundableByPaymentId[(int) $selectedPayment->id] = $constraints->refundableAmount($selectedPayment);
        }

        return view('livewire.admin.payment-manager', [
            'payments' => $payments,
            'selectedPayment' => $selectedPayment,
            'refundableByPaymentId' => $refundableByPaymentId,
            'statusCases' => PaymentStatus::cases(),
            'methodCases' => PaymentMethod::cases(),
        ])->layout('layouts.admin')->title('مدیریت پرداخت‌ها');
    }
}
