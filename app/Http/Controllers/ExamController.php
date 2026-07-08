<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    private const CONTRASTES = ['Con contraste', 'Sin contraste', 'Ambos'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $exams = Exam::with('reagents')->withCount(['prices', 'orderExams'])
            ->when($search !== '', fn ($q) => $q->where('nombre_examen', 'like', "%{$search}%"))
            ->orderBy('nombre_examen')->paginate(10)->withQueryString();
        $reagents = Reagent::where('activo', true)->orderBy('nombre')->get();
        $contrastes = self::CONTRASTES;

        return view('exams.index', compact('exams', 'reagents', 'contrastes', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $reagents = $data['reagents'] ?? [];
        unset($data['reagents']);
        $exam = Exam::create($data);
        $this->syncReagents($exam, $reagents);

        return redirect()->route('exams.index')->with('success', 'Examen creado correctamente.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $this->validatedData($request);
        $reagents = $data['reagents'] ?? [];
        unset($data['reagents']);
        $exam->update($data);
        $this->syncReagents($exam, $reagents);

        return redirect()->route('exams.index')->with('success', 'Examen actualizado correctamente.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        if ($exam->orderExams()->exists()) {
            return back()->with('error', 'No se puede eliminar: está usado en órdenes.');
        }

        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Examen eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'nombre_examen' => ['required', 'string', 'max:255'],
            'tipo_contraste' => ['required', Rule::in(self::CONTRASTES)],
            'activo' => ['nullable', 'boolean'],
            'reagents' => ['nullable', 'array'],
            'reagents.*.reagent_id' => ['nullable', 'exists:reagents,id'],
            'reagents.*.nombre' => ['nullable', 'string', 'max:255'],
            'reagents.*.cantidad_estimada' => ['nullable', 'numeric', 'min:0.01'],
        ]);
        $data['activo'] = $request->boolean('activo');

        return $data;
    }

    private function syncReagents(Exam $exam, array $rows): void
    {
        $sync = [];

        foreach ($rows as $row) {
            if (empty($row['cantidad_estimada'])) {
                continue;
            }

            $reagentId = $row['reagent_id'] ?? null;
            $name = trim((string) ($row['nombre'] ?? ''));

            if (empty($reagentId) && $name !== '') {
                $reagentId = Reagent::firstOrCreate(
                    ['nombre' => $name],
                    ['stock_actual' => 0, 'unidad' => 'unidad', 'stock_minimo' => 0, 'activo' => true]
                )->id;
            }

            if (! empty($reagentId)) {
                $sync[$reagentId] = ['cantidad_estimada' => $row['cantidad_estimada']];
            }
        }

        $exam->reagents()->sync($sync);
    }
}
