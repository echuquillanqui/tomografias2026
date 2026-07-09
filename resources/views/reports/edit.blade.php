@extends('layouts.app')

@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4">
        <div class="d-flex justify-content-between gap-3">
            <div>
                <div class="clinic-eyebrow mb-2">Plantilla de informe</div>
                <h1 class="display-6 fw-bold">{{ $order->codigo_orden ?? 'Orden #'.$order->id }}</h1>
                <p class="mb-0 opacity-75">{{ $order->patient->nombres }} {{ $order->patient->apellidos }} · {{ $order->fecha_orden->format('d/m/Y') }}</p>
            </div>
            <div>
                <a class="btn btn-light" href="{{ route('reports.pdf', $order) }}" target="_blank">Ver PDF</a>
                <a class="btn btn-outline-light" href="{{ route('reports.index') }}">Volver</a>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('reports.update', $order) }}" class="card clinic-card p-4">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold">TÍTULO</label>
                <input name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $order->report->titulo) }}" required>
                @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">MÉDICO QUE FIRMARÁ</label>
                <select name="medico_firmante_id" class="form-select @error('medico_firmante_id') is-invalid @enderror">
                    <option value="">Sin médico / firma en blanco</option>
                    @foreach($medicosInformantes as $medico)
                        <option value="{{ $medico->id }}" @selected(old('medico_firmante_id', $order->report->medico_firmante_id ?? $order->medico_informe_id) == $medico->id)>
                            {{ $medico->nombre_completo }}{{ $medico->firma_path ? ' · con firma' : ' · sin firma' }}
                        </option>
                    @endforeach
                </select>
                @error('medico_firmante_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label small fw-bold">CONTENIDO DEL INFORME</label>
                <textarea name="contenido" rows="24" class="form-control font-monospace @error('contenido') is-invalid @enderror" required>{{ old('contenido', $order->report->contenido) }}</textarea>
                @error('contenido') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">Cancelar</a>
            <button class="btn btn-clinic-primary">Guardar informe</button>
        </div>
    </form>
</div>
@endsection
