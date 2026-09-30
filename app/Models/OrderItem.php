<?php

namespace App\Models;

use App\Enums\CustomizationWorkflowEnum;
use App\Services\BankCard\BankCardCustomization;
use App\Services\Customization\CardPresenter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'customization_workflow',
        'product_name_snapshot',
        'color_id',
        'color_name_snapshot',
        'design_id',
        'design_name_snapshot',
        'design_image_id',
        'design_image_path_snapshot',
        'unit_price_snapshot',
        'quantity',
        'final_price',
        'customization_json',
    ];

    protected $casts = [
        'customization_json' => 'array',
        'customization_workflow' => CustomizationWorkflowEnum::class,
        'color_id' => 'integer',
        'design_id' => 'integer',
        'design_image_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item) {
            if (is_array($item->customization_json)) {
                $custom = $item->customization_json;
                $modified = false;

                // Ensure plaintext PAN is never persisted at rest: encrypt and mask
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

                // Strictly ensure CVV2 sensitive data is never persisted at rest
                if (array_key_exists('cvv2', $custom)) {
                    if (! empty($custom['cvv2'])) {
                        $custom['security_cvv_enabled'] = true;
                    }
                    unset($custom['cvv2']);
                    $modified = true;
                }

                if ($modified) {
                    $item->customization_json = $custom;
                }
            }
        });
    }

    /**
     * Decrypt PAN only for authorized internal fulfillment operations.
     * Never render this return value in HTML or public API payloads.
     */
    public function getDecryptedPan(): ?string
    {
        $custom = $this->customization_json;
        if (! is_array($custom)) {
            return null;
        }

        if (! empty($custom['pan_encrypted']) && is_string($custom['pan_encrypted'])) {
            try {
                return Crypt::decryptString($custom['pan_encrypted']);
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Backward compatibility for legacy rows before migration
        if (! empty($custom['card_number']) && is_string($custom['card_number'])) {
            return BankCardCustomization::canonicalizeCardNumber($custom['card_number']);
        }

        return null;
    }

    /**
     * Safe masked PAN for display across admin and customer views.
     * e.g. "•••• •••• •••• 7898"
     */
    public function getMaskedPan(): ?string
    {
        $custom = $this->customization_json;
        if (! is_array($custom)) {
            return null;
        }

        if (! empty($custom['card_number_masked']) && is_string($custom['card_number_masked'])) {
            return $custom['card_number_masked'];
        }

        if (! empty($custom['pan_last4']) && is_string($custom['pan_last4'])) {
            return CardPresenter::maskPan($custom['pan_last4']);
        }

        if (! empty($custom['card_number']) && is_string($custom['card_number'])) {
            return CardPresenter::maskPan($custom['card_number']);
        }

        return null;
    }

    /**
     * Safe last 4 digits of the card number.
     */
    public function getLast4Pan(): ?string
    {
        $custom = $this->customization_json;
        if (! is_array($custom)) {
            return null;
        }

        if (! empty($custom['pan_last4']) && is_string($custom['pan_last4'])) {
            return $custom['pan_last4'];
        }

        if (! empty($custom['card_number']) && is_string($custom['card_number'])) {
            $digits = preg_replace('/\D/', '', $custom['card_number']);

            return substr($digits, -4);
        }

        return null;
    }

    /**
     * Whether CVV engraving was requested (without exposing any CVV2 value).
     */
    public function isCvvEnabled(): bool
    {
        $custom = $this->customization_json;

        return is_array($custom) && ! empty($custom['security_cvv_enabled']);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function designImage(): BelongsTo
    {
        return $this->belongsTo(DesignImage::class);
    }
}
