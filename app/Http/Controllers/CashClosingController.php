<?php

namespace App\Http\Controllers;

use App\Models\CashExpense;
use App\Models\CashFixedExpense;
use App\Models\Order;
use App\Models\Reagent;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashClosingController extends Controller
{
    private const TIPOS_PAGO = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin', 'Convenio'];

    private const PERIODS = [
        'day' => 'Día',
        'week' => 'Semana',
        'fortnight' => 'Quincena',
        'month' => 'Mensual',
        'year' => 'Anual',
    ];

    public function index(Request $request): View
    {
        return view('cash-closings.index', $this->reportData($request));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $data = $this->reportData($request);
        $filename = 'cuadre-caja-'.$data['from'].'_'.$data['to'].'.xls';

        return response()->streamDownload(function () use ($data) {
            echo view('cash-closings.exports.excel', $data)->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $data = $this->reportData($request);

        return Pdf::loadView('cash-closings.exports.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->stream('cuadre-caja-'.$data['from'].'_'.$data['to'].'.pdf');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha_egreso' => ['required', 'date'],
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        if ($request->hasFile('archivo')) {
            $data['archivo_path'] = $request->file('archivo')->store('egresos-caja', 'public');
        }

        $data['created_by'] = auth()->id();
        CashExpense::create($data);

        return redirect()->route('cash-closings.index', $request->only(['period', 'base_date', 'from', 'to', 'tipo_pago', 'tab']))->with('success', 'Egreso registrado correctamente.');
    }

    public function storeFixedExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $data['created_by'] = auth()->id();
        CashFixedExpense::create($data);

        return redirect()->route('cash-closings.index', array_merge($request->only(['period', 'base_date', 'tipo_pago']), ['tab' => 'fijos']))->with('success', 'Gasto fijo registrado correctamente.');
    }

    public function updateFixedExpense(Request $request, CashFixedExpense $cashFixedExpense): RedirectResponse
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $data['activo'] = $request->boolean('activo');
        $cashFixedExpense->update($data);

        return redirect()->route('cash-closings.index', array_merge($request->only(['period', 'base_date', 'tipo_pago']), ['tab' => 'fijos']))->with('success', 'Gasto fijo actualizado correctamente.');
    }

    public function executeFixedExpenses(Request $request): RedirectResponse
    {
        $periodDate = $request->date('period_date') ?: now();
        $period = $periodDate->format('Y-m');
        $expenseDate = $periodDate->copy()->endOfMonth()->toDateString();
        $fixedExpenses = CashFixedExpense::where('activo', true)->get();
        $created = 0;

        foreach ($fixedExpenses as $fixedExpense) {
            $expense = CashExpense::firstOrCreate(
                ['cash_fixed_expense_id' => $fixedExpense->id, 'fixed_expense_period' => $period],
                [
                    'fecha_egreso' => $expenseDate,
                    'descripcion' => 'Gasto fijo: '.$fixedExpense->descripcion,
                    'monto' => $fixedExpense->monto,
                    'created_by' => auth()->id(),
                ]
            );

            if ($expense->wasRecentlyCreated) {
                $created++;
            }
        }

        $message = $created > 0
            ? $created.' gasto(s) fijo(s) ejecutado(s) para '.$period.'.'
            : 'Los gastos fijos de '.$period.' ya habían sido ejecutados.';

        return redirect()->route('cash-closings.index', ['period' => 'month', 'tab' => 'fijos'])->with('success', $message);
    }

    public function destroyExpense(CashExpense $cashExpense): RedirectResponse
    {
        if ($cashExpense->archivo_path) {
            Storage::disk('public')->delete($cashExpense->archivo_path);
        }

        $cashExpense->delete();

        return redirect()->route('cash-closings.index')->with('success', 'Egreso eliminado correctamente.');
    }

    private function reportData(Request $request): array
    {
        [$from, $to, $period, $baseDate] = $this->resolveRange($request);
        $tipoPago = in_array($request->query('tipo_pago'), self::TIPOS_PAGO, true)
            ? $request->query('tipo_pago')
            : null;
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $orders = Order::with(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'admissionForm', 'consumables.reagent'])
            ->whereBetween('fecha_orden', [$start, $end])
            ->where('estado', '!=', 'Anulado')
            ->when($tipoPago, fn ($query) => $query->where('tipo_pago', $tipoPago))
            ->latest('fecha_orden')
            ->get();

        $expenses = CashExpense::with(['creator', 'fixedExpense'])
            ->whereBetween('fecha_egreso', [$start->toDateString(), $end->toDateString()])
            ->latest('fecha_egreso')
            ->latest()
            ->get();

        $incomeTotal = $orders->sum('total');
        $expenseTotal = $expenses->sum('monto');
        $cashIncome = $orders->where('tipo_pago', 'Efectivo')->sum('total');
        $yapePlinIncome = $orders->where('tipo_pago', 'Yape/Plin')->sum('total');
        $transferIncome = $orders->where('tipo_pago', 'Transferencia')->sum('total');
        $digitalIncome = $yapePlinIncome + $transferIncome;
        $incomeByPayment = $orders->groupBy(fn (Order $order) => $order->tipo_pago ?? 'Sin método')
            ->map(fn ($items) => $items->sum('total'));
        $operationalBaseDate = $request->date('operational_base_date')?->toDateString() ?: $baseDate;
        $operationalTipoPago = in_array($request->query('operational_tipo_pago'), self::TIPOS_PAGO, true)
            ? $request->query('operational_tipo_pago')
            : null;
        $operationalStart = Carbon::parse($operationalBaseDate)->startOfDay();
        $operationalEnd = Carbon::parse($operationalBaseDate)->endOfDay();
        $operationalOrders = Order::with(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'admissionForm', 'consumables.reagent'])
            ->whereBetween('fecha_orden', [$operationalStart, $operationalEnd])
            ->where('estado', '!=', 'Anulado')
            ->when($operationalTipoPago, fn ($query) => $query->where('tipo_pago', $operationalTipoPago))
            ->latest('fecha_orden')
            ->get();
        $operationalExpenses = CashExpense::with(['creator', 'fixedExpense'])
            ->whereBetween('fecha_egreso', [$operationalStart->toDateString(), $operationalEnd->toDateString()])
            ->latest('fecha_egreso')
            ->latest()
            ->get();
        $operationalIncomeTotal = $operationalOrders->sum('total');
        $operationalExpenseTotal = $operationalExpenses->sum('monto');
        $operationalYapePlinIncome = $operationalOrders->where('tipo_pago', 'Yape/Plin')->sum('total');
        $operationalTransferIncome = $operationalOrders->where('tipo_pago', 'Transferencia')->sum('total');
        $operationalPlateSummary = $this->stockSummary($operationalOrders, $operationalStart, $operationalEnd, 'placa', 'Placas', function (Order $order): float {
            $data = $order->admissionForm?->data ?? [];

            return (float) ($data['delivery_quantities']['PLACAS'] ?? $data['plates_count'] ?? 0);
        });
        $operationalIopamidolSummary = $this->stockSummary($operationalOrders, $operationalStart, $operationalEnd, 'iopamidol', 'Iopamidol', function (Order $order): float {
            return (float) $order->consumables
                ->filter(fn ($consumable) => str_contains(strtolower($consumable->reagent->nombre ?? ''), 'iopamidol'))
                ->sum('cantidad');
        });

        $plateSummary = $this->stockSummary($orders, $start, $end, 'placa', 'Placas', function (Order $order): float {
            $data = $order->admissionForm?->data ?? [];

            return (float) ($data['delivery_quantities']['PLACAS'] ?? $data['plates_count'] ?? 0);
        });
        $iopamidolSummary = $this->stockSummary($orders, $start, $end, 'iopamidol', 'Iopamidol', function (Order $order): float {
            return (float) $order->consumables
                ->filter(fn ($consumable) => str_contains(strtolower($consumable->reagent->nombre ?? ''), 'iopamidol'))
                ->sum('cantidad');
        });

        $fixedExpenses = CashFixedExpense::withCount('expenses')->latest()->get();
        $currentFixedExpensePeriod = now()->format('Y-m');
        $monthlyFixedExpensesPending = $this->pendingFixedExpenses($currentFixedExpensePeriod);
        $shouldShowFixedExpenseModal = now()->isLastOfMonth() && $monthlyFixedExpensesPending->isNotEmpty();

        return compact('from', 'to', 'period', 'baseDate', 'tipoPago', 'orders', 'expenses', 'incomeTotal', 'expenseTotal', 'cashIncome', 'yapePlinIncome', 'transferIncome', 'digitalIncome', 'incomeByPayment', 'plateSummary', 'iopamidolSummary', 'operationalBaseDate', 'operationalTipoPago', 'operationalOrders', 'operationalExpenses', 'operationalIncomeTotal', 'operationalExpenseTotal', 'operationalYapePlinIncome', 'operationalTransferIncome', 'operationalPlateSummary', 'operationalIopamidolSummary') + [
            'cashBalance' => $cashIncome - $expenseTotal,
            'balance' => $incomeTotal - $expenseTotal,
            'tiposPago' => self::TIPOS_PAGO,
            'periods' => self::PERIODS,
            'cashOrders' => $orders->where('tipo_pago', 'Efectivo'),
            'yapePlinOrders' => $orders->where('tipo_pago', 'Yape/Plin'),
            'transferOrders' => $orders->where('tipo_pago', 'Transferencia'),
            'fixedExpenses' => $fixedExpenses,
            'currentFixedExpensePeriod' => $currentFixedExpensePeriod,
            'monthlyFixedExpensesPending' => $monthlyFixedExpensesPending,
            'shouldShowFixedExpenseModal' => $shouldShowFixedExpenseModal,
        ];
    }

    private function pendingFixedExpenses(string $period)
    {
        return CashFixedExpense::where('activo', true)
            ->whereDoesntHave('expenses', fn ($query) => $query->where('fixed_expense_period', $period))
            ->orderBy('descripcion')
            ->get();
    }

    private function stockSummary($orders, Carbon $start, Carbon $end, string $reagentSearch, string $fallbackName, callable $deliveredResolver): array
    {
        $delivered = (float) $orders->sum(fn (Order $order): float => $deliveredResolver($order));

        $reagent = Reagent::query()
            ->where('nombre', 'like', '%'.$reagentSearch.'%')
            ->orderBy('nombre')
            ->first();

        if (! $reagent) {
            return [
                'initial' => 0,
                'received' => 0,
                'delivered' => $delivered,
                'final' => max(0, 0 - $delivered),
                'name' => $fallbackName,
                'unit' => '',
                'tracked' => false,
            ];
        }

        $movements = StockMovement::query()
            ->where('reagent_id', $reagent->id)
            ->whereBetween('fecha_movimiento', [$start, $end])
            ->get();

        $received = (float) $movements->where('tipo_movimiento', 'Ingreso')->sum('cantidad');
        $stockAtEnd = (float) $reagent->stock_actual;
        $netAfterEnd = (float) StockMovement::query()
            ->where('reagent_id', $reagent->id)
            ->where('fecha_movimiento', '>', $end)
            ->get()
            ->sum(fn (StockMovement $movement) => $movement->tipo_movimiento === 'Ingreso' ? $movement->cantidad : -$movement->cantidad);
        $final = $stockAtEnd - $netAfterEnd;
        $initial = $final - $received + $delivered;

        return [
            'initial' => max(0, $initial),
            'received' => $received,
            'delivered' => $delivered,
            'final' => max(0, $final),
            'name' => $reagent->nombre,
            'unit' => $reagent->unidad,
            'tracked' => true,
        ];
    }

    private function resolveRange(Request $request): array
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'day';
        $baseDate = $period === 'day'
            ? ($request->date('base_date')?->toDateString() ?: now()->toDateString())
            : now()->toDateString();
        $base = Carbon::parse($baseDate);

        return match ($period) {
            'week' => [$base->copy()->startOfWeek()->toDateString(), $base->copy()->endOfWeek()->toDateString(), $period, $baseDate],
            'fortnight' => [
                $base->day <= 15 ? $base->copy()->startOfMonth()->toDateString() : $base->copy()->day(16)->toDateString(),
                $base->day <= 15 ? $base->copy()->day(15)->toDateString() : $base->copy()->endOfMonth()->toDateString(),
                $period,
                $baseDate,
            ],
            'month' => [$base->copy()->startOfMonth()->toDateString(), $base->copy()->endOfMonth()->toDateString(), $period, $baseDate],
            'year' => [$base->copy()->startOfYear()->toDateString(), $base->copy()->endOfYear()->toDateString(), $period, $baseDate],
            default => [$base->toDateString(), $base->toDateString(), $period, $baseDate],
        };
    }
}
