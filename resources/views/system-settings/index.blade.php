@extends('layouts.app')
@section('content')
<div class="container">
    <section class="clinic-page-hero mb-4"><div class="d-flex justify-content-between align-items-center gap-3"><div><div class="clinic-eyebrow mb-2">Configuración</div><h1 class="display-6 fw-bold mb-2">Datos de la empresa</h1><p class="mb-0 opacity-75">Estos datos y el logo se aplican en los formatos impresos.</p></div></div></section>
    <form class="card clinic-card p-4" method="POST" action="{{ route('system-settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label fw-bold">RUC</label><input name="ruc" class="form-control @error('ruc') is-invalid @enderror" value="{{ old('ruc', $setting->ruc) }}">@error('ruc')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-8"><label class="form-label fw-bold">Razón social</label><input name="razon_social" class="form-control @error('razon_social') is-invalid @enderror" value="{{ old('razon_social', $setting->razon_social) }}" required>@error('razon_social')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label fw-bold">Descripción o giro de la empresa</label><input name="descripcion_empresa" class="form-control @error('descripcion_empresa') is-invalid @enderror" value="{{ old('descripcion_empresa', $setting->descripcion_empresa) }}" placeholder="Ejemplo: Centro de diagnóstico médico">@error('descripcion_empresa')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Se mostrará debajo de la razón social en la nota de venta.</div></div>
            <div class="col-md-8"><label class="form-label fw-bold">Dirección</label><input name="direccion" class="form-control @error('direccion') is-invalid @enderror" value="{{ old('direccion', $setting->direccion) }}">@error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label fw-bold">Teléfono</label><input name="telefono" class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $setting->telefono) }}">@error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><hr><h5 class="fw-bold mb-1">Numeración de notas de venta</h5><p class="text-muted mb-0">La serie se cambia únicamente desde aquí. El número de recibo se asigna a cada nueva atención y avanza automáticamente.</p></div>
            <div class="col-md-4"><label class="form-label fw-bold">Correlativo de nota de venta</label><input name="sale_note_series" class="form-control @error('sale_note_series') is-invalid @enderror" value="{{ old('sale_note_series', $setting->sale_note_series) }}" required>@error('sale_note_series')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Ejemplo: 004.</div></div>
            <div class="col-md-4"><label class="form-label fw-bold">Siguiente número de recibo</label><input name="next_receipt_number" type="number" min="1" step="1" class="form-control @error('next_receipt_number') is-invalid @enderror" value="{{ old('next_receipt_number', $setting->next_receipt_number) }}" required>@error('next_receipt_number')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">El próximo comprobante será {{ $setting->sale_note_series }}-{{ str_pad((string) $setting->next_receipt_number, 3, '0', STR_PAD_LEFT) }}.</div></div>
            <div class="col-md-8"><label class="form-label fw-bold">Logo</label><input name="logo" type="file" accept="image/*" class="form-control @error('logo') is-invalid @enderror">@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Formatos JPG, PNG o similares. Máximo 2 MB.</div></div>
            <div class="col-md-4">@if($setting->logo_path)<img src="{{ asset('storage/'.$setting->logo_path) }}" alt="Logo actual" class="img-fluid rounded border p-2 bg-white">@else<div class="text-muted border rounded p-4 text-center">Sin logo cargado</div>@endif</div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-clinic-primary px-4">Guardar configuración</button></div>
    </form>
</div>
@endsection
