<?php

namespace App\Livewire\Admin;

use App\Models\ManualPaymentSetting;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ManualPaymentSettingManager extends Component
{
    use AuthorizesAdminActions;

    public ?int $settingId = null;

    public ?string $card_number = null;

    public ?string $iban = null;

    public ?string $account_name = null;

    public ?string $instruction_message = null;

    public ?string $success_message = null;

    public bool $is_active = false;

    public bool $loaded = false;

    public function mount(): void
    {
        $setting = ManualPaymentSetting::query()->whereKey(1)->first();

        if (! $setting) {
            return;
        }

        $this->settingId = $setting->id;
        $this->card_number = $setting->card_number;
        $this->iban = $setting->iban;
        $this->account_name = $setting->account_name;
        $this->instruction_message = $setting->instruction_message;
        $this->success_message = $setting->success_message;
        $this->is_active = $setting->is_active;
        $this->loaded = true;
    }

    protected function rules(): array
    {
        return [
            'card_number' => [
                'nullable',
                'string',
                'max:16',
                'regex:/^\d{16}$/',
            ],
            'iban' => [
                'nullable',
                'string',
                'max:26',
                'regex:/^IR\d{24}$/i',
            ],
            'account_name' => ['nullable', 'string', 'max:255'],
            'instruction_message' => ['nullable', 'string', 'max:2000'],
            'success_message' => ['nullable', 'string', 'max:2000'],
            'is_active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if (! $this->loaded || ! $this->settingId) {
            session()->flash('error', 'تنظیمات پرداخت یافت نشد. لطفاً سیدر را مجدداً اجرا کنید.');

            return;
        }

        $cleaned = $this->cleanFinancialData();

        if (! empty($this->is_active) && ! $this->hasMinimumFinancialData($cleaned)) {
            session()->flash('error', 'برای فعال‌سری پرداخت حداقل یک شماره کارت یا شبا و نام صاحب حساب الزامی است.');

            return;
        }

        DB::transaction(function () use ($cleaned): void {
            if (! empty($this->is_active)) {
                ManualPaymentSetting::query()
                    ->where('id', '!=', $this->settingId)
                    ->update(['is_active' => false]);
            }

            ManualPaymentSetting::query()->whereKey($this->settingId)->update($cleaned);
        });

        session()->flash('success', 'تنظیمات پرداخت با موفقیت ذخیره شد.');
    }

    private function cleanFinancialData(): array
    {
        $cardNumber = $this->card_number !== null ? trim($this->card_number) : null;
        $iban = $this->iban !== null ? strtoupper(trim($this->iban)) : null;
        $accountName = $this->account_name !== null ? trim($this->account_name) : null;

        return [
            'card_number' => $cardNumber !== '' ? $cardNumber : null,
            'iban' => $iban !== '' ? $iban : null,
            'account_name' => $accountName !== '' ? $accountName : null,
            'instruction_message' => $this->instruction_message !== null && trim($this->instruction_message) !== ''
                ? trim($this->instruction_message)
                : null,
            'success_message' => $this->success_message !== null && trim($this->success_message) !== ''
                ? trim($this->success_message)
                : null,
            'is_active' => (bool) $this->is_active,
        ];
    }

    private function hasMinimumFinancialData(array $data): bool
    {
        $hasCard = ($data['card_number'] ?? null) !== null;
        $hasIban = ($data['iban'] ?? null) !== null;

        if (! $hasCard && ! $hasIban) {
            return false;
        }

        return ($data['account_name'] ?? null) !== null;
    }

    public function render()
    {
        return view('livewire.admin.manual-payment-setting-manager')
            ->layout('layouts.admin')
            ->title('تنظیمات پرداخت');
    }
}
