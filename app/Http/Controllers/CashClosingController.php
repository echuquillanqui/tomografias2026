<?php

namespace App\Http\Controllers;

use App\Models\CashExpense;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CashClosingController extends Controller
{
    private const TIPOS_PAGO = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin', 'Convenio'];

    public function index(Request $request): View
    {
        $from = $request->date('from')?->toDateString() ?: now()->toDateString();
        $to = $request->date('to')?->toDateString() ?: $from;
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

        return view('cash-closings.index', compact('from', 'to', 'tipoPago', 'orders', 'expenses', 'incomeTotal', 'expenseTotal') + [
            'balance' => $incomeTotal - $expenseTotal,
            'incomeByPayment' => $orders->groupBy(fn (Order $order) => $order->tipo_pago ?? 'Sin método')
                ->map(fn ($items) => $items->sum('total')),
            'tiposPago' => self::TIPOS_PAGO,
        ]);
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

        return redirect()->route('cash-closings.index', $request->only(['from', 'to', 'tipo_pago']))->with('success', 'Egreso registrado correctamente.');
    }

    public function destroyExpense(CashExpense $cashExpense): RedirectResponse
    {
        if ($cashExpense->archivo_path) {
            Storage::disk('public')->delete($cashExpense->archivo_path);
        }

        $cashExpense->delete();

        return redirect()->route('cash-closings.index')->with('success', 'Egreso eliminado correctamente.');
    }
}
