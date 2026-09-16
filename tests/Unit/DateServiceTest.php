<?php

namespace Tests\Unit;

use App\Support\Dates\DateService;
use Carbon\Carbon;
use Tests\TestCase;

class DateServiceTest extends TestCase
{
    private DateService $dates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dates = app(DateService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_digits_converts_ascii_to_persian(): void
    {
        $this->assertSame('۱۲۳۴۵۶۷۸۹۰', $this->dates->digits('1234567890'));
        $this->assertSame('۱۴۰۵/۰۶/۲۵', $this->dates->digits('1405/06/25'));
    }

    public function test_ascii_converts_persian_digits_to_ascii(): void
    {
        $this->assertSame('1234567890', $this->dates->ascii('۱۲۳۴۵۶۷۸۹۰'));
        $this->assertSame('1405/06/25', $this->dates->ascii('۱۴۰۵/۰۶/۲۵'));
    }

    public function test_ascii_converts_arabic_digits_to_ascii(): void
    {
        $this->assertSame('0123', $this->dates->ascii('٠١٢٣'));
    }

    public function test_now_returns_business_timezone(): void
    {
        $now = $this->dates->now();
        $this->assertSame('Asia/Tehran', $now->getTimezone()->getName());
    }

    public function test_now_utc_returns_utc(): void
    {
        $utc = $this->dates->nowUtc();
        $this->assertSame('UTC', $utc->getTimezone()->getName());
    }

    public function test_to_business_converts_to_tehran(): void
    {
        $utc = Carbon::parse('2025-03-21 12:00:00', 'UTC');
        $business = $this->dates->toBusiness($utc);
        $this->assertSame('Asia/Tehran', $business->getTimezone()->getName());
        $this->assertSame('15:30', $business->format('H:i'));
    }

    public function test_to_canonical_converts_to_utc(): void
    {
        $tehran = Carbon::parse('2025-03-21 15:30:00', 'Asia/Tehran');
        $canonical = $this->dates->toCanonical($tehran);
        $this->assertSame('UTC', $canonical->getTimezone()->getName());
        $this->assertSame('12:00', $canonical->format('H:i'));
    }

    public function test_jdate_returns_persian_formatted_date(): void
    {
        // 2025-03-21 12:00 UTC → Tehran 15:30 → Jalali 1404/01/01 (Nowruz)
        $utc = Carbon::parse('2025-03-21 12:00:00', 'UTC');
        $this->assertSame('۱۴۰۴/۰۱/۰۱', $this->dates->jDate($utc));
    }

    public function test_jdatetime_returns_persian_formatted_datetime(): void
    {
        $utc = Carbon::parse('2025-03-21 12:00:00', 'UTC');
        $this->assertSame('۱۴۰۴/۰۱/۰۱ ۱۵:۳۰', $this->dates->jDateTime($utc));
    }

    public function test_jtime_returns_persian_formatted_time(): void
    {
        $utc = Carbon::parse('2025-03-21 12:00:00', 'UTC');
        $this->assertSame('۱۵:۳۰', $this->dates->jTime($utc));
    }

    public function test_jyear_month_yearmonth_long(): void
    {
        $utc = Carbon::parse('2025-03-21 12:00:00', 'UTC');
        $this->assertSame('۱۴۰۴', $this->dates->jYear($utc));
        $this->assertSame('۰۱', $this->dates->jMonth($utc));
        $this->assertSame(1, $this->dates->jMonthNumber($utc));
        $this->assertSame('۱۴۰۴/۰۱', $this->dates->jYearMonth($utc));
        $this->assertSame('۱ فروردین ۱۴۰۴', $this->dates->jLong($utc));
    }

    public function test_match_dispatches_to_correct_formatter(): void
    {
        $utc = Carbon::parse('2025-03-21 12:00:00', 'UTC');
        $this->assertSame($this->dates->jDate($utc), $this->dates->match($utc, 'date'));
        $this->assertSame($this->dates->jDateTime($utc), $this->dates->match($utc, 'datetime'));
        $this->assertSame($this->dates->jTime($utc), $this->dates->match($utc, 'time'));
        $this->assertSame($this->dates->jYear($utc), $this->dates->match($utc, 'year'));
        $this->assertSame($this->dates->jMonth($utc), $this->dates->match($utc, 'month'));
        $this->assertSame($this->dates->jYearMonth($utc), $this->dates->match($utc, 'yearmonth'));
        $this->assertSame($this->dates->jLong($utc), $this->dates->match($utc, 'long'));
        $this->assertSame($this->dates->jDate($utc), $this->dates->match($utc, 'unknown'));
    }

    public function test_from_jalali_returns_canonical_utc(): void
    {
        $result = $this->dates->fromJalali('1404/01/01');
        $this->assertNotNull($result);
        $this->assertSame('UTC', $result->getTimezone()->getName());
        // Jalali midnight in Tehran == UTC the previous evening
        $this->assertSame('2025-03-20', $result->format('Y-m-d'));
        $this->assertSame('20:30', $result->format('H:i'));
    }

    public function test_from_jalali_handles_persian_digits(): void
    {
        $result = $this->dates->fromJalali('۱۴۰۴/۰۱/۰۱');
        $this->assertNotNull($result);
        $this->assertSame('2025-03-20 20:30:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_from_jalali_parses_datetime(): void
    {
        $result = $this->dates->fromJalali('1404/01/01 10:30');
        $this->assertNotNull($result);
        $this->assertSame('2025-03-21 07:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_from_jalali_rejects_invalid(): void
    {
        $this->assertNull($this->dates->fromJalali(null));
        $this->assertNull($this->dates->fromJalali(''));
        $this->assertNull($this->dates->fromJalali('not-a-date'));
        $this->assertNull($this->dates->fromJalali('1404/13/01'));
        $this->assertNull($this->dates->fromJalali('1404/00/10'));
    }

    public function test_from_jalali_accepts_hyphen_separators(): void
    {
        $result = $this->dates->fromJalali('1404-01-01');
        $this->assertNotNull($result);
        $this->assertSame('2025-03-20', $result->format('Y-m-d'));
    }

    public function test_is_valid_date(): void
    {
        $this->assertTrue($this->dates->isValidDate('1404/01/01'));
        $this->assertTrue($this->dates->isValidDate('۱۴۰۴/۰۱/۰۱'));
        $this->assertFalse($this->dates->isValidDate('invalid'));
        $this->assertFalse($this->dates->isValidDate(null));
    }

    public function test_is_valid_month_year(): void
    {
        $this->assertTrue($this->dates->isValidMonthYear('01', '1404'));
        // 1403 is a leap year → Esfand 30 is a valid expiry
        $this->assertTrue($this->dates->isValidMonthYear('12', '1403'));
        // 1404 is NOT a leap year → Esfand 30 is invalid
        $this->assertFalse($this->dates->isValidMonthYear('12', '1404'));
        $this->assertFalse($this->dates->isValidMonthYear('00', '1404'));
        $this->assertFalse($this->dates->isValidMonthYear('13', '1404'));
        $this->assertFalse($this->dates->isValidMonthYear('01', '14'));
        $this->assertFalse($this->dates->isValidMonthYear('01', 'abcd'));
    }

    public function test_is_valid_month_year_accepts_persian_digits(): void
    {
        $this->assertTrue($this->dates->isValidMonthYear('۰۱', '۱۴۰۴'));
    }

    public function test_is_jalali_leap_year(): void
    {
        $this->assertTrue($this->dates->isJalaliLeapYear(1403));
        $this->assertFalse($this->dates->isJalaliLeapYear(1404));
    }

    public function test_jalali_expires_to_gregorian_normal_month(): void
    {
        // Shahrivar 1405 (31 days) → last day → Gregorian 2026-09-22
        $result = $this->dates->jalaliExpiryToGregorian('06', '1405');
        $this->assertNotNull($result);
        $this->assertSame('09', $result['expiry_month']);
        $this->assertSame('26', $result['expiry_year']);
    }

    public function test_jalali_expires_to_gregorian_esfand_non_leap(): void
    {
        // 1405/12 is not a leap year → Esfand 29 → Gregorian 2027-03-20
        $result = $this->dates->jalaliExpiryToGregorian('12', '1405');
        $this->assertNotNull($result);
        $this->assertSame('03', $result['expiry_month']);
        $this->assertSame('27', $result['expiry_year']);
    }

    public function test_jalali_expires_to_gregorian_esfand_leap(): void
    {
        // 1408/12 is a leap year → Esfand 30 → Gregorian 2030-03-19
        $result = $this->dates->jalaliExpiryToGregorian('12', '1408');
        $this->assertNotNull($result);
        $this->assertSame('03', $result['expiry_month']);
        $this->assertSame('30', $result['expiry_year']);
    }

    public function test_jalali_expires_to_gregorian_rejects_out_of_range(): void
    {
        [$min, $max] = $this->dates->jalaliYearRange();
        $this->assertNull($this->dates->jalaliExpiryToGregorian('01', (string) ($min - 1)));
        $this->assertNull($this->dates->jalaliExpiryToGregorian('01', (string) ($max + 1)));
    }

    public function test_jalali_expires_to_gregorian_rejects_invalid_month(): void
    {
        $this->assertNull($this->dates->jalaliExpiryToGregorian('00', '1404'));
        $this->assertNull($this->dates->jalaliExpiryToGregorian('13', '1404'));
    }

    public function test_jalali_year_range_starts_at_1400_and_not_capped_at_1415(): void
    {
        [$from, $to] = $this->dates->jalaliYearRange();

        $this->assertSame(1400, $from);
        $this->assertSame(config('dates.card_expiry_max_year', 1430), $to);
        $this->assertGreaterThan(1415, $to);
    }

    public function test_jalali_year_range_custom_bounds(): void
    {
        [$from, $to] = $this->dates->jalaliYearRange(1400, 1412);

        $this->assertSame(1400, $from);
        $this->assertSame(1412, $to);
    }

    public function test_day_start_canonical_returns_utc_midnight(): void
    {
        // A known Jalali day: 1404/06/31 = 2026-03-20
        $utc = Carbon::parse('2026-03-20 05:30:00', 'UTC');
        $dayStart = $this->dates->dayStartCanonical($utc);
        $this->assertSame('UTC', $dayStart->getTimezone()->getName());
        // Start of business day 2026-03-20 in Tehran → UTC 2026-03-19 20:30:00
        $this->assertSame('20:30', $dayStart->format('H:i'));
    }

    public function test_day_end_canonical_returns_utc_end_of_day(): void
    {
        $utc = Carbon::parse('2026-03-20 05:30:00', 'UTC');
        $dayEnd = $this->dates->dayEndCanonical($utc);
        $this->assertSame('20:29:59', $dayEnd->format('H:i:s'));
    }

    public function test_boundary_canonical_from_jalali(): void
    {
        $start = $this->dates->boundaryCanonical('1404/06/31');
        $this->assertNotNull($start);
        $this->assertSame('20:30', $start->format('H:i'));

        $end = $this->dates->boundaryCanonical('1404/06/31', true);
        $this->assertNotNull($end);
        $this->assertSame('20:29:59', $end->format('H:i:s'));
    }

    public function test_boundary_canonical_rejects_invalid(): void
    {
        $this->assertNull($this->dates->boundaryCanonical('invalid'));
    }

    public function test_relative_for_humans_future_returns_jalali_datetime(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $future = Carbon::parse('2099-01-01 12:00:00', 'UTC');
        $this->assertSame($this->dates->jDateTime($future), $this->dates->relativeForHumans($future));
    }

    public function test_relative_for_humans_just_now(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $past = $this->dates->now()->subSeconds(20);
        $this->assertSame('چند لحظه پیش', $this->dates->relativeForHumans($past));
    }

    public function test_relative_for_humans_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $past = $this->dates->now()->subMinutes(15);
        $this->assertSame('۱۵ دقیقه پیش', $this->dates->relativeForHumans($past));
    }

    public function test_relative_for_humans_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $past = $this->dates->now()->subHours(3);
        $this->assertSame('۳ ساعت پیش', $this->dates->relativeForHumans($past));
    }

    public function test_relative_for_humans_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $past = $this->dates->now()->subDay();
        $this->assertSame('دیروز', $this->dates->relativeForHumans($past));
    }

    public function test_relative_for_humans_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $past = $this->dates->now()->subDays(5);
        $this->assertSame('۵ روز پیش', $this->dates->relativeForHumans($past));
    }

    public function test_relative_for_humans_old_returns_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00', 'Asia/Tehran'));
        $past = $this->dates->now()->subDays(30);
        $this->assertSame($this->dates->jDate($past), $this->dates->relativeForHumans($past));
    }
}
