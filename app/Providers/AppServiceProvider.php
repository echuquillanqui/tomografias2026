<?php

namespace App\Providers;

use App\Models\CashFixedExpense;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $period = now()->format('Y-m');
            $pending = collect();

            if (auth()->check() && now()->isLastOfMonth()) {
                $pending = CashFixedExpense::where('activo', true)
                    ->whereDoesntHave('expenses', fn ($query) => $query->where('fixed_expense_period', $period))
                    ->orderBy('descripcion')
                    ->get();
            }

            $view->with([
                'layoutFixedExpensePeriod' => $period,
                'layoutPendingFixedExpenses' => $pending,
                'layoutShouldShowFixedExpenseModal' => $pending->isNotEmpty(),
            ]);
        });
    }
}
