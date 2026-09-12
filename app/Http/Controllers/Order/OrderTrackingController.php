<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function create(): View
    {
        return view('order-tracking.create');
    }

    public function store(Request $request): RedirectResponse|\Illuminate\View\View
    {
        $payload = $request->validate([
            'reference' => ['required', 'string', 'max:32', 'regex:/^ORD-\d{4}-\d{6}$/'],
            'customer_phone' => ['required', 'string', 'digits:11'],
        ]);

        $order = Order::query()
            ->with('items')
            ->where('reference', $payload['reference'])
            ->where('customer_phone', $payload['customer_phone'])
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['reference' => 'سفارشی با این شماره و شماره تماس یافت نشد.']);
        }

        return view('order-tracking.show', [
            'order' => $order,
        ]);
    }
}