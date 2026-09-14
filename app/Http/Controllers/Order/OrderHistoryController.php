<?php

namespace App\Http\Controllers\Order;

use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentConstraintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderHistoryController extends Controller
{
    public function __construct(
        private readonly PaymentConstraintService $constraints,
    ) {}

    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->paginate(10);

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'payments']);

        return view('orders.show', [
            'order' => $order,
            'canRetryPayment' => $this->constraints->canReceivePayment($order)
                && $order->payment_status !== PaymentStatusEnum::PAID
                && $order->payments()->where('status', PaymentStatus::FAILED->value)->exists(),
        ]);
    }
}
