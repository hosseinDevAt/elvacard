<?php

namespace App\Services;

/**
 * Resolves the configured variant for admin-controllable icon slots.
 *
 * Variant resolution is exclusive to the server-side allowlist in
 * config/icons.php. Values coming from site settings are validated against
 * that allowlist before they can influence rendering - they are never treated
 * as a class or component name.
 */
final class IconManager
{
    /**
     * @param  array<string, array<string, mixed>>  $slots
     * @param  array<string, array<string, mixed>>  $variants
     */
    public function __construct(
        private readonly array $slots,
        private readonly array $variants,
    ) {}

    public function has(string $key): bool
    {
        return isset($this->slots[$key]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function slot(string $key): ?array
    {
        return $this->slots[$key] ?? null;
    }

    public function label(string $key): ?string
    {
        return $this->slots[$key]['label'] ?? null;
    }

    public function defaultVariant(string $key): string
    {
        return (string) ($this->slots[$key]['default'] ?? 'outline');
    }

    /**
     * @return list<string>
     */
    public function allowedVariants(string $key): array
    {
        if (! isset($this->slots[$key]['variants']) || ! is_array($this->slots[$key]['variants'])) {
            return [];
        }

        return array_values(array_filter(
            $this->slots[$key]['variants'],
            fn (string $variant) => isset($this->variants[$variant]),
        ));
    }

    public function canDisable(string $key): bool
    {
        return (bool) ($this->slots[$key]['can_disable'] ?? false);
    }

    /**
     * Resolve the effective variant for a slot.
     *
     * Any stored value that is not in the slot's allowlist is ignored and the
     * slot default is returned. Raw user input therefore cannot influence
     * which blade component is rendered.
     */
    public function variant(string $key): string
    {
        $default = $this->defaultVariant($key);

        if (! $this->has($key)) {
            return $default;
        }

        $stored = (string) site_setting('icon.'.$key);

        return in_array($stored, $this->allowedVariants($key), true)
            ? $stored
            : $default;
    }

    /**
     * Whether the slot icon should be rendered.
     */
    public function enabled(string $key): bool
    {
        if (! $this->canDisable($key)) {
            return true;
        }

        $stored = site_setting('icon.'.$key.'_enabled');

        return $stored === null || filter_var($stored, FILTER_VALIDATE_BOOL);
    }
}