<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $orderCount = $user->orders()->count();

        $totalSpent = number_format((int) $user->orders()->sum('total_price'));

        $latestOrders = $user->orders()->latest()->take(8)->get();

        $latestOrder = $user->orders()->latest()->first();

        return view('customer.dashboard', [
            'orderCount' => $orderCount,
            'totalSpent' => $totalSpent,
            'latestOrders' => $latestOrders,
            'latestOrder' => $latestOrder,
        ]);
    }
}