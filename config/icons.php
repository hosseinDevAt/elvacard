<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Icon Variant Registry
    |--------------------------------------------------------------------------
    |
    | Canonical set of icon variants. Each variant maps to a blade component
    | suffix under resources/views/components/icons/{slot}.
    |
    | Resolution is exclusively server-side through this allowlist - the value
    | stored in site settings can never be used to resolve an arbitrary class
    | or component. An unknown variant always falls back to the slot default.
    |
    */

    'variants' => [
        'outline' => ['label' => 'خطی (پیش‌فرض)'],
        'solid' => ['label' => 'توپر'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin-Controllable Icon Slots
    |--------------------------------------------------------------------------
    |
    | Icons an admin can customize in the Appearance Manager. Each slot:
    |
    |   label        - Persian name shown to the admin
    |   group        - UI grouping (header / contact)
    |   default      - variant used when no override is stored
    |   variants     - allowed variant keys (must exist in the variants map)
    |   can_disable  - whether the icon can be hidden entirely
    |
    | Slots are rendered by <x-icons.{slot}> components. To add a new variant
    | for a slot, add the variant here AND ship the matching blade component.
    |
    */

    'slots' => [
        'search' => [
            'label' => 'جستجو',
            'group' => 'header',
            'default' => 'outline',
            'variants' => ['outline', 'solid'],
            'can_disable' => false,
        ],

        'cart' => [
            'label' => 'سبد خرید',
            'group' => 'header',
            'default' => 'outline',
            'variants' => ['outline', 'solid'],
            'can_disable' => false,
        ],

        'phone' => [
            'label' => 'تلفن',
            'group' => 'contact',
            'default' => 'outline',
            'variants' => ['outline', 'solid'],
            'can_disable' => true,
        ],

        'mail' => [
            'label' => 'ایمیل',
            'group' => 'contact',
            'default' => 'outline',
            'variants' => ['outline', 'solid'],
            'can_disable' => true,
        ],

        'map_pin' => [
            'label' => 'نشانگر آدرس',
            'group' => 'contact',
            'default' => 'outline',
            'variants' => ['outline', 'solid'],
            'can_disable' => true,
        ],
    ],
];