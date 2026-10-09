<?php

namespace App\Http\Controllers;

use App\Models\OrderProcessGuide;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderProcessGuideController extends Controller
{
    /**
     * Display the public order process guides for metallic bank and fuel cards.
     */
    public function index(Request $request): View
    {
        $requestedWorkflow = $request->query('workflow', OrderProcessGuide::WORKFLOW_BANK);
        if (! in_array($requestedWorkflow, OrderProcessGuide::WORKFLOWS, true)) {
            $requestedWorkflow = OrderProcessGuide::WORKFLOW_BANK;
        }

        $bankGuide = null;
        $fuelGuide = null;

        if (OrderProcessGuide::tablesExist()) {
            $guides = OrderProcessGuide::with('activeStages')
                ->whereIn('workflow', OrderProcessGuide::WORKFLOWS)
                ->get()
                ->keyBy('workflow');

            $bankGuide = $guides->get(OrderProcessGuide::WORKFLOW_BANK);
            $fuelGuide = $guides->get(OrderProcessGuide::WORKFLOW_FUEL);
        }

        return view('order-process.index', [
            'activeWorkflow' => $requestedWorkflow,
            'bankGuide' => $bankGuide,
            'fuelGuide' => $fuelGuide,
        ]);
    }
}
