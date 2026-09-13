<?php

namespace App\Livewire\Forms;

use App\Services\FuelCard\FuelCardCustomization;
use Livewire\Form;

class FuelCardWorkspace extends Form
{
    public function rules(): array
    {
        return FuelCardCustomization::rulesFor();
    }

    public function messages(): array
    {
        return FuelCardCustomization::messages();
    }

    public function customizationJson(): array
    {
        return [];
    }
}
