<?php

namespace App\Services;

use App\Contracts\Payments\PaymentInitiationRequest;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Support\GatewayInitiationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provider-agnostic gateway payment initiation.
 *
 * The authoritative amount always originates from the database order total and
 * is handed to the provider exclusively through PaymentInitiationRequest. The
 * browser, query string, forms and callbacks are never treated as a pricing
 * source.
 */
final class GatewayInitiationService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function initiate(Order $order, string $gatewayName): GatewayInitiationResult
    {
        try {
            $gateway = $this->gatewayManager->resolve($gatewayName);
        } catch (UnknownPaymentGatewayException) {
            return GatewayInitiationResult::unavailable('درگاه پرداخت فعال نیست.');
        }

        $amount = (int) $order->total_price;

        $payment = DB::transaction(function () use ($order, $gateway, $amount) {
            $lockedOrder = $this->lockOrder($order->id);

            if ($lockedOrder->payments()->active()->exists()) {
                return null;
            }

            return Payment::create([
                'order_id' => $lockedOrder->id,
                'method' => PaymentMethod::GATEWAY->value,
                'status' => PaymentStatus::PENDING->value,
                'amount' => $amount,
                'gateway' => $gateway->name(),
                'metadata' => [],
            ]);
        });

        if ($payment === null) {
            return GatewayInitiationResult::unavailable('پرداخت فعالی برای این سفارش وجود دارد.');
        }

        $request = new PaymentInitiationRequest(
            orderId: $order->id,
            amount: $amount,
            orderReference: $order->reference,
            callbackUrl: route('checkout.payment.callback', $gateway->name()),
        );

        $result = $gateway->initiate($request);

        if (! $result->success || ! $this->isSafeRedirectUrl($result->redirectUrl)) {
            $this->markInitiationFailed($payment);

            return GatewayInitiationResult::unavailable('امکان شروع پرداخت وجود ندارد. لطفاً بعداً تلاش کنید.');
        }

        $payment->transaction_id = (string) $result->providerReference;
        $payment->save();

        return GatewayInitiationResult::success($payment, (string) $result->redirectUrl);
    }

    private function markInitiationFailed(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = $this->lockPayment($payment->id);

            if ($locked->status !== PaymentStatus::PENDING) {
                return;
            }

            $locked->status = PaymentStatus::FAILED;
            $locked->metadata = array_merge($locked->metadata ?? [], ['reason' => 'initiation_failed']);
            $locked->save();
        });
    }

    private function isSafeRedirectUrl(?string $redirectUrl): bool
    {
        return is_string($redirectUrl)
            && Str::startsWith($redirectUrl, ['https://', 'http://']);
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    private function lockPayment(int $paymentId): Payment
    {
        $query = Payment::query()->where('id', $paymentId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new \RuntimeException('Payment not found.');
    }
}
