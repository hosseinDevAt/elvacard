<?php

namespace App\Support\Dates;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeZone;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

class DateService
{
    private const PERSIAN_DIGITS = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    private const ASCII_DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public const MONTH_NAMES = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    public function __construct(
        private readonly string $businessTimezone,
    ) {}

    public function businessTimezone(): string
    {
        return $this->businessTimezone;
    }

    public function timeZone(): DateTimeZone
    {
        return new DateTimeZone($this->businessTimezone);
    }

    public function toBusiness(CarbonInterface $value): Carbon
    {
        return Carbon::instance($value)->setTimezone($this->businessTimezone);
    }

    public function toCanonical(CarbonInterface $value): Carbon
    {
        return Carbon::instance($value)->setTimezone('UTC');
    }

    public function now(): Carbon
    {
        return Carbon::now($this->businessTimezone);
    }

    public function nowUtc(): Carbon
    {
        return Carbon::now('UTC');
    }

    // ─────────────────────────────────────────────
    // Jalali conversion
    // ─────────────────────────────────────────────

    public function jalali(CarbonInterface $value): Jalalian
    {
        return Jalalian::fromCarbon($this->toBusiness($value));
    }

    public function todayJalali(): Jalalian
    {
        return Jalalian::now($this->timeZone());
    }

    public function currentJalaliYear(): int
    {
        return $this->todayJalali()->getYear();
    }

    // ─────────────────────────────────────────────
    // Formatting
    // ─────────────────────────────────────────────

    public function jDate(CarbonInterface $value): string
    {
        return $this->digits($this->jalali($value)->format('Y/m/d'));
    }

    public function jDateTime(CarbonInterface $value): string
    {
        return $this->digits($this->jalali($value)->format('Y/m/d H:i'));
    }

    public function jTime(CarbonInterface $value): string
    {
        return $this->digits($this->jalali($value)->format('H:i'));
    }

    public function jYear(CarbonInterface $value): string
    {
        return $this->digits((string) $this->jalali($value)->getYear());
    }

    public function jMonth(CarbonInterface $value): string
    {
        return $this->digits($this->jalali($value)->format('m'));
    }

    public function jMonthNumber(CarbonInterface $value): int
    {
        return $this->jalali($value)->getMonth();
    }

    public function jYearMonth(CarbonInterface $value): string
    {
        return $this->digits($this->jalali($value)->format('Y/m'));
    }

    public function match(CarbonInterface $value, string $format = 'date'): string
    {
        return match ($format) {
            'date' => $this->jDate($value),
            'datetime' => $this->jDateTime($value),
            'time' => $this->jTime($value),
            'year' => $this->jYear($value),
            'month' => $this->jMonth($value),
            'yearmonth' => $this->jYearMonth($value),
            'long' => $this->jLong($value),
            default => $this->jDate($value),
        };
    }

    public function jLong(CarbonInterface $value): string
    {
        $j = $this->jalali($value);

        return $this->digits((string) $j->getDay()).' '.self::MONTH_NAMES[$j->getMonth()].' '.$this->digits((string) $j->getYear());
    }

    // ─────────────────────────────────────────────
    // Jalali range for bank card expiry year selects
    // (absolute bounds, not relative to the current year)
    // ─────────────────────────────────────────────

    public function jalaliYearRange(?int $minYear = null, ?int $maxYear = null): array
    {
        $minYear ??= config('dates.card_expiry_min_year', 1400);
        $maxYear ??= config('dates.card_expiry_max_year', 1430);

        return [$minYear, $maxYear];
    }

    // ─────────────────────────────────────────────
    // Parsing (Jalali string → canonical UTC Carbon)
    // ─────────────────────────────────────────────

