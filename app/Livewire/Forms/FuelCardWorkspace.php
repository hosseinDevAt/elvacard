<?php

namespace App\Livewire\Forms;

use App\Services\FuelCard\FuelCardCustomization;
use Livewire\Form;

class FuelCardWorkspace extends Form
{
    public string $owner_name = '';

    public string $car_info = '';

    public string $vin = '';

    public string $system_name = '';

    public string $system_identifier = '';

    public string $plate_number = '';

    public string $chip_info = '';

    public function rules(): array
    {
        return FuelCardCustomization::rulesFor();
    }

    public function messages(): array
    {
        return FuelCardCustomization::messages();
    }

    public function canonicalize(): void
    {
        $this->owner_name = FuelCardCustomization::canonicalizeText($this->owner_name, 100);
        $this->car_info = FuelCardCustomization::canonicalizeText($this->car_info, 255);
        $this->vin = FuelCardCustomization::canonicalizeVin($this->vin);
        $this->system_name = FuelCardCustomization::canonicalizeText($this->system_name, 100);
        $this->system_identifier = FuelCardCustomization::canonicalizeText($this->system_identifier, 64);
        $this->plate_number = FuelCardCustomization::canonicalizePlate($this->plate_number);
        $this->chip_info = FuelCardCustomization::canonicalizeText($this->chip_info, 100);
    }

    public function customizationJson(): array
    {
        $customization = [];

        foreach ([
            'owner_name',
            'car_info',
            'vin',
            'system_name',
            'system_identifier',
            'plate_number',
            'chip_info',
        ] as $field) {
            if (trim($this->{$field}) !== '') {
                $customization[$field] = trim($this->{$field});
            }
        }

        return $customization;
    }
}
