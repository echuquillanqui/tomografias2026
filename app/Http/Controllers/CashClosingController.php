<?php

namespace App\Http\Controllers;

use App\Models\CashExpense;
use App\Models\Order;
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
        'custom' => 'Personalizado',
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

        return redirect()->route('cash-closings.index', $request->only(['period', 'base_date', 'from', 'to', 'tipo_pago']))->with('success', 'Egreso registrado correctamente.');
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

        $orders = Order::with(['patient', 'agreement'])
            ->whereBetween('fecha_orden', [$start->toDateString(), $end->toDateString()])
            ->where('estado', '!=', 'Anulado')
            ->when($tipoPago, fn ($query) => $query->where('tipo_pago', $tipoPago))
            ->latest('fecha_orden')
            ->get();

        $expenses = CashExpense::with('creator')
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

        return compact('from', 'to', 'period', 'baseDate', 'tipoPago', 'orders', 'expenses', 'incomeTotal', 'expenseTotal', 'cashIncome', 'yapePlinIncome', 'transferIncome', 'digitalIncome', 'incomeByPayment') + [
            'cashBalance' => $cashIncome - $expenseTotal,
            'balance' => $incomeTotal - $expenseTotal,
            'tiposPago' => self::TIPOS_PAGO,
            'periods' => self::PERIODS,
            'cashOrders' => $orders->where('tipo_pago', 'Efectivo'),
            'yapePlinOrders' => $orders->where('tipo_pago', 'Yape/Plin'),
            'transferOrders' => $orders->where('tipo_pago', 'Transferencia'),
        ];
    }

    private function resolveRange(Request $request): array
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : 'day';
        $baseDate = $request->date('base_date')?->toDateString() ?: now()->toDateString();
        $base = Carbon::parse($baseDate);

        if ($period === 'custom') {
            $from = $request->date('from')?->toDateString() ?: $base->toDateString();
            $to = $request->date('to')?->toDateString() ?: $from;

            return [$from, $to, $period, $baseDate];
        }

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
