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
        // Public Tracking strictly by Order Reference + Last 4 Digits of Customer Phone
        // Clean and normalize Persian/Arabic digits
        $referenceInput = trim((string) $request->input('reference', ''));
        $phoneInput = trim((string) $request->input('customer_phone_last4', ''));

        $referenceInput = strtr($referenceInput, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'
        ]);

        $phoneInput = strtr($phoneInput, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'
        ]);

        $request->merge([
            'reference' => $referenceInput,
            'customer_phone_last4' => $phoneInput,
        ]);

        $payload = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'customer_phone_last4' => ['required', 'digits:4'],
        ], [
            'reference.required' => 'شماره سفارش الزامی است.',
            'customer_phone_last4.required' => 'وارد کردن ۴ رقم آخر شماره موبایل الزامی است.',
            'customer_phone_last4.digits' => '۴ رقم آخر شماره موبایل باید دقیقاً ۴ رقم باشد.',
        ]);

        // Query matching both reference (e.g. 10001 or ORD-...) AND last 4 digits of customer_phone
        $order = Order::query()
            ->with('items')
            ->where('reference', $payload['reference'])
            ->where('customer_phone', 'like', '%' . $payload['customer_phone_last4'])
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['reference' => 'اطلاعات وارد شده با هیچ سفارشی مطابقت ندارد. لطفاً شماره سفارش و ۴ رقم آخر موبایل را بررسی کنید.']);
        }

        return view('order-tracking.show', [
            'order' => $order,
        ]);
    }
}
