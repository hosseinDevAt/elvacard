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
            return redirect()->route('cart.index')->with('error', 'Cart is empty.');
        }

        $submissionToken = session('checkout_submission_token');

        if (! is_string($submissionToken) || strlen($submissionToken) < 16) {
            $submissionToken = Str::random(40);
            session(['checkout_submission_token' => $submissionToken]);
        }

        return view('checkout.index', [
            'cart' => $cart,
            'submissionToken' => $submissionToken,
            'customer' => $this->customerPrefill(),
        ]);
    }

    private function customerPrefill(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return [
            'name' => $user->displayName(),
            'phone' => $user->phone,
            'address' => $user->address,
            'postal_code' => $user->postal_code,
            'plaque' => $user->plaque,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'customer_phone' => normalize_phone((string) $request->input('customer_phone')),
        ]);

        $payload = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_valid_iranian_mobile($value)) {
                    $fail('شماره موبایل معتبر نیست. نمونه صحیح: 09123456789');
                }
            }],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'shipping_postal_code' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'shipping_plaque' => ['nullable', 'string', 'max:50'],
            'shipping_description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'submission_token' => ['required', 'string', 'regex:/^[A-Za-z0-9]{16,64}$/'],
        ]);

        $submissionToken = $payload['submission_token'];

        $existing = Order::query()->where('token', $submissionToken)->first();

        if ($existing) {
            return $this->duplicateOrderRedirect($existing);
        }

        try {
            $order = $this->cartService->createDraftOrder(
                $payload,
                auth()->id(),
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

        if ($order->user_id !== null && auth()->id() !== $order->user_id) {
            abort(403);
        }

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
