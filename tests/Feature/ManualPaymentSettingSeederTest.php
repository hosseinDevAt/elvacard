<?php

namespace Tests\Feature;

use App\Models\ManualPaymentSetting;
use Database\Seeders\ManualPaymentSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPaymentSettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_inactive_singleton_row(): void
    {
        $this->seed(ManualPaymentSettingSeeder::class);

        $row = ManualPaymentSetting::query()->findOrFail(1);
        $this->assertFalse($row->is_active);
        $this->assertNull($row->card_number);
        $this->assertNull($row->iban);
        $this->assertNull($row->account_name);
        $this->assertNull($row->instruction_message);
        $this->assertSame(1, ManualPaymentSetting::query()->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ManualPaymentSettingSeeder::class);
        $this->seed(ManualPaymentSettingSeeder::class);

        $this->assertSame(1, ManualPaymentSetting::query()->count());
        $this->assertFalse(ManualPaymentSetting::query()->findOrFail(1)->is_active);
    }

    public function test_seeder_does_not_overwrite_an_existing_active_setting(): void
    {
        $this->seed(ManualPaymentSettingSeeder::class);

        ManualPaymentSetting::query()->whereKey(1)->update([
            'card_number' => '6037997000000000',
            'iban' => 'IR123456789012345678901234',
            'account_name' => 'Test Account',
            'is_active' => true,
        ]);

        $this->seed(ManualPaymentSettingSeeder::class);

        $this->assertSame(1, ManualPaymentSetting::query()->count());
        $this->assertDatabaseHas('manual_payment_settings', [
            'id' => 1,
            'is_active' => true,
            'account_name' => 'Test Account',
        ]);
    }
}
