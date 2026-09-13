<?php

namespace App\Livewire\Forms;

use App\Services\BankCard\BankCardCustomization;
use Livewire\Form;

class BankCardWorkspace extends Form
{
    public string $card_number = '';

    public string $card_holder_name = '';

    public string $back_text = '';

    public string $cvv2 = '';

    public string $expiry_month = '';

    public string $expiry_year = '';

    public bool $security_cvv_enabled = false;

    public bool $security_expiry_enabled = false;

    public function rules(): array
    {
        return BankCardCustomization::rulesFor(
            $this->security_cvv_enabled,
            $this->security_expiry_enabled,
        );
    }

    public function messages(): array
    {
        return BankCardCustomization::messages();
    }

    public function canonicalize(): void
    {
        $this->card_number = BankCardCustomization::canonicalizeCardNumber($this->card_number);
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
        }

        if ($this->security_cvv_enabled && trim($this->cvv2) !== '') {
            $customizationJson['cvv2'] = trim($this->cvv2);
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
