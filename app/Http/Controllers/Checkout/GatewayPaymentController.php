<?php

namespace App\Http\Controllers\Checkout;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GatewayInitiationService;
use App\Services\GatewayPaymentCore;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class GatewayPaymentController extends Controller
{
    public function __construct(
        private readonly GatewayPaymentCore $paymentCore,
        private readonly GatewayInitiationService $initiationService,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function initiate(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $gatewayName = $request->input('gateway');

        if (! is_string($gatewayName) || $gatewayName === '' || ! $this->gatewayManager->has($gatewayName)) {
            return back()->withErrors(['payment' => 'درگاه پرداخت فعال نیست.']);
        }

        $result = $this->initiationService->initiate($order, $gatewayName);

        if (! $result->success) {
            return back()->withErrors(['payment' => $result->message]);
        }

        return redirect()->away($result->redirectUrl);
    }

    public function callback(Request $request, string $gateway): JsonResponse
    {
        if (! $this->gatewayManager->has($gateway)) {
            return response()->json(['status' => 'unknown_gateway'], 404);
        }

        $reference = $request->input('reference');

        if (! is_string($reference) || $reference === '') {
            return response()->json(['status' => 'missing_reference'], 422);
        }

        $payment = Payment::query()
            ->where('gateway', $gateway)
            ->where('transaction_id', $reference)
            ->first();

        if (! $payment) {
            return response()->json(['status' => 'unknown_payment'], 404);
        }

        try {
            $status = $this->paymentCore->handleCallback($payment, $request->except(['reference']));
        } catch (\Throwable $e) {
            Log::error('Unhandled gateway callback failure', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $gateway,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'failed']);
        }

        return response()->json(['status' => $status->value]);
    }

    public function return(Order $order): View
    {
        $this->authorizeOrder($order);

        $payment = $order->payments()
            ->where('method', PaymentMethod::GATEWAY->value)
            ->latest('id')
            ->first();

        return view('checkout.gateway-return', [
            'order' => $order,
            'payment' => $payment,
        ]);
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->user_id !== null && auth()->id() !== $order->user_id) {
            abort(403);
        }
    }
}
