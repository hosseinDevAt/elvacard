<?php

namespace Tests\Feature;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentReviewException;
use App\Livewire\Admin\OrderManager;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\ManualPaymentReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Review Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function createCustomer(): User
    {
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();

        return $customer;
    }

    private function createPendingReviewPayment(Order $order, int $amount = 150000, ?string $receiptPath = null): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'amount' => $amount,
            'tracking_code' => 'TRACK-1234',
            'receipt_path' => $receiptPath,
            'metadata' => ['note' => 'رسید را ارسال کردم'],
        ]);
    }

    public function test_guest_cannot_access_receipt(): void
    {
        Storage::fake('local');
        $receiptPath = 'payment_receipts/guest.png';
        Storage::disk('local')->put($receiptPath, 'image-bytes');

        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order, receiptPath: $receiptPath);

        $this->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_receipt(): void
    {
        Storage::fake('local');
        $receiptPath = 'payment_receipts/customer.png';
        Storage::disk('local')->put($receiptPath, 'image-bytes');

        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order, receiptPath: $receiptPath);

        $this->actingAs($this->createCustomer())
            ->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertForbidden();
    }

    public function test_admin_can_access_receipt(): void
    {
        Storage::fake('local');
        $receiptPath = 'payment_receipts/admin.png';
        Storage::disk('local')->put($receiptPath, 'image-bytes');

        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order, receiptPath: $receiptPath);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertOk();
    }

    public function test_customer_cannot_approve_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);

        Livewire::actingAs($this->createCustomer())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('approvePayment', $payment->id);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING_REVIEW->value,
        ]);
        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_customer_cannot_reject_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);

        Livewire::actingAs($this->createCustomer())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('rejectPayment', $payment->id);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING_REVIEW->value,
        ]);
    }

    public function test_admin_can_approve_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('approvePayment', $payment->id);

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame($payment->amount, $payment->paid_amount);
        $this->assertNotNull($payment->paid_at);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
    }

    public function test_approval_uses_order_state_machine_pending_to_confirmed(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);

        app(ManualPaymentReviewService::class)->approve($payment);

        $order->refresh();
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
    }

    public function test_approval_does_not_force_illegal_lifecycle_transition(): void
    {
        $order = $this->createOrder();
        $order->status = OrderStatusEnum::COMPLETED;
        $order->save();

        $payment = $this->createPendingReviewPayment($order);

        app(ManualPaymentReviewService::class)->approve($payment);

        $order->refresh();
        $this->assertSame(OrderStatusEnum::COMPLETED, $order->status);
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_admin_can_reject_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('rejectPayment', $payment->id);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->paid_amount);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_approve_twice_is_idempotent(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);
        $service = app(ManualPaymentReviewService::class);

        $service->approve($payment);

        $this->expectException(PaymentReviewException::class);
        $service->approve($payment);
    }

    public function test_reject_twice_is_idempotent(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);
        $service = app(ManualPaymentReviewService::class);

        $service->reject($payment);

        $this->expectException(PaymentReviewException::class);
        $service->reject($payment);
    }

    public function test_approve_after_reject_is_blocked(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);
        $service = app(ManualPaymentReviewService::class);

        $service->reject($payment);

        $this->expectException(PaymentReviewException::class);
        $service->approve($payment);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_reject_after_approve_is_blocked(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order);
        $service = app(ManualPaymentReviewService::class);

        $service->approve($payment);

        $this->expectException(PaymentReviewException::class);
        $service->reject($payment);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_approval_blocks_mismatched_payment_amount(): void
    {
        $order = $this->createOrder(total: 150000);
        $payment = $this->createPendingReviewPayment($order, amount: 999999);

        $this->expectException(PaymentReviewException::class);
        app(ManualPaymentReviewService::class)->approve($payment);
    }

    public function test_receipt_path_traversal_fails(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('secret.txt', 'secret');

        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order, receiptPath: '../secret.txt');

        $this->actingAs($this->createAdmin())
            ->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertNotFound();
    }

    public function test_missing_receipt_file_returns_404(): void
    {
        Storage::fake('local');

        $order = $this->createOrder();
        $payment = $this->createPendingReviewPayment($order, receiptPath: 'payment_receipts/missing.png');

        $this->actingAs($this->createAdmin())
            ->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertNotFound();
    }

    public function test_receipt_from_another_order_cannot_be_accessed(): void
    {
        Storage::fake('local');
        $receiptPath = 'payment_receipts/other.png';
        Storage::disk('local')->put($receiptPath, 'image-bytes');

        $orderA = $this->createOrder();
        $orderB = $this->createOrder();
        $paymentB = $this->createPendingReviewPayment($orderB, receiptPath: $receiptPath);

        $this->actingAs($this->createAdmin())
            ->get(route('admin.payments.receipt', ['order' => $orderA, 'payment' => $paymentB]))
            ->assertNotFound();
    }

    public function test_payment_of_another_order_cannot_be_approved_through_ui(): void
    {
        $orderA = $this->createOrder();
        $orderB = $this->createOrder();
        $paymentB = $this->createPendingReviewPayment($orderB);

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $orderA->id)
            ->call('approvePayment', $paymentB->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $paymentB->id,
            'status' => PaymentStatus::PENDING_REVIEW->value,
        ]);
    }

    public function test_amount_comes_from_database_not_request(): void
    {
        $order = $this->createOrder(total: 225000);
        $payment = $this->createPendingReviewPayment($order, amount: 225000);

        $service = app(ManualPaymentReviewService::class);
        $service->approve($payment);

        $payment->refresh();
        $this->assertSame(225000, $payment->paid_amount);
        $this->assertSame(225000, $payment->amount);
    }
}