<?php

namespace Tests\Feature;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Contracts\Payments\PaymentVerificationResult;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Http\Controllers\Checkout\ManualTransferPaymentController;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\ManualPaymentRetryService;
use App\Services\ManualPaymentReviewService;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentGatewayAbstractionTest extends TestCase
{
    use RefreshDatabase;

    private function makeManager(array $gateways = ['fake' => FakePaymentGateway::class]): PaymentGatewayManager
    {
        return new PaymentGatewayManager($this->app, $gateways);
    }

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Gateway Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createActiveSetting(): ManualPaymentSetting
    {
        return ManualPaymentSetting::create([
            'card_number' => '6037991234567890',
            'iban' => 'IR012345678901234567890123',
            'account_name' => 'Shop Account',
            'instruction_message' => 'مبلغ را دقیقاً به این کارت واریز کنید.',
            'success_message' => 'رسید شما دریافت شد و پس از بررسی تأیید خواهد شد.',
            'is_active' => true,
        ]);
    }

    private function initiationRequest(Order $order): PaymentInitiationRequest
    {
        return new PaymentInitiationRequest(
            orderId: $order->id,
            amount: (int) $order->total_price,
            orderReference: $order->reference,
            callbackUrl: 'https://shop.example.test/checkout/payment/callback',
        );
    }

    public function test_fake_gateway_implements_the_payment_gateway_contract(): void
    {
        $gateway = new FakePaymentGateway;

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertSame('fake', $gateway->name());
    }

    public function test_gateway_can_initiate_payment_and_return_redirect_information(): void
    {
        $request = $this->initiationRequest($this->createOrder(total: 225000));
        $gateway = new FakePaymentGateway;

        $result = $gateway->initiate($request);

        $this->assertInstanceOf(PaymentInitiationResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotNull($result->redirectUrl);
        $this->assertStringStartsWith('https://', $result->redirectUrl);
        $this->assertNotNull($result->providerReference);
    }

    public function test_initiation_failure_is_represented_as_safe_normalized_result(): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->failOnInitiate = true;

        $result = $gateway->initiate($this->initiationRequest($this->createOrder()));

        $this->assertFalse($result->success);
        $this->assertNull($result->redirectUrl);
        $this->assertNull($result->providerReference);
        $this->assertNotEmpty($result->message);
        $this->assertIsArray($result->metadata);
    }

    public function test_gateway_can_verify_payment_and_return_provider_transaction_data(): void
    {
        $order = $this->createOrder(total: 225000);
        $gateway = new FakePaymentGateway;
        $initiated = $gateway->initiate($this->initiationRequest($order));

        $result = $gateway->verify(
            $this->initiationRequest($order),
            $initiated->providerReference,
            ['status' => 'OK'],
        );

        $this->assertInstanceOf(PaymentVerificationResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotNull($result->providerTransactionId);
        $this->assertSame($initiated->providerReference, $result->providerReference);
        $this->assertSame(225000, $result->amount);
        $this->assertIsArray($result->metadata);
    }

    public function test_verification_failure_does_not_leak_provider_details(): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->failOnVerify = true;

        $result = $gateway->verify(
            $this->initiationRequest($this->createOrder()),
            'REF-X',
            ['authority' => 'top-secret-provider-token'],
        );

        $this->assertFalse($result->success);
        $this->assertStringNotContainsString('top-secret-provider-token', (string) $result->message);
    }

    public function test_gateway_interaction_does_not_mutate_payment_or_order_state(): void
    {
        $order = $this->createOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => (int) $order->total_price,
        ]);
        $gateway = new FakePaymentGateway;
        $request = $this->initiationRequest($order);

        $initiated = $gateway->initiate($request);
        $gateway->verify($request, $initiated->providerReference, []);

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(OrderStatusEnum::PENDING, $order->fresh()->status);
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->fresh()->payment_status);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_gateway_receives_amount_solely_from_payment_core(): void
    {
        $order = $this->createOrder(total: 337000);
        $gateway = new FakePaymentGateway;

        $gateway->initiate($this->initiationRequest($order));

        $this->assertNotNull($gateway->lastInitiationRequest);
        $this->assertSame(337000, $gateway->lastInitiationRequest->amount);
        $this->assertSame($order->id, $gateway->lastInitiationRequest->orderId);
        $this->assertSame($order->reference, $gateway->lastInitiationRequest->orderReference);
        $this->assertStringStartsWith('https://', $gateway->lastInitiationRequest->callbackUrl);
    }

    public function test_resolver_resolves_a_registered_gateway(): void
    {
        $manager = $this->makeManager();

        $gateway = $manager->resolve('fake');

        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertInstanceOf(FakePaymentGateway::class, $gateway);
        $this->assertSame('fake', $gateway->name());
        $this->assertTrue($manager->has('fake'));
        $this->assertSame(['fake'], $manager->names());
    }

    public function test_resolver_rejects_unknown_gateways(): void
    {
        $manager = $this->makeManager();

        foreach (['unknown', '', 'zarinpal', 'idpay'] as $name) {
            $rejected = false;

            try {
                $manager->resolve($name);
            } catch (UnknownPaymentGatewayException) {
                $rejected = true;
            }

            $this->assertTrue($rejected, "Gateway [{$name}] should have been rejected.");
        }

        $this->assertFalse($manager->has('unknown'));
    }

    public function test_resolver_never_instantiates_arbitrary_class_names_from_input(): void
    {
        $manager = $this->makeManager();

        foreach ([User::class, FakePaymentGateway::class, PaymentGatewayManager::class] as $class) {
            $rejected = false;

            try {
                // The name is used only as an allowlist key, never as a class name.
                $manager->resolve($class);
            } catch (UnknownPaymentGatewayException) {
                $rejected = true;
            }

            $this->assertTrue($rejected, "Class [{$class}] must never be instantiated through resolve().");
        }
    }

    public function test_resolver_rejects_gateway_with_mismatched_provider_name(): void
    {
        $manager = new PaymentGatewayManager($this->app, [
            'mismatch' => FakePaymentGateway::class, // provider name is 'fake', not 'mismatch'
        ]);

        $this->expectException(UnknownPaymentGatewayException::class);
        $manager->resolve('mismatch');
    }

    public function test_resolver_rejects_allowlisted_class_that_does_not_implement_contract(): void
    {
        $manager = new PaymentGatewayManager($this->app, [
            'bogus' => ManualPaymentReviewService::class,
        ]);

        $this->expectException(UnknownPaymentGatewayException::class);
        $manager->resolve('bogus');
    }

    public function test_manual_transfer_works_with_empty_gateway_registry(): void
    {
        Storage::fake('local');
        $this->createActiveSetting();
        $order = $this->createOrder();

        $response = $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
            'tracking_number' => 'MANUAL-F5',
        ]);

        $response->assertRedirect(route('checkout.success', $order->token));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'gateway' => null,
        ]);

        $this->assertSame([], app(PaymentGatewayManager::class)->names());
    }

    public function test_production_gateway_registry_contains_no_test_gateway(): void
    {
        $gateways = config('payments.gateways');

        $this->assertSame([], $gateways);
        $this->assertSame([], app(PaymentGatewayManager::class)->names());
        $this->assertNotContains(FakePaymentGateway::class, $gateways);
    }

    public function test_manual_payment_components_have_no_dependency_on_gateway_abstraction(): void
    {
        $components = [
            ManualPaymentReviewService::class,
            ManualPaymentRetryService::class,
            ManualTransferPaymentController::class,
        ];

        $gatewayRelated = [
            PaymentGateway::class,
            PaymentGatewayManager::class,
        ];

        foreach ($components as $component) {
            $reflection = new ReflectionClass($component);

            $this->assertFalse(
                $reflection->implementsInterface(PaymentGateway::class),
                "{$component} must not implement the gateway contract."
            );

            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                continue;
            }

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType) {
                    $this->assertNotContains($type->getName(), $gatewayRelated);
                }
            }
        }

        foreach ($components as $component) {
            $source = (string) file_get_contents((new ReflectionClass($component))->getFileName());

            $this->assertStringNotContainsString('App\Contracts\Payments', $source);
            $this->assertStringNotContainsString('PaymentGatewayManager', $source);
        }
    }
}
