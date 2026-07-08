<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Patient::create($this->validatedData($request));

        return redirect()->route('patients.index')->with('success', 'Paciente creado correctamente.');
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $patient->update($this->validatedData($request, $patient));

        return redirect()->route('patients.index')->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        if ($patient->orders()->exists()) {
            return redirect()->route('patients.index')->with('error', 'No se puede eliminar el paciente porque tiene órdenes asociadas.');
        }

        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Paciente eliminado correctamente.');
    }

    private function validatedData(Request $request, ?Patient $patient = null): array
    {
        return $request->validate([
            'dni' => ['required', 'string', 'max:20', Rule::unique('patients', 'dni')->ignore($patient?->id)],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'edad' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);
    }
}
