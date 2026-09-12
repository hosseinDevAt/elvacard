<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function index()
    {
        $cart = $this->cartService->getCart();

        return view('cart.index', [
            'cart' => $cart,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'customization_json' => ['nullable', 'array'],
        ]);

        try {
            $this->cartService->addItem($payload);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'cart' => $e->getMessage(),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Item added to cart.');
    }

    public function remove(string $id): RedirectResponse
    {
        $this->cartService->removeItem($id);

        return redirect()->route('cart.index')->with('success', 'Item removed from cart.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $payload = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->cartService->updateQuantity($id, (int) $payload['quantity']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'quantity' => $e->getMessage(),
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart item updated.');
    }

    public function empty(): RedirectResponse
    {
        $this->cartService->clear();

        return redirect()->route('cart.index')->with('success', 'Cart cleared.');
    }
}
