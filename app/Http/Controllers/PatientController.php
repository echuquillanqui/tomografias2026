<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $patient = Patient::create($this->validatedData($request));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Paciente creado correctamente.',
                'patient' => $this->patientPayload($patient),
            ], 201);
        }

        return redirect()->route('patients.index')->with('success', 'Paciente creado correctamente.');
    }

    public function update(Request $request, Patient $patient): RedirectResponse|JsonResponse
    {
        $patient->update($this->validatedData($request, $patient));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Paciente actualizado correctamente.',
                'patient' => $this->patientPayload($patient),
            ]);
        }

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


    public function reniec(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero' => ['required', 'digits:8'],
        ]);

        if (blank(config('services.decolecta.token'))) {
            return response()->json(['message' => 'La consulta RENIEC no está configurada.'], 503);
        }

        try {
            $response = Http::acceptJson()
            ->withToken(config('services.decolecta.token'))
            ->timeout(10)
            ->get('https://api.decolecta.com/v1/reniec/dni', [
                'numero' => $data['numero'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'No se pudo conectar con el servicio RENIEC.'], 503);
        }

        if ($response->failed()) {
            if ($response->notFound()) {
                return response()->json([
                    'message' => 'RENIEC no devolvió datos para este DNI. En menores de edad el padrón consultado por Decolecta puede no tener información; registre sus nombres y apellidos manualmente.',
                    'manual_entry' => true,
                ], 404);
            }

            return response()->json([
                'message' => 'El servicio RENIEC no pudo procesar la consulta en este momento.',
            ], 503);
        }

        return response()->json($response->json());
    }

    private function validatedData(Request $request, ?Patient $patient = null): array
    {
        $data = $request->validate([
            'dni' => ['required', 'string', 'max:20', Rule::unique('patients', 'dni')->ignore($patient?->id)],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'sexo' => ['required', Rule::in(['MASCULINO', 'FEMENINO'])],
        ]);

        $data['edad'] = filled($data['fecha_nacimiento'] ?? null)
            ? Carbon::parse($data['fecha_nacimiento'])->age
            : null;

        return $data;
    }

    private function patientPayload(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'dni' => $patient->dni,
            'nombres' => $patient->nombres,
            'apellidos' => $patient->apellidos,
            'telefono' => $patient->telefono,
            'fecha_nacimiento' => optional($patient->fecha_nacimiento)->format('Y-m-d'),
            'edad' => $patient->edad,
            'sexo' => $patient->sexo,
            'label' => $patient->dni.' - '.$patient->nombres.' '.$patient->apellidos,
        ];
    }
}
