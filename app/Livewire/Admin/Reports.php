<?php

namespace App\Livewire\Admin;

use App\Services\ReportingService;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Reports extends Component
{
    public string $fromDate = '';

    public string $toDate = '';

    public array $summary = [];

    public array $revenueTrend = [];

    public array $topProducts = [];

    public array $paymentMethodBreakdown = [];

    public array $orderStatusBreakdown = [];

    public int $maxTrendRevenue = 0;

    public function mount(): void
    {
        $this->fromDate = now()->subDays(29)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->compute();
    }

    public function applyFilter(): void
    {
        $this->validate([
            'fromDate' => ['required', 'date'],
            'toDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ], [
            'fromDate.required' => 'تاریخ شروع الزامی است',
            'toDate.required' => 'تاریخ پایان الزامی است',
            'toDate.after_or_equal' => 'تاریخ پایان نباید قبل از تاریخ شروع باشد',
        ]);

        $tz = config('app.timezone');
        $from = Carbon::parse($this->fromDate, $tz)->startOfDay();
        $to = Carbon::parse($this->toDate, $tz)->endOfDay();

        if ($from->diffInDays($to) > 365) {
            $this->addError('toDate', 'بازه زمانی نباید بیش از یک سال باشد.');

            return;
        }

        $this->compute();
    }

    public function clearFilter(): void
    {
        $this->fromDate = now()->subDays(29)->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->compute();
    }

    public function render()
    {
        return view('livewire.admin.reports')->layout('layouts.admin')->title('گزارش‌ها');
    }

    private function compute(): void
    {
        $tz = config('app.timezone');
        $from = Carbon::parse($this->fromDate, $tz)->startOfDay();
        $to = Carbon::parse($this->toDate, $tz)->endOfDay();

        $service = app(ReportingService::class);

        $this->summary = $service->summary($from, $to);
        $this->revenueTrend = $service->revenueTrend($from, $to);
        $this->topProducts = $service->topProducts($from, $to);
        $this->paymentMethodBreakdown = $service->paymentMethodBreakdown($from, $to);
        $this->orderStatusBreakdown = $service->orderStatusBreakdown($from, $to);

        $this->maxTrendRevenue = collect($this->revenueTrend)->max('revenue') ?: 1;
    }
}
