<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentReviewException;
use App\Models\Payment;
use App\Services\ManualPaymentReviewService;
use App\Support\Concerns\AuthorizesAdminActions;
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
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate !== null && $this->toDate !== '') {
            $query->whereDate('created_at', '<=', $this->toDate);
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
