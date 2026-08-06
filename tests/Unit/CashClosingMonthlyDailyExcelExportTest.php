<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class CashClosingMonthlyDailyExcelExportTest extends TestCase
{
    public function test_monthly_report_has_one_daily_closing_sheet_for_every_day(): void
    {
        $days = collect(range(1, 31))->map(fn (int $day) => [
            'date' => Carbon::create(2026, 8, $day),
            'orders' => collect(),
            'expenses' => collect(),
            'incomeTotal' => 0,
            'expenseTotal' => 0,
            'yapePlinIncome' => 0,
            'transferIncome' => 0,
            'plateSummary' => ['initial' => 10, 'delivered' => 0, 'final' => 10],
            'iopamidolSummary' => ['initial' => 5, 'delivered' => 0, 'final' => 5],
        ]);

        $xml = view('cash-closings.exports.monthly-daily-excel', [
            'dailyClosings' => $days,
            'month' => Carbon::create(2026, 8, 1),
        ])->render();

        $this->assertSame(31, substr_count($xml, '<Worksheet ss:Name='));
        $this->assertStringContainsString('<Worksheet ss:Name="01-08-2026">', $xml);
        $this->assertStringContainsString('<Worksheet ss:Name="31-08-2026">', $xml);
        $this->assertSame(31, substr_count($xml, 'TOTALES DEL DÍA'));
        $this->assertStringNotContainsString('ss:Name="Resumen"', $xml);
    }
}
