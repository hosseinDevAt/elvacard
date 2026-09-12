<?php

namespace Tests\Feature;

use App\Models\ManualPaymentSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManualPaymentSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_and_manual_settings_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));
        $this->assertTrue(Schema::hasTable('manual_payment_settings'));
    }

    public function test_can_create_settings(): void
    {
        $settings = ManualPaymentSetting::create([
            'card_number' => '6037997000000000',
            'iban' => 'IR123456789012345678901234',
            'account_name' => 'Test Account',
            'instruction_message' => 'مبلغ را کارت‌به‌کارت کنید.',
            'success_message' => 'پرداخت شما در انتظار بررسی است.',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('manual_payment_settings', [
            'id' => $settings->id,
            'card_number' => '6037997000000000',
            'iban' => 'IR123456789012345678901234',
        ]);
    }

    public function test_boolean_cast_works(): void
    {
        ManualPaymentSetting::create([
            'is_active' => true,
        ]);

        $fresh = ManualPaymentSetting::query()->firstOrFail();

        $this->assertIsBool($fresh->is_active);
        $this->assertTrue($fresh->is_active);
    }

    public function test_nullable_fields_work(): void
    {
        $settings = ManualPaymentSetting::create([
            'card_number' => null,
            'iban' => null,
            'account_name' => null,
            'instruction_message' => null,
            'success_message' => null,
            'is_active' => false,
        ]);

        $fresh = $settings->fresh();

        $this->assertNull($fresh->card_number);
        $this->assertNull($fresh->iban);
        $this->assertNull($fresh->account_name);
        $this->assertNull($fresh->instruction_message);
        $this->assertNull($fresh->success_message);
        $this->assertFalse($fresh->is_active);
    }
}