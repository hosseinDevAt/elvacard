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

    public function store(Request $request): RedirectResponse|View
    {
        $payload = $request->validate([
            'token' => ['required', 'string', 'size:40'],
        ]);

        $order = Order::query()
            ->with('items')
            ->where('token', $payload['token'])
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['token' => 'سفارشی با این کد پیگیری یافت نشد.']);
        }

        return view('order-tracking.show', [
            'order' => $order,
        ]);
    }
}
