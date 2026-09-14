<?php

namespace Database\Seeders;

use App\Models\ManualPaymentSetting;
use Illuminate\Database\Seeder;

/**
 * Bootstraps the manual transfer (card-to-card) payment configuration row.
 *
 * Financial data (card number, IBAN, account name) is real banking information
 * and is intentionally never hardcoded. This seeder guarantees a deterministic
 * row with id = 1 exists so the payment screen degrades gracefully instead of
 * operating against an empty table.
 *
 * The seeded row is inactive and carries no financial data by default.
 * A production operator must supply real details (via the data layer / a future
 * admin UI) and set is_active to true before card-to-card payments are exposed
 * to customers. Re-running this seeder never modifies an existing row, so an
 * operator-configured setting is never overwritten.
 */
class ManualPaymentSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (ManualPaymentSetting::query()->whereKey(1)->exists()) {
            return;
        }

        $setting = new ManualPaymentSetting;
        $setting->id = 1;
        $setting->card_number = null;
        $setting->iban = null;
        $setting->account_name = null;
        $setting->instruction_message = null;
        $setting->success_message = 'پرداخت شما دریافت شد و در انتظار بررسی است.';
        $setting->is_active = false;
        $setting->save();
    }
}