    public function fromJalali(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $ascii = $this->ascii(trim($value));

        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $ascii, $m) !== 1) {
            return null;
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];
        $hour = isset($m[4]) ? (int) $m[4] : 0;
        $minute = isset($m[5]) ? (int) $m[5] : 0;
        $second = isset($m[6]) ? (int) $m[6] : 0;

        return $this->makeCarbonFromJalali($year, $month, $day, $hour, $minute, $second);
    }

    public function fromJalaliDate(?string $value): ?Carbon
    {
        return $this->fromJalali($value);
    }

    public function fromJalaliDateTime(?string $value): ?Carbon
    {
        return $this->fromJalali($value);
    }

    public function isValidDate(?string $value): bool
    {
        return $this->fromJalali($value) !== null;
    }

    public function isValidDateTime(?string $value): bool
    {
        return $this->fromJalali($value) !== null;
    }

    public function isValidMonthYear(?string $month, ?string $year): bool
    {
        $monthAscii = $this->ascii(trim((string) $month));
        $yearAscii = $this->ascii(trim((string) $year));

        if (! preg_match('/^(0[1-9]|1[0-2])$/', $monthAscii)) {
            return false;
        }

        if (! preg_match('/^\d{4}$/', $yearAscii)) {
            return false;
        }

        $y = (int) $yearAscii;
        $m = (int) $monthAscii;
        $d = $this->isJalaliLeapYear($y) && $m === 12 ? 30 : ($m <= 6 ? 31 : 30);

        return CalendarUtils::isValidateJalaliDate($y, $m, $d);
    }

    public function isJalaliLeapYear(int $year): bool
    {
        return CalendarUtils::isLeapJalaliYear($year);
    }

    // ─────────────────────────────────────────────
    // Jalali expiry → Gregorian MM/YY (canonical engraving)
    // ─────────────────────────────────────────────

    public function jalaliExpiryToGregorian(?string $jalaliMonth, ?string $jalaliYear): ?array
    {
        $m = $this->ascii(trim((string) $jalaliMonth));
        $y = $this->ascii(trim((string) $jalaliYear));

        if ($m === '' || $y === '') {
            return null;
        }

        if (preg_match('/^(0[1-9]|1[0-2])$/', $m) !== 1) {
            return null;
        }

        if (preg_match('/^\d{4}$/', $y) !== 1) {
            return null;
        }

        $jy = (int) $y;
        $jm = (int) $m;

        $minYear = config('dates.card_expiry_min_year', 1400);
        $maxYear = config('dates.card_expiry_max_year', 1430);

        if ($jy < $minYear || $jy > $maxYear) {
            return null;
        }

        // Convert last day of the Jalali month to Gregorian
        $jd = $jm <= 6 ? 31 : ($jm < 12 ? 30 : ($this->isJalaliLeapYear($jy) ? 30 : 29));
        $gDate = CalendarUtils::toGregorian($jy, $jm, $jd);

        return [
            'expiry_month' => sprintf('%02d', $gDate[1]),
            'expiry_year' => substr((string) $gDate[0], -2),
        ];
    }

    /**
     * Whether the given Jalali month/year card expiry has already passed
     * relative to the current Jalali month. Returns false for malformed input
     * (other validation rules cover format errors). This does NOT modify the
     * behaviour of jalaliExpiryToGregorian() – call that separately.
     */
    public function isPastJalaliExpiry(?string $jalaliMonth, ?string $jalaliYear): bool
    {
        $m = $this->ascii(trim((string) $jalaliMonth));
        $y = $this->ascii(trim((string) $jalaliYear));

        if (preg_match('/^(0[1-9]|1[0-2])$/', $m) !== 1 || preg_match('/^\d{4}$/', $y) !== 1) {
            return false; // malformed — let format rules handle it
        }

        $jy = (int) $y;
        $jm = (int) $m;

        $today = $this->todayJalali();
        $currentYear = $today->getYear();
        $currentMonth = $today->getMonth();

        return $jy < $currentYear || ($jy === $currentYear && $jm < $currentMonth);
    }

    // ─────────────────────────────────────────────
    // UTC boundaries for business days
    // ─────────────────────────────────────────────

    public function dayStartCanonical(CarbonInterface $value): Carbon
    {
        return $this->toCanonical($this->toBusiness($value)->copy()->startOfDay());
    }

    public function dayEndCanonical(CarbonInterface $value): Carbon
    {
        return $this->toCanonical($this->toBusiness($value)->copy()->endOfDay());
    }

    public function boundaryCanonical(?string $jalaliDate, bool $isEnd = false): ?Carbon
    {
        $carbon = $this->fromJalali($jalaliDate);

        if ($carbon === null) {
            return null;
        }

        return $isEnd ? $this->dayEndCanonical($carbon) : $this->dayStartCanonical($carbon);
    }

    // ─────────────────────────────────────────────
    // Relative time in Persian
    // ─────────────────────────────────────────────

    public function relativeForHumans(CarbonInterface $value): string
    {
        $now = $this->now();
        $valueBusiness = $this->toBusiness($value);

        if ($now->lessThanOrEqualTo($valueBusiness)) {
            return $this->jDateTime($value);
        }

        $diffSeconds = (int) abs($now->diffInSeconds($valueBusiness));
        $minutes = (int) floor($diffSeconds / 60);

        if ($minutes < 1) {
            return 'چند لحظه پیش';
        }

        if ($minutes < 60) {
            return $this->digits((string) $minutes).' دقیقه پیش';
        }

        $hours = (int) floor($diffSeconds / 3600);
        $businessDayDiff = (int) abs($now->copy()->startOfDay()->diffInDays($valueBusiness->copy()->startOfDay()));

        if ($businessDayDiff === 0 && $hours < 24) {
            return $this->digits((string) $hours).' ساعت پیش';
        }

        if ($businessDayDiff === 1) {
            return 'دیروز';
        }

        if ($businessDayDiff < 7) {
            return $this->digits((string) $businessDayDiff).' روز پیش';
        }

        return $this->jDate($value);
    }

    // ─────────────────────────────────────────────
    // Digit conversion
    // ─────────────────────────────────────────────

    public function digits(string|int|float $value): string
    {
        return strtr((string) $value, self::PERSIAN_DIGITS);
    }

    public function ascii(string $value): string
    {
        return strtr($value, self::ASCII_DIGITS);
    }

    // ─────────────────────────────────────────────
    // Internal
    // ─────────────────────────────────────────────

    private function makeCarbonFromJalali(int $y, int $mo, int $d, int $h = 0, int $mi = 0, int $s = 0): ?Carbon
    {
        if (! CalendarUtils::isValidateJalaliDate($y, $mo, $d)) {
            return null;
        }

        try {
            $jalali = new Jalalian($y, $mo, $d, $h, $mi, $s, $this->timeZone());

            return $this->toCanonical($jalali->toCarbon());
        } catch (\Throwable) {
            return null;
        }
    }
}
