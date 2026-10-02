<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Support\Dates\DateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    /**
     * Compute aggregate metrics and cash-basis net revenue for the period.
     *
     * Net revenue = successful payments (paid_at within window) minus completed refunds (refunded_at within window).
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::instance($from);
        $to = Carbon::instance($to);

        $revenue = (int) Payment::query()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('paid_amount');

        $refunded = $this->refundedAmount($from, $to);

        $totalRefunds = $this->completedRefundCount($from, $to);

        $totalOrders = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $completedOrders = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', OrderStatusEnum::COMPLETED->value)
            ->count();

        $cancelledOrders = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', OrderStatusEnum::CANCELLED->value)
            ->count();

        $successfulPayments = DB::table('payments')
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereBetween('paid_at', [$from, $to])
            ->count();

        $pendingReviewPayments = DB::table('payments')
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $activeCustomerCount = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->distinct('customer_phone')
            ->count('customer_phone');

        return [
            'revenue' => $revenue - $refunded,
            'refunded' => $refunded,
            'totalRefunds' => $totalRefunds,
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders,
            'cancelledOrders' => $cancelledOrders,
            'successfulPayments' => $successfulPayments,
            'pendingReviewPayments' => $pendingReviewPayments,
            'activeCustomerCount' => $activeCustomerCount,
        ];
    }

    /**
     * Sum of completed refunds refunded inside the given window.
     *
     * Period Accounting Note:
     * Cash outflows from refunds are strictly aggregated by `refunded_at` (the actual
     * settlement timestamp when funds were remitted). Under cash-basis accounting and
     * bank ledger reconciliation, refunds must be reported in the period they occur
     * rather than retroactively mutating historical inflow reports of the original payment.
     */
    private function refundedAmount(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) Refund::query()
            ->where('status', RefundStatus::COMPLETED->value)
            ->whereBetween('refunded_at', [$from, $to])
            ->sum('amount');
    }

    /**
     * Count of completed refunds refunded inside the given window.
     *
     * Counts completed refund transactions by `refunded_at` within the window.
     */
    private function completedRefundCount(CarbonInterface $from, CarbonInterface $to): int
    {
        return Refund::query()
            ->where('status', RefundStatus::COMPLETED->value)
            ->whereBetween('refunded_at', [$from, $to])
            ->count();
    }

    public function revenueTrend(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::instance($from);
        $to = Carbon::instance($to);

        $dates = app(DateService::class);
        $businessFrom = $dates->toBusiness($from)->copy()->startOfDay();
        $businessTo = $dates->toBusiness($to)->copy()->startOfDay();
        $daily = $businessFrom->diffInDays($businessTo, false) <= 31;

        $payments = Payment::query()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereBetween('paid_at', [$from, $to])
            ->get(['paid_at', 'paid_amount']);

        $refunds = Refund::query()
            ->where('status', RefundStatus::COMPLETED->value)
            ->whereBetween('refunded_at', [$from, $to])
            ->get(['refunded_at', 'amount']);

        $filled = [];
        $cursor = $businessFrom->copy();

        while ($cursor->lte($businessTo)) {
            $key = $dates->ascii($dates->jDate($cursor));
            if (! $daily) {
                $key = substr($key, 0, 7);
            }

            $filled[$key] = ['period' => $key, 'revenue' => 0, 'count' => 0];
            $cursor->addDay();
        }

        foreach ($payments as $payment) {
            $key = $dates->ascii($dates->jDate($payment->paid_at));
            if (! $daily) {
                $key = substr($key, 0, 7);
            }

            $filled[$key]['revenue'] += (int) ($payment->paid_amount ?? 0);
            $filled[$key]['count'] += 1;
        }

        foreach ($refunds as $refund) {
            $key = $dates->ascii($dates->jDate($refund->refunded_at));
            if (! $daily) {
                $key = substr($key, 0, 7);
            }

            $filled[$key]['revenue'] -= (int) $refund->amount;
        }

        return array_values($filled);
    }

    /**
     * Retrieve top-performing products by volume and realized sales in the window.
     *
     * Accounting Semantics & Net Revenue Alignment:
     * - Only orders created within [$from, $to] with confirmed successful payments are included.
     * - Cancelled orders (`o.status = CANCELLED`) and fully refunded orders (`o.payment_status = REFUNDED`)
     *   are strictly excluded to ensure product sales totals align with net revenue semantics.
     * - Order-item level vs payment-level accounting: Line items (`order_items`) represent
     *   contracted prices at checkout and do not track partial refunds (which are ledgered
     *   at the payment level). Fully refunded orders are therefore omitted in their entirety,
     *   while macro-level partial refund deductions are reflected in summary() via payment refunds.
     */
    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 10): array
    {
        $from = Carbon::instance($from);
        $to = Carbon::instance($to);

        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->where('o.status', '!=', OrderStatusEnum::CANCELLED->value)
            ->where(function ($query) {
                $query->where('o.payment_status', '!=', PaymentStatusEnum::REFUNDED->value)
                    ->orWhereNull('o.payment_status');
            })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('payments as p')
                    ->whereColumn('p.order_id', 'o.id')
                    ->where('p.status', PaymentStatus::SUCCESS->value);
            })
            ->select(
                'oi.product_id',
                'oi.product_name_snapshot',
                DB::raw('SUM(oi.quantity) as total_qty'),
                DB::raw('SUM(oi.final_price) as total_amount'),
                DB::raw('COUNT(DISTINCT oi.order_id) as order_count')
            )
            ->groupBy('oi.product_id', 'oi.product_name_snapshot')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $productIds = $rows->pluck('product_id')->filter()->unique()->values()->all();
        $products = $productIds
            ? DB::table('products')->whereIn('id', $productIds)->get()->keyBy('id')
            : collect();

        return $rows->map(function ($row) use ($products) {
            $product = $products->get($row->product_id);

            return [
                'product_name' => $row->product_name_snapshot,
                'total_qty' => (int) $row->total_qty,
                'total_amount' => (int) $row->total_amount,
                'order_count' => (int) $row->order_count,
                'is_active' => $product ? (bool) $product->is_active : null,
            ];
        })->all();
    }

    public function paymentMethodBreakdown(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::instance($from);
        $to = Carbon::instance($to);

        $results = [];

        foreach (PaymentMethod::cases() as $method) {
            $successRevenue = (int) DB::table('payments')
                ->where('method', $method->value)
                ->where('status', PaymentStatus::SUCCESS->value)
                ->whereBetween('paid_at', [$from, $to])
                ->sum('paid_amount');

            $refundedRevenue = (int) Refund::query()
                ->where('status', RefundStatus::COMPLETED->value)
                ->whereBetween('refunded_at', [$from, $to])
                ->whereHas('payment', fn ($q) => $q->where('method', $method->value))
                ->sum('amount');

            $refundCount = Refund::query()
                ->where('status', RefundStatus::COMPLETED->value)
                ->whereBetween('refunded_at', [$from, $to])
                ->whereHas('payment', fn ($q) => $q->where('method', $method->value))
                ->count();

            $successCount = DB::table('payments')
                ->where('method', $method->value)
                ->where('status', PaymentStatus::SUCCESS->value)
                ->whereBetween('paid_at', [$from, $to])
                ->count();

            $pendingReviewCount = DB::table('payments')
                ->where('method', $method->value)
                ->where('status', PaymentStatus::PENDING_REVIEW->value)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $failedCount = DB::table('payments')
                ->where('method', $method->value)
                ->where('status', PaymentStatus::FAILED->value)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $cancelledCount = DB::table('payments')
                ->where('method', $method->value)
                ->where('status', PaymentStatus::CANCELLED->value)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $results[$method->value] = [
                'label' => $method->faLabel(),
                'success_count' => $successCount,
                'success_revenue' => $successRevenue - $refundedRevenue,
                'refund_count' => $refundCount,
                'refunded_revenue' => $refundedRevenue,
                'pending_review_count' => $pendingReviewCount,
                'failed_count' => $failedCount,
                'cancelled_count' => $cancelledCount,
            ];
        }

        return $results;
    }

    public function orderStatusBreakdown(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = Carbon::instance($from);
        $to = Carbon::instance($to);

        $counts = DB::table('orders')
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $results = [];

        foreach (OrderStatusEnum::cases() as $status) {
            $results[$status->value] = [
                'label' => $status->faLabel(),
                'count' => (int) ($counts->get($status->value) ?? 0),
            ];
        }

        return $results;
    }
}
