<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Exam;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const ESTADOS = ['Pendiente', 'En proceso', 'Informado', 'Entregado', 'Anulado'];
    private const TIPOS_PAGO = ['Efectivo', 'Tarjeta', 'Transferencia', 'Yape/Plin', 'Convenio'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $orders = Order::with(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme'])
            ->withCount('orderExams')
            ->when($search !== '', fn ($q) => $q->where('codigo_orden', 'like', "%{$search}%")
                ->orWhereHas('patient', fn ($qq) => $qq->where('dni', 'like', "%{$search}%")
                    ->orWhere('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")))
            ->latest('fecha_orden')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', compact('orders', 'search'));
    }

    public function create(Request $request): View
    {
        return view('orders.form', $this->formData($request) + [
            'order' => new Order(['fecha_orden' => now(), 'estado' => 'Pendiente', 'descuento' => 0]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $order = DB::transaction(fn () => $this->saveOrder(new Order(), $request));

        return redirect()->route('orders.show', $order)->with('success', 'Orden generada correctamente.');
    }

    public function show(Order $order): View
    {
        $order->load(['patient', 'agreement', 'medicoSolicitante', 'medicoInforme', 'orderExams.exam', 'creator']);

        return view('orders.show', compact('order'));
    }

    public function edit(Request $request, Order $order): View
    {
        $order->load('orderExams');

        return view('orders.form', $this->formData($request, $order) + ['order' => $order, 'mode' => 'edit']);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(fn () => $this->saveOrder($order, $request));

        return redirect()->route('orders.show', $order)->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Orden eliminada correctamente.');
    }

    private function formData(Request $request, ?Order $order = null): array
    {
        $medicos = User::select(['id', 'nombre_completo', 'tipo_medico', 'comision_porcentaje'])
            ->where('rol', 'Médico')
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();

        return [
            'patients' => Patient::select(['id', 'dni', 'nombres', 'apellidos'])->orderBy('apellidos')->orderBy('nombres')->get(),
            'agreements' => Agreement::select(['id', 'nombre_institucion'])->where('activo', true)->orderBy('nombre_institucion')->get(),
            'exams' => Exam::select(['id', 'nombre_examen'])->where('activo', true)->orderBy('nombre_examen')->get(),
            'medicosSolicitantes' => $medicos->whereIn('tipo_medico', ['Solicitante', 'Ambos'])->values(),
            'medicosInformantes' => $medicos->whereIn('tipo_medico', ['De Informe', 'Ambos'])->values(),
            'estados' => self::ESTADOS,
            'tiposPago' => self::TIPOS_PAGO,
        ];
    }

    private function saveOrder(Order $order, Request $request): Order
    {
        $request->merge(['exams' => collect($request->input('exams', []))->filter(fn ($row) => ! empty($row['exam_id']))->values()->all()]);
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'agreement_id' => ['required', 'exists:agreements,id'],
            'medico_solicitante_id' => ['nullable', 'exists:users,id'],
            'medico_informe_id' => ['nullable', 'exists:users,id'],
            'fecha_orden' => ['required', 'date'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'tipo_pago' => ['required', Rule::in(self::TIPOS_PAGO)],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
            'exams' => ['required', 'array', 'min:1'],
            'exams.*.exam_id' => ['required', 'exists:exams,id'],
            'exams.*.tipo_contraste' => ['required', Rule::in(['Con contraste', 'Sin contraste'])],
            'exams.*.precio' => ['required', 'numeric', 'min:0'],
            'exams.*.estado' => ['required', Rule::in(['Pendiente', 'Realizado', 'Informado', 'Anulado'])],
        ]);
        $subtotal = collect($data['exams'])->sum('precio');
        $descuento = $data['descuento'] ?? 0;
        $payload = $data + [
            'codigo_orden' => $order->exists ? $order->codigo_orden : $this->nextCode(),
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => max($subtotal - $descuento, 0),
            'created_by' => $order->exists ? $order->created_by : auth()->id(),
        ];
        unset($payload['exams']);
        $order->fill($payload)->save();
        $order->orderExams()->delete();
        $med = User::find($data['medico_informe_id'] ?? null);
        $pct = $med?->comision_porcentaje;
        foreach ($data['exams'] as $row) {
            $order->orderExams()->create($row + [
                'comision_porcentaje' => $pct,
                'comision_monto' => $pct ? ($row['precio'] * $pct / 100) : null,
            ]);
        }

        return $order;
    }

    private function nextCode(): string
    {
        $id = (Order::max('id') ?? 0) + 1;

        return 'ORD-' . now()->format('Ymd') . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
    }
}
