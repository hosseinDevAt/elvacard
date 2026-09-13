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

    public function test_fixed_slots_have_all_fields_with_normalized_coordinates(): void
    {
        $slots = CardPresenter::fixedSlots();

        $this->assertSame(
            ['card_number', 'card_holder_name', 'back_text', 'cvv2', 'expiry'],
            array_keys($slots)
        );

        foreach ($slots as $slot) {
            $this->assertGreaterThanOrEqual(0, $slot['x']);
            $this->assertLessThanOrEqual(1, $slot['x']);
            $this->assertGreaterThanOrEqual(0, $slot['y']);
            $this->assertLessThanOrEqual(1, $slot['y']);
        }
    }

    public function test_fixed_slots_values_unchanged_from_original_layout(): void
    {
        $slots = CardPresenter::fixedSlots();

        $this->assertSame(['x' => 0.08, 'y' => 0.42], $slots['card_number']);
        $this->assertSame(['x' => 0.08, 'y' => 0.78], $slots['card_holder_name']);
        $this->assertSame(['x' => 0.08, 'y' => 0.62], $slots['back_text']);
        $this->assertSame(['x' => 0.72, 'y' => 0.78], $slots['cvv2']);
        $this->assertSame(['x' => 0.48, 'y' => 0.78], $slots['expiry']);
    }
}
