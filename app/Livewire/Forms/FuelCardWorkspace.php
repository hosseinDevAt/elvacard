<?php

namespace App\Livewire\Forms;

use App\Services\FuelCard\FuelCardCustomization;
use Livewire\Form;

class FuelCardWorkspace extends Form
{
    public string $owner_name = '';

    public string $phone_number = '';

    public string $vehicle_type = '';

    public string $vehicle_model = '';

    public string $oil_serial_number = '';

    public string $plate_number = '';

    public string $plate_part1 = '';

    public string $plate_letter = '';

    public string $plate_part2 = '';

    public string $plate_iran = '';

    public string $vin = '';

    public ?string $chip_info = null;

    // Historical fields for backward compatibility
    public string $car_info = '';

    public string $system_name = '';

    public string $system_identifier = '';

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
        $this->phone_number = FuelCardCustomization::canonicalizePhone($this->phone_number);
        $this->vehicle_type = trim($this->vehicle_type);
        $this->vehicle_model = FuelCardCustomization::canonicalizeText($this->vehicle_model, 100);
        $this->oil_serial_number = FuelCardCustomization::canonicalizeText($this->oil_serial_number, 50);

        // Normalize plate parts
        $p1 = FuelCardCustomization::canonicalizeDigits($this->plate_part1);
        $letter = trim($this->plate_letter);
        $p2 = FuelCardCustomization::canonicalizeDigits($this->plate_part2);
        $iran = FuelCardCustomization::canonicalizeDigits($this->plate_iran);

        if ($p1 !== '' || $letter !== '' || $p2 !== '' || $iran !== '') {
            $this->plate_part1 = $p1;
            $this->plate_letter = $letter;
            $this->plate_part2 = $p2;
            $this->plate_iran = $iran;
            $this->plate_number = trim("{$p1} {$letter} {$p2} ایران {$iran}");
        } elseif ($this->plate_number !== '') {
            $this->plate_number = FuelCardCustomization::canonicalizePlate($this->plate_number);
            $parsed = FuelCardCustomization::formatIranPlateDisplay($this->plate_number);
            if ($parsed['is_structured']) {
                $this->plate_part1 = $parsed['part1'] ?? '';
                $this->plate_letter = $parsed['letter'] ?? '';
                $this->plate_part2 = $parsed['part2'] ?? '';
                $this->plate_iran = $parsed['iran'] ?? '';
            }
        }

        $this->vin = FuelCardCustomization::canonicalizeVin($this->vin);
        $this->chip_info = FuelCardCustomization::canonicalizeChipInfo($this->chip_info);

        // Historical
        $this->car_info = FuelCardCustomization::canonicalizeText($this->car_info, 255);
        $this->system_name = FuelCardCustomization::canonicalizeText($this->system_name, 100);
        $this->system_identifier = FuelCardCustomization::canonicalizeText($this->system_identifier, 64);
    }

    public function customizationJson(): array
    {
        $customization = [];

        foreach ([
            'owner_name',
            'phone_number',
            'vehicle_type',
            'vehicle_model',
            'oil_serial_number',
            'vin',
            'plate_number',
        ] as $field) {
            if (trim($this->{$field}) !== '') {
                $customization[$field] = trim($this->{$field});
            }
        }

        if ($this->plate_part1 !== '' || $this->plate_letter !== '' || $this->plate_part2 !== '' || $this->plate_iran !== '') {
            $customization['plate_parts'] = [
                'part1' => $this->plate_part1,
                'letter' => $this->plate_letter,
                'part2' => $this->plate_part2,
                'iran' => $this->plate_iran,
            ];
        }

        if (in_array($this->chip_info, FuelCardCustomization::VALID_CHIP_SIZES, true)) {
            $customization['chip_info'] = $this->chip_info;
        }

        // Backward compatibility
        if (trim($this->car_info) !== '') {
            $customization['car_info'] = trim($this->car_info);
        }
        if (trim($this->system_name) !== '') {
            $customization['system_name'] = trim($this->system_name);
        }
        if (trim($this->system_identifier) !== '') {
            $customization['system_identifier'] = trim($this->system_identifier);
        }

        return $customization;
    }
}
