<?php

namespace App\Services\Customization;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;

class CustomizationWorkflowRegistry
{
    // Compile-time allowlist of workflows whose server-side workspace is fully
    // deployed and purchaseable. This list is the single source of truth for
    // availability and is never derived from database or request input, so a
    // product row can never point to a class/component name of its choosing.
    public const ACTIVE_WORKFLOWS = [
        CustomizationWorkflowEnum::BANK_CARD,
        CustomizationWorkflowEnum::FUEL_CARD,
    ];

    public static function isActive(?CustomizationWorkflowEnum $workflow): bool
    {
        if ($workflow === null) {
            return false;
        }

        return in_array($workflow, self::ACTIVE_WORKFLOWS, true);
    }

    /**
     * The only legal product type for a given customization workflow. A card
     * products always carries its own workflow and a plain commerce product
     * always carries none. Mirrors the historical backfill in migration
     * 2026_09_13_000002 (bank→bank_card, fuel→fuel_card, standard→null).
     */
    public static function expectedType(?CustomizationWorkflowEnum $workflow): ProductTypeEnum
    {
        return match ($workflow) {
            CustomizationWorkflowEnum::BANK_CARD => ProductTypeEnum::BANK,
            CustomizationWorkflowEnum::FUEL_CARD => ProductTypeEnum::FUEL,
            null => ProductTypeEnum::STANDARD,
        };
    }

    public static function typeIsConsistent(string $type, ?string $workflowRaw): bool
    {
        $typeEnum = ProductTypeEnum::tryFrom($type);

        if ($typeEnum === null) {
            return false;
        }

        $workflow = $workflowRaw !== null ? CustomizationWorkflowEnum::tryFrom($workflowRaw) : null;

        if ($workflowRaw !== null && $workflow === null) {
            return false;
        }

        return self::expectedType($workflow) === $typeEnum;
    }

    public static function classifyLegacyCustomization(array $customization): ?CustomizationWorkflowEnum
    {
        $bankEvidenceKeys = [
            'card_number',
            'card_holder_name',
            'back_text',
            'cvv2',
            'expiry_month',
            'expiry_year',
            'security_cvv_enabled',
            'security_expiry_enabled',
            'positions',
            'qr_code_enabled',
            'qr_code_path',
        ];

        foreach ($bankEvidenceKeys as $key) {
            if (array_key_exists($key, $customization)) {
                return CustomizationWorkflowEnum::BANK_CARD;
            }
        }

        return null;
    }
}
