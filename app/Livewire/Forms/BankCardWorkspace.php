<?php

namespace App\Livewire\Forms;

use App\Services\BankCard\BankCardCustomization;
use App\Support\Dates\DateService;
use Livewire\Form;

class BankCardWorkspace extends Form
{
    public string $card_number = '';

    public string $card_holder_name = '';

    public string $back_text = '';

    public string $custom_text_placement = 'back'; // 'back' | 'front'

    public string $iban = '';

    public string $cvv2 = '';

    public string $expiry_month = '';

    public string $expiry_year = '';

    public bool $security_cvv_enabled = false;

    public bool $security_expiry_enabled = false;

    public function rules(): array
    {
        $rules = BankCardCustomization::rulesFor(
            $this->security_cvv_enabled,
            $this->security_expiry_enabled,
        );

        if ($this->security_expiry_enabled) {
            $month = $this->expiry_month;
            $rules['expiry_year'][] = function (string $attribute, mixed $value, \Closure $fail) use ($month): void {
                if ($value !== '' && $month !== '' && app(DateService::class)->isPastJalaliExpiry($month, (string) $value)) {
                    $fail('تاریخ انقضای کارت گذشته است؛ لطفاً تاریخی در آینده انتخاب کنید.');
                }
            };
        }

        return $rules;
    }

    public function messages(): array
    {
        return BankCardCustomization::messages();
    }

    public function canonicalize(): void
    {
        $this->card_number = BankCardCustomization::canonicalizeCardNumber($this->card_number);
        $this->iban = BankCardCustomization::canonicalizeIban($this->iban);
    }

    public function toggleCvv(): void
    {
        $this->security_cvv_enabled = ! $this->security_cvv_enabled;

        if (! $this->security_cvv_enabled) {
            $this->cvv2 = '';
        }
    }

    public function toggleExpiry(): void
    {
        $this->security_expiry_enabled = ! $this->security_expiry_enabled;

        if (! $this->security_expiry_enabled) {
            $this->expiry_month = '';
            $this->expiry_year = '';
        }
    }

    public function setCustomTextPlacement(string $placement): void
    {
        if (in_array($placement, ['front', 'back'], true)) {
            $this->custom_text_placement = $placement;
        }
    }

    public function customizationJson(): array
    {
        $customizationJson = [
            'security_cvv_enabled' => $this->security_cvv_enabled,
            'security_expiry_enabled' => $this->security_expiry_enabled,
        ];

        if (trim($this->card_number) !== '') {
            $customizationJson['card_number'] = trim($this->card_number);
        }

        if (trim($this->card_holder_name) !== '') {
            $customizationJson['card_holder_name'] = trim($this->card_holder_name);
        }

        if (trim($this->back_text) !== '') {
            $customizationJson['back_text'] = trim($this->back_text);
            $customizationJson['custom_text_placement'] = $this->custom_text_placement === 'front' ? 'front' : 'back';
        }

        if (trim($this->iban) !== '') {
            $customizationJson['iban'] = trim($this->iban);
        }

        if ($this->security_expiry_enabled) {
            if (trim($this->expiry_month) !== '') {
                $customizationJson['expiry_month'] = trim($this->expiry_month);
            }

            if (trim($this->expiry_year) !== '') {
                $customizationJson['expiry_year'] = trim($this->expiry_year);
            }
        }

        return $customizationJson;
    }
}
