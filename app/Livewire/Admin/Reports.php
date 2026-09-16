<?php

namespace App\Livewire\Admin;

use App\Services\ReportingService;
use App\Support\Dates\DateService;
use Carbon\Carbon;
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
        $dates = app(DateService::class);

        $this->fromDate = $dates->ascii($dates->jDate($dates->now()->subDays(29)));
        $this->toDate = $dates->ascii($dates->jDate($dates->now()));
        $this->compute();
    }

    public function applyFilter(): void
    {
        $this->validate([
            'fromDate' => ['required', 'string'],
            'toDate' => ['required', 'string'],
        ], [
            'fromDate.required' => 'تاریخ شروع الزامی است',
            'toDate.required' => 'تاریخ پایان الزامی است',
        ]);

        $dates = app(DateService::class);

        if (! $dates->isValidDate($this->fromDate)) {
            $this->addError('fromDate', 'تاریخ شروع نامعتبر است');

            return;
        }

        if (! $dates->isValidDate($this->toDate)) {
            $this->addError('toDate', 'تاریخ پایان نامعتبر است');

            return;
        }

        $from = $dates->fromJalali($this->fromDate);
        $to = $dates->fromJalali($this->toDate);

        if ($from->greaterThan($to)) {
            $this->addError('toDate', 'تاریخ پایان نباید قبل از تاریخ شروع باشد');

            return;
        }

        if ($dates->dayStartCanonical($from)->diffInDays($dates->dayEndCanonical($to), false) > 366) {
            $this->addError('toDate', 'بازه زمانی نباید بیش از یک سال باشد.');

            return;
        }

        $this->fromDate = $dates->ascii($dates->jDate($from));
        $this->toDate = $dates->ascii($dates->jDate($to));

        $this->resetErrorBag();
        $this->compute($from, $to);
    }

    public function clearFilter(): void
    {
        $dates = app(DateService::class);

        $this->fromDate = $dates->ascii($dates->jDate($dates->now()->subDays(29)));
        $this->toDate = $dates->ascii($dates->jDate($dates->now()));
        $this->resetErrorBag();
        $this->compute();
    }

    public function render()
    {
        return view('livewire.admin.reports')->layout('layouts.admin')->title('گزارش‌ها');
    }

    private function compute(?Carbon $from = null, ?Carbon $to = null): void
    {
        $dates = app(DateService::class);

        if ($from === null) {
            $from = $dates->fromJalali($this->fromDate);
        }

        if ($to === null) {
            $to = $this->toDate !== '' ? $dates->fromJalali($this->toDate) : null;
        }

        if ($from === null || $to === null) {
            $from = $dates->dayStartCanonical($dates->now()->subDays(29));
            $to = $dates->dayEndCanonical($dates->now());
        }

        $from = $dates->dayStartCanonical($from);
        $to = $dates->dayEndCanonical($to);

        $service = app(ReportingService::class);

        $this->summary = $service->summary($from, $to);
        $this->revenueTrend = $service->revenueTrend($from, $to);
        $this->topProducts = $service->topProducts($from, $to);
        $this->paymentMethodBreakdown = $service->paymentMethodBreakdown($from, $to);
        $this->orderStatusBreakdown = $service->orderStatusBreakdown($from, $to);

        $this->maxTrendRevenue = collect($this->revenueTrend)->max('revenue') ?: 1;
    }
}
