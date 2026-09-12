<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualTransferFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(): Order
    {
        $order = new Order([
            'customer_name' => 'Manual Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = 150000;
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

    public function test_customer_can_see_payment_page(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();

        $response = $this->get(route('checkout.payment', $order));

        $response->assertOk();
        $response->assertSee($order->reference);
        $response->assertSee('150,000');
        $response->assertSee('receipt_image');
    }

    public function test_payment_page_shows_active_settings(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $setting = $this->createActiveSetting();

        $response = $this->get(route('checkout.payment', $order));

        $response->assertOk();
        $response->assertSee($setting->card_number);
        $response->assertSee($setting->iban);
        $response->assertSee($setting->account_name);
        $response->assertSee($setting->instruction_message);
    }

    public function test_payment_page_hides_inactive_settings(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        ManualPaymentSetting::create([
            'card_number' => '6037991234567890',
            'iban' => 'IR012345678901234567890123',
            'account_name' => 'Shop Account',
            'instruction_message' => 'مبلغ را دقیقاً به این کارت واریز کنید.',
            'success_message' => 'رسید شما دریافت شد.',
            'is_active' => false,
        ]);

        $response = $this->get(route('checkout.payment', $order));

        $response->assertOk();
        $response->assertDontSee('6037991234567890');
        $response->assertSee('پرداخت کارت‌به‌کارت در حال حاضر فعال نیست');
    }

    public function test_valid_receipt_creates_pending_review_payment_and_order_stays_unpaid(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();

        $response = $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
            'tracking_number' => '123456789',
            'note' => 'از طرف دوستم واریز کردم',
        ]);

        $response->assertRedirect(route('checkout.success', $order->token));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'amount' => 150000,
            'tracking_code' => '123456789',
        ]);

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment->receipt_path);
        Storage::disk('local')->assertExists($payment->receipt_path);
        $this->assertSame(['note' => 'از طرف دوستم واریز کردم'], $payment->metadata);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_invalid_receipt_file_is_rejected(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();

        $response = $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->create('receipt.txt', 100),
        ]);

        $response->assertSessionHasErrors('receipt_image');
        $this->assertDatabaseCount('payments', 0);
        Storage::disk('local')->assertDirectoryEmpty('payment_receipts');
    }

    public function test_duplicate_submit_does_not_create_duplicate_payments(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();

        $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
        ]);

        $response = $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
        ]);

        $response->assertRedirect(route('checkout.success', $order->token));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_page_redirects_to_success_when_payment_already_submitted(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();

        $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
        ]);

        $response = $this->get(route('checkout.payment', $order));

        $response->assertRedirect(route('checkout.success', $order->token));
    }

    public function test_manual_transfer_blocked_when_no_active_setting(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();

        $response = $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
        ]);

        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 0);
    }
}