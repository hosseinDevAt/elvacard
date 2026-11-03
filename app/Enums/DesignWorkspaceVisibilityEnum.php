<?php

namespace App\Enums;

enum DesignWorkspaceVisibilityEnum: string
{
    case BANK = 'bank';
    case FUEL = 'fuel';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::BANK => 'Bank Card Only',
            self::FUEL => 'Fuel Card Only',
            self::BOTH => 'Both Workspaces',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::BANK => 'فقط میزکار کارت بانکی',
            self::FUEL => 'فقط میزکار کارت سوخت',
            self::BOTH => 'هر دو میزکار (کارت بانکی و سوخت)',
        };
    }

    public function matchesWorkflow(?CustomizationWorkflowEnum $workflow): bool
    {
        if ($this === self::BOTH) {
            return true;
        }

        if ($workflow === CustomizationWorkflowEnum::BANK_CARD && $this === self::BANK) {
            return true;
        }

        if ($workflow === CustomizationWorkflowEnum::FUEL_CARD && $this === self::FUEL) {
            return true;
        }

        return false;
    }

    public static function values(): array
    {
        return [
            self::BANK->value,
            self::FUEL->value,
            self::BOTH->value,
        ];
    }
}
