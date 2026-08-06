<?php

namespace Tests\Unit;

use App\Http\Controllers\CashClosingController;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class CashClosingRangeTest extends TestCase
{
    public function test_monthly_range_uses_the_selected_month(): void
    {
        $request = Request::create('/cash-closings', 'GET', [
            'period' => 'month',
            'base_month' => '2026-02',
        ]);

        $method = new ReflectionMethod(CashClosingController::class, 'resolveRange');
        $range = $method->invoke(new CashClosingController(), $request);

        $this->assertSame(['2026-02-01', '2026-02-28', 'month', '2026-02-01'], $range);
    }

    public function test_monthly_range_ignores_an_invalid_month(): void
    {
        $this->travelTo('2026-08-06');
        $request = Request::create('/cash-closings', 'GET', [
            'period' => 'month',
            'base_month' => '2026-13',
        ]);

        $method = new ReflectionMethod(CashClosingController::class, 'resolveRange');
        $range = $method->invoke(new CashClosingController(), $request);

        $this->assertSame(['2026-08-01', '2026-08-31', 'month', '2026-08-06'], $range);
    }
}
