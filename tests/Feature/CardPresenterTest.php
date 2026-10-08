<?php

namespace Tests\Feature;

use App\Services\Customization\CardPresenter;
use Tests\TestCase;

class CardPresenterTest extends TestCase
{
    public function test_present_groups_canonical_digits_by_four(): void
    {
        $this->assertSame('6274 0512 3456 7890', CardPresenter::presentCardNumber('6274051234567890'));
        $this->assertSame('6274 0512 34', CardPresenter::presentCardNumber('6274051234'));
        $this->assertSame('1234 6578 9789 7897', CardPresenter::presentCardNumber('1234657897897897'));
    }

    public function test_present_strips_separators_before_grouping(): void
    {
        $this->assertSame('6274 0512 3456 7890', CardPresenter::presentCardNumber('6274-0512-3456-7890'));
        $this->assertSame('6274 0512 3456 7890', CardPresenter::presentCardNumber(' 6274 0512 3456 7890 '));
    }

    public function test_present_normalizes_persian_and_arabic_digits(): void
    {
        $this->assertSame('6274 0512 3456 7890', CardPresenter::presentCardNumber('۶۲۷۴۰۵۱۲۳۴۵۶۷۸۹۰'));
        $this->assertSame('6274 0512 3456 7890', CardPresenter::presentCardNumber('٦٢٧٤٠٥١٢٣٤٥٦٧٨٩٠'));
    }

    public function test_present_ignores_non_numeric_characters(): void
    {
        $this->assertSame('6274 0512 34', CardPresenter::presentCardNumber('ABC-6274_0512/34'));
    }

    public function test_present_returns_empty_for_null_and_blank(): void
    {
        $this->assertSame('', CardPresenter::presentCardNumber(null));
        $this->assertSame('', CardPresenter::presentCardNumber(''));
    }

    public function test_mask_pan_formats_and_hides_sensitive_digits(): void
    {
        $this->assertSame('•••• •••• •••• 7898', CardPresenter::maskPan('6274051234567898'));
        $this->assertSame('•••• •••• •••• 7898', CardPresenter::maskPan('6274 0512 3456 7898'));
        $this->assertSame('•••• •••• •••• 7898', CardPresenter::maskPan('۶۲۷۴۰۵۱۲۳۴۵۶۷۸۹۸'));
        $this->assertSame('•••• •••• •••• 7898', CardPresenter::maskPan('7898'));
        $this->assertSame('•••• •••• •••• 7898', CardPresenter::maskPan('•••• •••• •••• 7898'));
        $this->assertSame('', CardPresenter::maskPan(null));
        $this->assertSame('', CardPresenter::maskPan(''));
    }

    public function test_present_iban_formats_into_four_char_groups(): void
    {
        $this->assertSame('IR12 0120 0000 0000 0012 3456 78', CardPresenter::presentIban('IR120120000000000012345678'));
        $this->assertSame('IR12 0120 0000 0000 0012 3456 78', CardPresenter::presentIban('ir12 0120-0000 0000 0012 3456 78'));
        $this->assertSame('', CardPresenter::presentIban(''));
        $this->assertSame('', CardPresenter::presentIban(null));
    }

    public function test_mask_iban_hides_middle_digits(): void
    {
        $this->assertSame('IR12 •••• •••• •••• •••• 5678', CardPresenter::maskIban('IR120120000000000012345678'));
        $this->assertSame('', CardPresenter::maskIban(''));
        $this->assertSame('', CardPresenter::maskIban(null));
    }

    public function test_fixed_slots_have_all_fields_with_normalized_coordinates(): void
    {
        $slots = CardPresenter::fixedSlots();

        $this->assertSame(
            ['iban', 'card_number', 'cvv2', 'expiry', 'back_text', 'card_holder_name', 'front_custom_text'],
            array_keys($slots)
        );

        foreach ($slots as $slot) {
            $this->assertGreaterThanOrEqual(0, $slot['x']);
            $this->assertLessThanOrEqual(1, $slot['x']);
            $this->assertGreaterThanOrEqual(0, $slot['y']);
            $this->assertLessThanOrEqual(1, $slot['y']);
        }
    }

    public function test_fixed_slots_values_match_approved_phase_03_layout(): void
    {
        $slots = CardPresenter::fixedSlots();

        // Row 1: IBAN (top near stripe)
        $this->assertSame(['x' => 0.08, 'y' => 0.26], $slots['iban']);
        // Row 2: Card number (center)
        $this->assertSame(['x' => 0.08, 'y' => 0.45], $slots['card_number']);
        // Row 3: Security row (CVV2 on left, Expiry on right)
        $this->assertSame(['x' => 0.08, 'y' => 0.65], $slots['cvv2']);
        $this->assertSame(['x' => 0.70, 'y' => 0.65], $slots['expiry']);
        // Row 4: Optional back text
        $this->assertSame(['x' => 0.08, 'y' => 0.74], $slots['back_text']);
        // Row 5: Card holder name (bottom)
        $this->assertSame(['x' => 0.08, 'y' => 0.83], $slots['card_holder_name']);
    }

    public function test_contrast_calculation_selects_dark_text_for_light_backgrounds(): void
    {
        // Pure white background -> dark text
        $this->assertSame('dark', CardPresenter::textColorMode('#FFFFFF'));
        $this->assertTrue(CardPresenter::isLightBackground('#FFFFFF'));
        $this->assertSame('text-slate-950', CardPresenter::backTextColorClass('#FFFFFF'));

        // Gold background -> dark text
        $this->assertSame('dark', CardPresenter::textColorMode('#FFD700'));
        $this->assertTrue(CardPresenter::isLightBackground('#FFD700'));

        // Silver background -> dark text
        $this->assertSame('dark', CardPresenter::textColorMode('#C0C0C0'));
        $this->assertTrue(CardPresenter::isLightBackground('#C0C0C0'));
    }

    public function test_contrast_calculation_selects_light_text_for_dark_backgrounds(): void
    {
        // Pure black background -> light text
        $this->assertSame('light', CardPresenter::textColorMode('#000000'));
        $this->assertFalse(CardPresenter::isLightBackground('#000000'));
        $this->assertSame('text-white', CardPresenter::backTextColorClass('#000000'));

        // Navy blue background -> light text
        $this->assertSame('light', CardPresenter::textColorMode('#000080'));

        // Dark green background -> light text
        $this->assertSame('light', CardPresenter::textColorMode('#006400'));
    }

    public function test_contrast_calculation_handles_arbitrary_admin_hex_codes(): void
    {
        // Arbitrary pastel yellow
        $this->assertSame('dark', CardPresenter::textColorMode('#FEF08A'));

        // Arbitrary deep midnight purple
        $this->assertSame('light', CardPresenter::textColorMode('#1E1B4B'));
    }
}
