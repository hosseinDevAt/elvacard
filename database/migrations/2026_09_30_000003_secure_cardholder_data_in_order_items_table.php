<?php

use App\Models\OrderItem;
use App\Services\BankCard\BankCardCustomization;
use App\Services\Customization\CardPresenter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely encrypt plaintext PAN at rest and discard CVV2 in all existing order items.
     */
    public function up(): void
    {
        OrderItem::query()
            ->whereNotNull('customization_json')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    $custom = $item->customization_json;
                    if (! is_array($custom)) {
                        continue;
                    }

                    $modified = false;

                    // 1. Plaintext PAN encryption and masking
                    if (isset($custom['card_number']) && is_string($custom['card_number']) && ! empty($custom['card_number'])) {
                        $rawPan = BankCardCustomization::canonicalizeCardNumber($custom['card_number']);
                        if (preg_match('/^[0-9]{16}$/', $rawPan)) {
                            $custom['pan_encrypted'] = Crypt::encryptString($rawPan);
                            $custom['pan_last4'] = substr($rawPan, -4);
                            $custom['card_number_masked'] = CardPresenter::maskPan($rawPan);
                            $custom['pan_hash'] = hash_hmac('sha256', $rawPan, (string) config('app.key'));
                        }
                        unset($custom['card_number']);
                        $modified = true;
                    }

                    // 2. Discard CVV2 sensitive data
                    if (array_key_exists('cvv2', $custom)) {
                        if (! empty($custom['cvv2'])) {
                            $custom['security_cvv_enabled'] = true;
                        }
                        unset($custom['cvv2']);
                        $modified = true;
                    }

                    if ($modified) {
                        $item->customization_json = $custom;
                        $item->saveQuietly();
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     * Decrypt pan_encrypted back to card_number. Discarded CVV2 cannot and must not be restored.
     */
    public function down(): void
    {
        OrderItem::query()
            ->whereNotNull('customization_json')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    $custom = $item->customization_json;
                    if (! is_array($custom)) {
                        continue;
                    }

                    if (! empty($custom['pan_encrypted']) && is_string($custom['pan_encrypted'])) {
                        try {
                            $custom['card_number'] = Crypt::decryptString($custom['pan_encrypted']);
                            unset($custom['pan_encrypted'], $custom['pan_last4'], $custom['card_number_masked'], $custom['pan_hash']);
                            $item->customization_json = $custom;
                            $item->saveQuietly();
                        } catch (Throwable $e) {
                            // Skip undecryptable records
                        }
                    }
                }
            });
    }
};
