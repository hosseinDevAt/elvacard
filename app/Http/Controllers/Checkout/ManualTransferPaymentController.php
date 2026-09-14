<?php

namespace App\Http\Controllers\Checkout;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentRetryException;
use App\Http\Controllers\Controller;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ManualPaymentCreationService;
use App\Services\ManualPaymentRetryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ManualTransferPaymentController extends Controller
{
    public function show(Order $order): View|RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($this->hasActivePayment($order)) {
            return redirect()->route('checkout.success', $order->token);
        }

        $lastFailedPayment = $order->payments()
            ->where('method', PaymentMethod::MANUAL_TRANSFER->value)
            ->where('status', PaymentStatus::FAILED->value)
            ->latest()
            ->first();

        return view('checkout.payment', [
            'order' => $order,
            'setting' => ManualPaymentSetting::query()->where('is_active', true)->first(),
            'lastFailedPayment' => $lastFailedPayment,
            'guestRetryBlocked' => $lastFailedPayment !== null && $order->user_id === null,
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($this->hasActivePayment($order)) {
            return redirect()->route('checkout.success', $order->token);
        }

        $setting = ManualPaymentSetting::query()->where('is_active', true)->first();

        if (! $setting) {
            return back()->withErrors(['payment' => 'پرداخت کارت به کارت در حال حاضر فعال نیست.']);
        }

        $validated = $request->validate([
            'receipt_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $receiptPath = $request->file('receipt_image')->store('payment_receipts', 'local');

        // A retry is only ever a repeat of a FAILED manual transfer attempt;
        // a failed online gateway attempt is not a manual retry and therefore
        // never blocks a first-time manual transfer.
        $isRetry = Payment::query()
            ->where('order_id', $order->id)
            ->where('method', PaymentMethod::MANUAL_TRANSFER->value)
            ->where('status', PaymentStatus::FAILED->value)
            ->exists();

        if ($isRetry && $order->user_id === null) {
            Storage::disk('local')->delete($receiptPath);

            return back()->withErrors(['payment' => 'پرداخت مجدد برای سفارش مهمان امکان‌پذیر نیست.']);
        }

        try {
            if ($isRetry) {
                app(ManualPaymentRetryService::class)->createRetryPayment($order, [
                    'receipt_path' => $receiptPath,
                    'tracking_number' => $validated['tracking_number'] ?? null,
                    'note' => $validated['note'] ?? null,
                ]);
            } else {
                app(ManualPaymentCreationService::class)->createPayment($order, [
                    'receipt_path' => $receiptPath,
                    'tracking_number' => $validated['tracking_number'] ?? null,
                    'note' => $validated['note'] ?? null,
                ]);
            }
        } catch (PaymentRetryException|PaymentConstraintViolationException $e) {
            Storage::disk('local')->delete($receiptPath);

            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->route('checkout.success', $order->token);
    }

    private function hasActivePayment(Order $order): bool
    {
        return Payment::query()
            ->where('order_id', $order->id)
            ->active()
            ->exists();
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->user_id !== null && auth()->id() !== $order->user_id) {
            abort(403);
        }
    }
}
