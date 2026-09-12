<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(): Order
    {
        $order = new Order([
            'customer_name' => 'Pay Tester',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = 150000;
        $order->save();

        return $order;
    }

    public function test_payment_belongs_to_order(): void
    {
        $order = $this->createOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => 150000,
        ]);

        $this->assertTrue($payment->order()->exists());
        $this->assertSame($order->id, $payment->order->id);
    }

    public function test_order_has_payments(): void
    {
        $order = $this->createOrder();

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => 150000,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 150000,
            'paid_at' => now(),
        ]);

        $this->assertCount(2, $order->payments);
        $this->assertCount(2, $order->payments()->get());
    }

    public function test_enum_casting_works(): void
    {
        $order = $this->createOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 150000,
            'metadata' => ['tracking_label' => 'کارت‌به‌کارت'],
            'paid_at' => now(),
        ]);

        $fresh = $payment->fresh();

        $this->assertInstanceOf(PaymentMethod::class, $fresh->method);
        $this->assertSame(PaymentMethod::MANUAL_TRANSFER, $fresh->method);
        $this->assertInstanceOf(PaymentStatus::class, $fresh->status);
        $this->assertSame(PaymentStatus::SUCCESS, $fresh->status);
        $this->assertIsArray($fresh->metadata);
        $this->assertSame('کارت‌به‌کارت', $fresh->metadata['tracking_label']);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->paid_at);
    }

    public function test_payment_cascade_deletes_with_order(): void
    {
        $order = $this->createOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => 150000,
        ]);

        $order->delete();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }
}