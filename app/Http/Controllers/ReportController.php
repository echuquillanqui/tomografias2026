<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $orders = Order::with(['patient', 'agreement', 'medicoInforme', 'report.medicoFirmante'])
            ->withCount('orderExams')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('codigo_orden', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($patient) => $patient->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%"));
            }))
            ->latest('fecha_orden')
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', compact('orders', 'search'));
    }

    public function edit(Order $order): View
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'report']);
        $this->ensureReport($order);

        return view('reports.edit', [
            'order' => $order->fresh(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'report']),
            'medicosInformantes' => $this->medicosInformantes(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'contenido' => ['required', 'string'],
            'medico_firmante_id' => ['nullable', 'exists:users,id'],
        ]);

        $order->update(['medico_informe_id' => $data['medico_firmante_id']]);
        $order->report()->updateOrCreate(
            ['order_id' => $order->id],
            $data
        );

        return redirect()->route('reports.edit', $order)->with('success', 'Informe actualizado correctamente.');
    }

    public function pdf(Order $order)
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'orderExams.exam', 'report.medicoFirmante']);
        $this->ensureReport($order);
        $order->load('report.medicoFirmante');

        return Pdf::loadView('reports.pdf', ['order' => $order])->stream('informe-orden-'.$order->id.'.pdf');
    }

    private function ensureReport(Order $order): void
    {
        if ($order->report) {
            return;
        }

        app(OrderController::class)->createInitialReport($order);
    }

    private function medicosInformantes()
    {
        return User::select(['id', 'nombre_completo', 'tipo_medico', 'cmp', 'rne', 'firma_path'])
            ->where('rol', 'Médico')
            ->where('activo', true)
            ->whereIn('tipo_medico', ['De Informe', 'Ambos'])
            ->orderBy('nombre_completo')
            ->get();
    }
}
