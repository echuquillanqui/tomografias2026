@extends('layouts.app')
@section('content')
<div class="container"><section class="clinic-page-hero mb-4"><div class="d-flex justify-content-between"><div><div class="clinic-eyebrow mb-2">Servicios</div><h1 class="display-6 fw-bold">Exámenes</h1><p class="mb-0 opacity-75">Define estudios y consumo estimado de reactivos.</p></div><button class="btn btn-clinic-primary" data-bs-toggle="modal" data-bs-target="#create">+ Nuevo examen</button></div></section><div class="card clinic-card">@include('catalogs.partials.reactive-search', ['placeholder' => 'Buscar por nombre de examen...'])<div class="card-body p-0"><table class="table table-clinic mb-0"><thead><tr><th>Examen</th><th>Contraste</th><th>Reactivos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>@forelse($exams as $e)<tr><td class="fw-bold">{{ $e->nombre_examen }}</td><td>{{ $e->tipo_contraste }}</td><td>@foreach($e->reagents as $rg)<span class="badge badge-role">{{ $rg->nombre }}: {{ $rg->pivot->cantidad_estimada }} ({{ $rg->pivot->tipo_contraste }})</span>@endforeach</td><td><span class="badge {{ $e->activo?'badge-active':'badge-inactive' }}">{{ $e->activo?'Activo':'Inactivo' }}</span></td><td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit{{ $e->id }}">Editar</button><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#del{{ $e->id }}">Eliminar</button></td></tr>@empty<tr><td colspan="5" class="text-center py-5">Sin exámenes.</td></tr>@endforelse</tbody></table></div><div class="card-footer bg-white">{{ $exams->links() }}</div></div></div>
@include('exams.modal',['id'=>'create','action'=>route('exams.store'),'method'=>'POST','e'=>null]) @foreach($exams as $e) @include('exams.modal',['id'=>'edit'.$e->id,'action'=>route('exams.update',$e),'method'=>'PUT','e'=>$e]) @include('shared.delete',['id'=>'del'.$e->id,'action'=>route('exams.destroy',$e),'name'=>$e->nombre_examen]) @endforeach
@endsection
@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('examConsumables', (initialRows, initialContrast) => ({
                    examContrast: initialContrast,
                    rows: initialRows,
                    nextKey: 0,
                    init() {
                        Object.keys(this.rows).forEach(type => {
                            this.rows[type] = this.rows[type].map(row => ({ ...row, key: ++this.nextKey }));
                        });
                    },
                    addRow(type) {
                        this.rows[type].push({ key: ++this.nextKey, reagent_id: '', nombre: '', cantidad_estimada: '' });
                    },
                    removeRow(type, key) {
                        this.rows[type] = this.rows[type].filter(row => row.key !== key);
                    },
                    field(row, name) {
                        return `reagents[${row.key}][${name}]`;
                    },
                    payload() {
                        return JSON.stringify(Object.fromEntries(Object.entries(this.rows).map(([tipo_contraste, rows]) => [
                            tipo_contraste,
                            rows.map(({ reagent_id, nombre, cantidad_estimada }) => ({
                                reagent_id,
                                nombre,
                                cantidad_estimada,
                            })),
                        ])));
                    },
                }));
            });
        </script>
    @endpush
@endonce
