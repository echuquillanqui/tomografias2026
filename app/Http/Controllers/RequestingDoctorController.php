<?php

namespace App\Http\Controllers;

use App\Models\RequestingDoctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RequestingDoctorController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $requestingDoctors = RequestingDoctor::withCount('orders')
            ->when($search !== '', fn ($query) => $query->where('nombre', 'like', "%{$search}%"))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('requesting-doctors.index', compact('requestingDoctors', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        RequestingDoctor::create($this->validatedData($request));

        return redirect()->route('requesting-doctors.index')->with('success', 'Médico solicitante creado correctamente.');
    }

    public function update(Request $request, RequestingDoctor $requestingDoctor): RedirectResponse
    {
        $requestingDoctor->update($this->validatedData($request, $requestingDoctor));

        return redirect()->route('requesting-doctors.index')->with('success', 'Médico solicitante actualizado correctamente.');
    }

    public function destroy(RequestingDoctor $requestingDoctor): RedirectResponse
    {
        if ($requestingDoctor->orders()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene órdenes registradas.');
        }

        $requestingDoctor->delete();

        return redirect()->route('requesting-doctors.index')->with('success', 'Médico solicitante eliminado correctamente.');
    }

    private function validatedData(Request $request, ?RequestingDoctor $requestingDoctor = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('requesting_doctors', 'nombre')->ignore($requestingDoctor?->id)],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['nombre'] = trim(preg_replace('/\s+/', ' ', $data['nombre']));
        $data['activo'] = $request->boolean('activo');

        return $data;
    }
}
