<?php

namespace App\Services;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    private const SESSION_KEY = 'cart';

    private const MAX_QUANTITY = 20;

    public function getCart(): array
    {
        $cart = session(self::SESSION_KEY, ['items' => []]);

        if (! is_array($cart) || ! isset($cart['items']) || ! is_array($cart['items'])) {
            $cart = ['items' => []];
        }

        $cart['items'] = array_values($cart['items']);
        $cart['total_quantity'] = array_sum(array_map(fn (array $item) => (int) ($item['quantity'] ?? 0), $cart['items']));
        $cart['total_price'] = array_sum(array_map(fn (array $item) => (int) ($item['final_price'] ?? 0), $cart['items']));

        return $cart;
    }

    public function addItem(array $payload): array
    {
        $validated = $this->validatePayload($payload, false);
        $cart = $this->getCart();

        $existingIndex = $this->findDuplicateItemIndex($cart['items'], $validated);

        if ($existingIndex !== null) {
            $newQuantity = min((int) $cart['items'][$existingIndex]['quantity'] + $validated['quantity'], self::MAX_QUANTITY);
            $cart['items'][$existingIndex]['quantity'] = $newQuantity;
            $cart['items'][$existingIndex]['unit_price_snapshot'] = $validated['unit_price_snapshot'];
            $cart['items'][$existingIndex]['final_price'] = $newQuantity * $validated['unit_price_snapshot'];
            $cart['items'][$existingIndex]['customization_json'] = $validated['customization_json'];
            $cart['items'][$existingIndex]['product_name_snapshot'] = $validated['product_name_snapshot'];
            $cart['items'][$existingIndex]['color_name_snapshot'] = $validated['color_name_snapshot'];
            $cart['items'][$existingIndex]['design_name_snapshot'] = $validated['design_name_snapshot'];
            $cart['items'][$existingIndex]['design_image_path_snapshot'] = $validated['design_image_path_snapshot'];
        } else {
            $cart['items'][] = [
                'id' => (string) Str::uuid(),
                'product_id' => $validated['product_id'],
                'color_id' => $validated['color_id'],
                'color_name_snapshot' => $validated['color_name_snapshot'],
                'design_id' => $validated['design_id'],
                'design_name_snapshot' => $validated['design_name_snapshot'],
                'design_image_id' => $validated['design_image_id'],
                'design_image_path_snapshot' => $validated['design_image_path_snapshot'],
                'quantity' => $validated['quantity'],
                'product_name_snapshot' => $validated['product_name_snapshot'],
                'unit_price_snapshot' => $validated['unit_price_snapshot'],
                'final_price' => $validated['final_price'],
                'customization_json' => $validated['customization_json'],
            ];
        }

        $this->store($cart['items']);

        return $this->getCart();
    }

    public function removeItem(string $id): array
    {
        $cart = $this->getCart();
        $cart['items'] = array_values(array_filter(
            $cart['items'],
            fn (array $item) => (string) ($item['id'] ?? '') !== $id
        ));

        $this->store($cart['items']);

        return $this->getCart();
    }

    public function updateQuantity(string $id, int $quantity): array
    {
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw new InvalidArgumentException('Quantity must be between 1 and '.self::MAX_QUANTITY.'.');
        }

        $cart = $this->getCart();

        foreach ($cart['items'] as $index => $item) {
            if ((string) ($item['id'] ?? '') !== $id) {
                continue;
            }

            $payload = [
                'product_id' => (int) $item['product_id'],
                'color_id' => isset($item['color_id']) && $item['color_id'] !== '' ? (int) $item['color_id'] : null,
                'design_id' => isset($item['design_id']) && $item['design_id'] !== '' ? (int) $item['design_id'] : null,
                'design_image_id' => isset($item['design_image_id']) && $item['design_image_id'] !== ''
                    ? (int) $item['design_image_id']
                    : null,
                'quantity' => $quantity,
                'customization_json' => is_array($item['customization_json'] ?? null) ? $item['customization_json'] : [],
            ];

            $validated = $this->validatePayload($payload, true);

            $cart['items'][$index]['quantity'] = $validated['quantity'];
            $cart['items'][$index]['unit_price_snapshot'] = $validated['unit_price_snapshot'];
            $cart['items'][$index]['final_price'] = $validated['final_price'];
            $cart['items'][$index]['customization_json'] = $validated['customization_json'];
            $cart['items'][$index]['product_name_snapshot'] = $validated['product_name_snapshot'];
            $cart['items'][$index]['color_name_snapshot'] = $validated['color_name_snapshot'];
            $cart['items'][$index]['design_name_snapshot'] = $validated['design_name_snapshot'];
            $cart['items'][$index]['design_image_path_snapshot'] = $validated['design_image_path_snapshot'];

            $this->store($cart['items']);

            return $this->getCart();
        }

        throw new InvalidArgumentException('Cart item not found.');
    }

    public function clear(): array
    {
        session()->forget(self::SESSION_KEY);

        return $this->getCart();
    }

    public function getValidatedCheckoutItems(): array
    {
        $cart = $this->getCart();

        if (empty($cart['items'])) {
            throw new InvalidArgumentException('Cart is empty.');
        }

        $validatedItems = [];

        foreach ($cart['items'] as $item) {
            $validatedItems[] = $this->validatePayload([
                'product_id' => (int) ($item['product_id'] ?? 0),
                'color_id' => isset($item['color_id']) && $item['color_id'] !== '' ? (int) $item['color_id'] : null,
                'design_id' => isset($item['design_id']) && $item['design_id'] !== '' ? (int) $item['design_id'] : null,
                'design_image_id' => isset($item['design_image_id']) && $item['design_image_id'] !== ''
                    ? (int) $item['design_image_id']
                    : null,
                'quantity' => (int) ($item['quantity'] ?? 1),
                'customization_json' => is_array($item['customization_json'] ?? null) ? $item['customization_json'] : [],
            ], true) + [
                'id' => (string) ($item['id'] ?? Str::uuid()),
            ];
        }

        return $validatedItems;
    }

    public function createDraftOrder(array $customerData, ?int $userId = null, ?string $idempotencyToken = null): Order
    {
        $validatedItems = $this->getValidatedCheckoutItems();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(function () use ($customerData, $validatedItems, $userId, $idempotencyToken) {
                    $order = new Order;
                    $order->user_id = $userId;
                    $order->customer_name = $customerData['customer_name'];
                    $order->customer_phone = $customerData['customer_phone'];
                    $order->shipping_address = $customerData['shipping_address'] ?? null;
                    $order->shipping_postal_code = $customerData['shipping_postal_code'] ?? null;
                    $order->shipping_plaque = $customerData['shipping_plaque'] ?? null;
                    $order->shipping_description = $customerData['shipping_description'] ?? null;
                    $order->token = $idempotencyToken;
                    $order->status = OrderStatusEnum::PENDING;
                    $order->payment_status = PaymentStatusEnum::UNPAID;
                    $order->total_price = 0;
                    $order->notes = $customerData['notes'] ?? null;
                    $order->save();

                    $total = 0;

                    foreach ($validatedItems as $item) {
                        OrderItem::query()->create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'product_name_snapshot' => $item['product_name_snapshot'],
                            'color_id' => $item['color_id'],
                            'color_name_snapshot' => $item['color_name_snapshot'],
                            'design_id' => $item['design_id'],
                            'design_name_snapshot' => $item['design_name_snapshot'],
                            'design_image_id' => $item['design_image_id'],
                            'design_image_path_snapshot' => $item['design_image_path_snapshot'],
                            'unit_price_snapshot' => $item['unit_price_snapshot'],
                            'quantity' => $item['quantity'],
                            'final_price' => $item['final_price'],
                            'customization_json' => $item['customization_json'],
                        ]);

                        $total += (int) $item['final_price'];
                    }

                    $order->total_price = $total;
                    $order->save();

                    $this->clear();

                    return $order;
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }

                // Reference for this year may have just been taken by a concurrent
                // checkout; regenerate it and retry once more.
            }
        }

        throw new \LogicException('Unable to persist order after retries.');
    }

    private function validatePayload(array $payload, bool $forExisting): array
    {
        $productId = (int) ($payload['product_id'] ?? 0);
        $colorId = isset($payload['color_id']) && $payload['color_id'] !== '' ? (int) $payload['color_id'] : null;
        $designId = isset($payload['design_id']) && $payload['design_id'] !== '' ? (int) $payload['design_id'] : null;
        $designImageId = isset($payload['design_image_id']) && $payload['design_image_id'] !== ''
            ? (int) $payload['design_image_id']
            : null;
        $quantity = (int) ($payload['quantity'] ?? 1);

        if ($productId < 1) {
            throw new InvalidArgumentException('Invalid product selection.');
        }

        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw new InvalidArgumentException('Quantity must be between 1 and '.self::MAX_QUANTITY.'.');
        }

        $product = Product::query()->active()->find($productId);

        if (! $product) {
            throw new InvalidArgumentException('Selected product is not available.');
        }

        $workflowRaw = $product->getRawOriginal('customization_workflow');
        $workflow = $workflowRaw !== null ? CustomizationWorkflowEnum::tryFrom((string) $workflowRaw) : null;

        if ($workflowRaw !== null && $workflow === null) {
            throw new InvalidArgumentException('Selected product has an unsupported customization workflow.');
        }

        if ($workflow !== null && ! CustomizationWorkflowRegistry::isActive($workflow)) {
            throw new InvalidArgumentException('Selected product customization is currently unavailable.');
        }

        if ($workflow === null) {
            return $this->validateCommercePayload($product, $colorId, $quantity, $forExisting);
        }

        return $this->validateBankCardPayload($product, $colorId, $designId, $designImageId, $quantity, $payload, $forExisting);
    }

    private function validateCommercePayload($product, ?int $colorId, int $quantity, bool $forExisting): array
    {
        $colorPrice = null;
        $colorName = null;
        $unitPrice = $product->base_price !== null ? (int) $product->base_price : null;

        if ($colorId !== null) {
            $colorPrice = ProductColorPrice::query()
                ->where('product_id', $product->id)
                ->where('color_id', $colorId)
                ->where('is_active', true)
                ->with(['color' => fn ($query) => $query->where('is_active', true)])
                ->first();

            if (! $colorPrice || ! $colorPrice->color) {
                throw new InvalidArgumentException('Selected color is not valid for this product.');
            }

            $unitPrice = (int) $colorPrice->price;
            $colorName = $colorPrice->color->name;
        }

        if ($unitPrice === null) {
            throw new InvalidArgumentException('Selected product has no base price.');
        }

        return [
            'product_id' => $product->id,
            'color_id' => $colorId,
            'color_name_snapshot' => $colorName,
            'design_id' => null,
            'design_name_snapshot' => null,
            'design_image_id' => null,
            'design_image_path_snapshot' => null,
            'quantity' => $quantity,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => $unitPrice,
            'final_price' => $unitPrice * $quantity,
            'customization_json' => [],
            'for_existing' => $forExisting,
        ];
    }

    private function validateBankCardPayload($product, ?int $colorId, ?int $designId, ?int $designImageId, int $quantity, array $payload, bool $forExisting): array
    {
        if ($colorId === null || $designId === null) {
            throw new InvalidArgumentException('Invalid product/color/design selection.');
        }

        $colorPrice = ProductColorPrice::query()
            ->where('product_id', $product->id)
            ->where('color_id', $colorId)
            ->where('is_active', true)
            ->with(['color' => fn ($query) => $query->where('is_active', true)])
            ->first();

        if (! $colorPrice || ! $colorPrice->color) {
            throw new InvalidArgumentException('Selected color is not valid for this product.');
        }

        $design = Design::query()->active()->find($designId);

        if (! $design) {
            throw new InvalidArgumentException('Selected design is not available.');
        }

        $designImage = null;

        if ($designImageId !== null) {
            $designImage = DesignImage::query()->active()->find($designImageId);

            if (! $designImage) {
                throw new InvalidArgumentException('Selected design image is not available.');
            }

            if ((int) $designImage->design_id !== $designId) {
                throw new InvalidArgumentException('Design image does not belong to selected design.');
            }

            $compatible = DesignColorCompatibility::query()
                ->where('design_image_id', $designImageId)
                ->where('card_color_id', $colorId)
                ->where('is_allowed', true)
                ->exists();

            if (! $compatible) {
                throw new InvalidArgumentException('Selected design image is not compatible with selected color.');
            }
        }

        return [
            'product_id' => $product->id,
            'color_id' => $colorId,
            'color_name_snapshot' => $colorPrice->color->name,
            'design_id' => $designId,
            'design_name_snapshot' => $design->name,
            'design_image_id' => $designImageId,
            'design_image_path_snapshot' => $designImage?->image_path,
            'quantity' => $quantity,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => (int) $colorPrice->price,
            'final_price' => (int) $colorPrice->price * $quantity,
            'customization_json' => $this->sanitizeCardCustomization($payload),
            'for_existing' => $forExisting,
        ];
    }

    private function sanitizeCardCustomization(array $payload): array
    {
        $rawCustomization = is_array($payload['customization_json'] ?? null)
            ? $payload['customization_json']
            : [];

        $sanitizedCustomization = [];

        if (! empty($rawCustomization['card_number']) && is_string($rawCustomization['card_number'])) {
            $cardNumber = $this->canonicalizeCardNumber($rawCustomization['card_number']);
            if (preg_match('/^[0-9]{16}$/', $cardNumber) === 1) {
                $sanitizedCustomization['card_number'] = $cardNumber;
            }
        }

        if (! empty($rawCustomization['card_holder_name']) && is_string($rawCustomization['card_holder_name'])) {
            $name = mb_substr(trim($rawCustomization['card_holder_name']), 0, 100);
            if ($name !== '') {
                $sanitizedCustomization['card_holder_name'] = $name;
            }
        }

        if (! empty($rawCustomization['back_text']) && is_string($rawCustomization['back_text'])) {
            $text = mb_substr(trim($rawCustomization['back_text']), 0, 255);
            if ($text !== '') {
                $sanitizedCustomization['back_text'] = $text;
            }
        }

        if (isset($rawCustomization['security_cvv_enabled'])) {
            $sanitizedCustomization['security_cvv_enabled'] = (bool) $rawCustomization['security_cvv_enabled'];
            if ($sanitizedCustomization['security_cvv_enabled'] && ! empty($rawCustomization['cvv2']) && is_string($rawCustomization['cvv2'])) {
                $cvv = $this->canonicalizeCardNumber($rawCustomization['cvv2']);
                if (preg_match('/^[0-9]{3,4}$/', $cvv) === 1) {
                    $sanitizedCustomization['cvv2'] = $cvv;
                }
            }
        }

        if (isset($rawCustomization['security_expiry_enabled'])) {
            $sanitizedCustomization['security_expiry_enabled'] = (bool) $rawCustomization['security_expiry_enabled'];
            if ($sanitizedCustomization['security_expiry_enabled']) {
                if (! empty($rawCustomization['expiry_month']) && is_string($rawCustomization['expiry_month'])) {
                    $month = substr(trim($rawCustomization['expiry_month']), 0, 2);
                    if (preg_match('/^(0[1-9]|1[0-2])$/', $month) === 1) {
                        $sanitizedCustomization['expiry_month'] = $month;
                    }
                }
                if (! empty($rawCustomization['expiry_year']) && is_string($rawCustomization['expiry_year'])) {
                    $year = substr(trim($rawCustomization['expiry_year']), 0, 2);
                    $currentShort = (int) date('y');
                    if (preg_match('/^[0-9]{2}$/', $year) === 1 && (int) $year >= $currentShort && (int) $year <= $currentShort + 10) {
                        $sanitizedCustomization['expiry_year'] = $year;
                    }
                }
            }
        }

        return $sanitizedCustomization;
    }

    private function canonicalizeCardNumber(string $value): string
    {
        // Presentation separators (spaces/dashes) and Persian/Arabic digit
        // glyphs are tolerated on input but the canonical snapshot form is
        // ASCII digits without separators.
        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[\s\-]+/', '', $value) ?? '';
    }

    private function findDuplicateItemIndex(array $items, array $validated): ?int
    {
        foreach ($items as $index => $item) {
            if (
                (int) ($item['product_id'] ?? 0) === $validated['product_id']
                && (int) ($item['color_id'] ?? 0) === (int) ($validated['color_id'] ?? 0)
                && (int) ($item['design_id'] ?? 0) === (int) ($validated['design_id'] ?? 0)
                && ((int) ($item['design_image_id'] ?? 0) === (int) ($validated['design_image_id'] ?? 0))
            ) {
                return $index;
            }
        }

        return null;
    }

    private function store(array $items): void
    {
        session([self::SESSION_KEY => ['items' => array_values($items)]]);
    }
}
