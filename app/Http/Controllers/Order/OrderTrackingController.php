<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Closure;
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
        $request->merge([
            'customer_phone' => normalize_phone((string) $request->input('customer_phone')),
        ]);

        $payload = $request->validate([
            'reference' => ['required', 'string', 'max:32', 'regex:/^ORD-\d{4}-\d{6}$/'],
            'customer_phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_valid_iranian_mobile($value)) {
                    $fail('شماره موبایل معتبر نیست. نمونه صحیح: 09123456789');
                }
            }],
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
