<?php

namespace App\Services\Customization;

use App\Enums\CustomizationWorkflowEnum;

class CustomizationWorkflowRegistry
{
    // Compile-time allowlist of workflows whose server-side workspace is fully
    // deployed and purchaseable. This list is the single source of truth for
    // availability and is never derived from database or request input, so a
    // product row can never point to a class/component name of its choosing.
    public const ACTIVE_WORKFLOWS = [
        CustomizationWorkflowEnum::BANK_CARD,
    ];

    public static function isActive(?CustomizationWorkflowEnum $workflow): bool
    {
        if ($workflow === null) {
            return false;
        }

        return in_array($workflow, self::ACTIVE_WORKFLOWS, true);
    }
}
