<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Services\CartService;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index()
    {
        $cart = $this->cartService->getCart();

        if (empty($cart['items'])) {
            return redirect()->route('cart.index')->with('error', 'سبد خرید شما خالی است.');
        }

        $submissionToken = session('checkout_submission_token');

        if (! is_string($submissionToken) || strlen($submissionToken) < 16) {
            $submissionToken = Str::random(40);
            session(['checkout_submission_token' => $submissionToken]);
        }

        return view('checkout.index', [
            'cart' => $cart,
            'submissionToken' => $submissionToken,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'customer_phone' => normalize_phone((string) $request->input('customer_phone')),
            'shipping_postal_code' => $request->filled('shipping_postal_code')
                ? strtr((string) $request->input('shipping_postal_code'), [
                    '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
                    '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'
                ])
                : null,
            'shipping_plaque' => $request->filled('shipping_plaque')
                ? trim((string) $request->input('shipping_plaque'))
                : null,
        ]);

        $payload = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_valid_iranian_mobile($value)) {
                    $fail('شماره موبایل معتبر نیست. نمونه صحیح: 09123456789');
                }
            }],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'shipping_postal_code' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'shipping_plaque' => ['required', 'string', 'max:50'],
            'shipping_description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'submission_token' => ['required', 'string', 'regex:/^[A-Za-z0-9]{16,64}$/'],
        ], [
            'shipping_plaque.required' => 'وارد کردن پلاک الزامی است.',
            'shipping_postal_code.regex' => 'کد پستی باید ۱۰ رقم باشد.',
        ]);

        $submissionToken = $payload['submission_token'];

        $existing = Order::query()->where('token', $submissionToken)->first();

        if ($existing) {
            return $this->duplicateOrderRedirect($existing);
        }

        try {
            $order = $this->cartService->createDraftOrder(
                $payload,
                null,
                $submissionToken,
            );
        } catch (UniqueConstraintViolationException $e) {
            $existing = Order::query()->where('token', $submissionToken)->first();

            if ($existing) {
                return $this->duplicateOrderRedirect($existing);
            }

            throw $e;
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'cart' => $e->getMessage(),
            ]);
        }

        session()->forget('checkout_submission_token');

        return redirect()->route('checkout.payment', $order->token);
    }

    public function success(string $token): View
    {
        $order = Order::query()
            ->with('items')
            ->where('token', $token)
            ->firstOrFail();

        return view('checkout.success', [
            'order' => $order,
            'payment' => $order->payments()->orderByDesc('id')->first(),
            'activeSetting' => ManualPaymentSetting::query()->where('is_active', true)->first(),
        ]);
    }

    private function duplicateOrderRedirect(Order $order): RedirectResponse
    {
        $hasActivePayment = $order->payments()->active()->exists();

        $route = $hasActivePayment ? 'checkout.success' : 'checkout.payment';

        return redirect()
            ->route($route, $order->token)
            ->with('info', 'این سفارش قبلاً ثبت شده است؛ سفارش تکراری ایجاد نشد.');
    }
}
