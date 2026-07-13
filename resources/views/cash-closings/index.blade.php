@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Caja</div>
                <h1 class="display-6 fw-bold">Cuadre de caja</h1>
                <p class="mb-0 opacity-75">Consolida las entradas por órdenes y registra egresos con sustento adjunto.</p>
            </div>
            <form method="GET" class="d-flex flex-column flex-sm-row gap-2 align-self-lg-start">
                <input type="date" name="from" value="{{ $from }}" class="form-control" aria-label="Desde">
                <input type="date" name="to" value="{{ $to }}" class="form-control" aria-label="Hasta">
                <button class="btn btn-light fw-bold">Filtrar</button>
            </form>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card clinic-card h-100"><div class="card-body"><div class="text-muted small fw-bold">ENTRADAS</div><div class="display-6 fw-bold text-success">S/ {{ number_format($incomeTotal, 2) }}</div><div class="text-muted">{{ $orders->count() }} órdenes cobradas/no anuladas</div></div></div></div>
        <div class="col-md-4"><div class="card clinic-card h-100"><div class="card-body"><div class="text-muted small fw-bold">EGRESOS</div><div class="display-6 fw-bold text-danger">S/ {{ number_format($expenseTotal, 2) }}</div><div class="text-muted">{{ $expenses->count() }} salidas registradas</div></div></div></div>
        <div class="col-md-4"><div class="card clinic-card h-100"><div class="card-body"><div class="text-muted small fw-bold">SALDO FINAL</div><div class="display-6 fw-bold {{ $balance < 0 ? 'text-danger' : 'text-primary' }}">S/ {{ number_format($balance, 2) }}</div><div class="text-muted">Entradas menos egresos</div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card clinic-card mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="fw-bold mb-0">Registrar egreso</h5></div>
                <div class="card-body px-4 pb-4">
                    <form method="POST" action="{{ route('cash-closings.expenses.store', ['from' => $from, 'to' => $to]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3"><label class="form-label fw-bold">Fecha</label><input type="date" name="fecha_egreso" value="{{ old('fecha_egreso', $to) }}" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Descripción</label><input name="descripcion" value="{{ old('descripcion') }}" class="form-control" maxlength="255" required placeholder="Ej. Compra de útiles, movilidad..."></div>
                        <div class="mb-3"><label class="form-label fw-bold">Monto</label><div class="input-group"><span class="input-group-text">S/</span><input type="number" step="0.01" min="0.01" name="monto" value="{{ old('monto') }}" class="form-control" required></div></div>
                        <div class="mb-3"><label class="form-label fw-bold">Archivo sustentatorio</label><input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"><div class="form-text">PDF, imagen u Office hasta 10 MB.</div></div>
                        <button class="btn btn-clinic-primary w-100">Guardar egreso</button>
                    </form>
                </div>
            </div>
            <div class="card clinic-card"><div class="card-body"><h6 class="fw-bold">Entradas por método de pago</h6>@forelse($incomeByPayment as $payment => $amount)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $payment }}</span><strong>S/ {{ number_format($amount, 2) }}</strong></div>@empty<p class="text-muted mb-0">Sin entradas en el rango.</p>@endforelse</div></div>
        </div>

        <div class="col-lg-8">
            <div class="card clinic-card mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="fw-bold mb-0">Egresos del periodo</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-clinic align-middle mb-0"><thead><tr><th>Fecha</th><th>Descripción</th><th>Monto</th><th>Archivo</th><th>Usuario</th><th></th></tr></thead><tbody>@forelse($expenses as $expense)<tr><td>{{ $expense->fecha_egreso->format('d/m/Y') }}</td><td class="fw-semibold">{{ $expense->descripcion }}</td><td class="text-danger fw-bold">S/ {{ number_format($expense->monto, 2) }}</td><td>@if($expense->archivo_path)<a target="_blank" href="{{ asset('storage/'.$expense->archivo_path) }}">Ver archivo</a>@else — @endif</td><td>{{ $expense->creator->username ?? '—' }}</td><td class="text-end"><form method="POST" action="{{ route('cash-closings.expenses.destroy', $expense) }}" onsubmit="return confirm('¿Eliminar este egreso?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Eliminar</button></form></td></tr>@empty<tr><td colspan="6" class="text-center py-4">Sin egresos registrados.</td></tr>@endforelse</tbody></table></div></div>
            </div>

            <div class="card clinic-card">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="fw-bold mb-0">Entradas por órdenes</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-clinic align-middle mb-0"><thead><tr><th>Fecha</th><th>Orden</th><th>Paciente</th><th>Convenio</th><th>Pago</th><th>Total</th></tr></thead><tbody>@forelse($orders as $order)<tr><td>{{ $order->fecha_orden->format('d/m/Y') }}</td><td><a href="{{ route('orders.show', $order) }}" class="fw-bold">{{ $order->codigo_orden ?? '#'.$order->id }}</a></td><td>{{ $order->patient->nombres }} {{ $order->patient->apellidos }}</td><td>{{ $order->agreement->nombre_institucion }}</td><td>{{ $order->tipo_pago ?? '—' }}</td><td class="text-success fw-bold">S/ {{ number_format($order->total, 2) }}</td></tr>@empty<tr><td colspan="6" class="text-center py-4">Sin órdenes en el rango.</td></tr>@endforelse</tbody></table></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
