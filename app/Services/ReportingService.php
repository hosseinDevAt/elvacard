<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $tz = config('app.timezone');

        $from = Carbon::parse($from, $tz)->startOfDay();
        $to = Carbon::parse($to, $tz)->endOfDay();

        $revenue = (int) Payment::query()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('paid_amount');

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
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return [
            'revenue' => $revenue,
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders,
            'cancelledOrders' => $cancelledOrders,
            'successfulPayments' => $successfulPayments,
            'pendingReviewPayments' => $pendingReviewPayments,
            'activeCustomerCount' => $activeCustomerCount,
        ];
    }

    public function revenueTrend(CarbonInterface $from, CarbonInterface $to): array
    {
        $tz = config('app.timezone');

        $from = Carbon::parse($from, $tz)->startOfDay();
        $to = Carbon::parse($to, $tz)->endOfDay();

        $daily = $from->diffInDays($to) <= 31;

        if ($daily) {
            $rows = DB::table('payments')
                ->where('status', PaymentStatus::SUCCESS->value)
                ->whereBetween('paid_at', [$from, $to])
                ->select(
                    DB::raw('DATE(paid_at) as period'),
                    DB::raw('SUM(paid_amount) as revenue'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $filled = [];
            $cursor = $from->copy()->startOfDay();

            while ($cursor->lte($to)) {
                $key = $cursor->format('Y-m-d');
                $filled[$key] = ['period' => $key, 'revenue' => 0, 'count' => 0];
                $cursor->addDay();
            }

            foreach ($rows as $row) {
                $filled[$row->period] = [
                    'period' => $row->period,
                    'revenue' => (int) $row->revenue,
                    'count' => (int) $row->count,
                ];
            }

            return array_values($filled);
        }

        $driver = DB::getDriverName();

        $monthExpr = $driver === 'mysql'
            ? "DATE_FORMAT(paid_at, '%Y-%m')"
            : "strftime('%Y-%m', paid_at)";

        $rows = DB::table('payments')
            ->where('status', PaymentStatus::SUCCESS->value)
            ->whereBetween('paid_at', [$from, $to])
            ->select(
                DB::raw("{$monthExpr} as period"),
                DB::raw('SUM(paid_amount) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $filled = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $filled[$key] = ['period' => $key, 'revenue' => 0, 'count' => 0];
            $cursor->addMonthNoOverflow();
        }

        foreach ($rows as $row) {
            $filled[$row->period] = [
                'period' => $row->period,
                'revenue' => (int) $row->revenue,
                'count' => (int) $row->count,
            ];
        }

        return array_values($filled);
    }

    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 10): array
    {
        $tz = config('app.timezone');

        $from = Carbon::parse($from, $tz)->startOfDay();
        $to = Carbon::parse($to, $tz)->endOfDay();

        $rows = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereBetween('o.created_at', [$from, $to])
            ->where('o.status', '!=', OrderStatusEnum::CANCELLED->value)
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
        $tz = config('app.timezone');

        $from = Carbon::parse($from, $tz)->startOfDay();
        $to = Carbon::parse($to, $tz)->endOfDay();

        $results = [];

        foreach (PaymentMethod::cases() as $method) {
            $successRevenue = (int) DB::table('payments')
                ->where('method', $method->value)
                ->where('status', PaymentStatus::SUCCESS->value)
                ->whereBetween('paid_at', [$from, $to])
                ->sum('paid_amount');

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
                'success_revenue' => $successRevenue,
                'pending_review_count' => $pendingReviewCount,
                'failed_count' => $failedCount,
                'cancelled_count' => $cancelledCount,
            ];
        }

        return $results;
    }

    public function orderStatusBreakdown(CarbonInterface $from, CarbonInterface $to): array
    {
        $tz = config('app.timezone');

        $from = Carbon::parse($from, $tz)->startOfDay();
        $to = Carbon::parse($to, $tz)->endOfDay();

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
