<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderHistoryController extends Controller
{
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
        ]);
    }
}
