<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Dates\DateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Livewire\Component;

class Dashboard extends Component
{
    use AuthorizesAdminActions;

    /**
     * Jalali week day labels, ordered Saturday -> Friday, matching the week
     * window computed in mount().
     */
    private const WEEK_DAY_LABELS = [
        'شنبه',
        '۱شنبه',
        '۲شنبه',
        '۳شنبه',
        '۴شنبه',
        '۵شنبه',
        'جمعه',
    ];

    public int $totalUsers = 0;

    public int $totalOrders = 0;

    public int $pendingOrders = 0;

    public int $totalProducts = 0;

    public int $pendingReviewPayments = 0;

    public int $successfulPayments = 0;

    public int $totalRevenue = 0;

    /**
     * Net revenue per day of the current Jalali week, Saturday first.
     *
     * Each entry: ['label' => string, 'value' => int, 'height' => int]
     * where height is a 4-100 percentage of the busiest day.
     *
     * @var list<array{label: string, value: int, height: int}>
     */
    public array $weeklyRevenue = [];

    public $recentOrders = [];

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->totalOrders = Order::count();
        $this->pendingOrders = Order::where('status', 'pending')->count();
        $this->totalProducts = Product::count();

        $this->pendingReviewPayments = Payment::where('status', PaymentStatus::PENDING_REVIEW->value)->count();
        $this->successfulPayments = Payment::where('status', PaymentStatus::SUCCESS->value)->count();
        $this->totalRevenue = (int) Payment::where('status', PaymentStatus::SUCCESS->value)->sum('paid_amount')
            - (int) Refund::where('status', RefundStatus::COMPLETED->value)->sum('amount');

        $this->weeklyRevenue = $this->buildWeeklyRevenue();

        $this->recentOrders = Order::with('user')->latest()->take(5)->get();
    }

    /**
     * Real daily net revenue for the Jalali week that contains "now".
     *
     * Uses the same basis as the totalRevenue KPI - successful payments minus
     * completed refunds - so the bars and the headline figure are reconcilable.
     * The Jalali week starts on Saturday; Carbon's startOfWeek() is locale
     * dependent, so the Saturday boundary is derived from dayOfWeek directly.
     * Day boundaries come from DateService so the business-timezone/UTC
     * conversion stays in one place.
     *
     * @return list<array{label: string, value: int, height: int}>
     */
    private function buildWeeklyRevenue(): array
    {
        $dates = app(DateService::class);

        $today = $dates->toBusiness(Carbon::now());

        // Saturday is dayOfWeek 6, so (6 + 1) % 7 === 0 and the offset is zero.
        $daysSinceSaturday = ($today->dayOfWeek + 1) % 7;
        $weekStart = $today->copy()->startOfDay()->subDays($daysSinceSaturday);

        $values = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $weekStart->copy()->addDays($offset);

            $values[] = $this->netRevenueBetween(
                $dates->dayStartCanonical($day),
                $dates->dayEndCanonical($day),
            );
        }

        $busiest = max(1, max(array_map(abs(...), $values)));

        return array_map(
            fn (int $value, int $offset): array => [
                'label' => self::WEEK_DAY_LABELS[$offset],
                'value' => $value,
                // A 4% floor keeps a zero-revenue day visible without inventing
                // a value for it.
                'height' => max(4, (int) round(abs($value) / $busiest * 100)),
            ],
            $values,
            array_keys($values),
        );
    }

    /**
     * Successful payments minus completed refunds within a UTC instant range.
     */
    private function netRevenueBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        $paid = (int) Payment::query()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('paid_amount');

        $refunded = (int) Refund::query()
            ->where('status', RefundStatus::COMPLETED->value)
            ->whereBetween('refunded_at', [$start, $end])
            ->sum('amount');

        return $paid - $refunded;
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->layout('layouts.admin')->title('داشبورد');
    }
}
