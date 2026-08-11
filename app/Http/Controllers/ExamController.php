<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $exams = Exam::withCount(['prices', 'orderExams'])
            ->when($search !== '', fn ($q) => $q->where('nombre_examen', 'like', "%{$search}%"))
            ->orderBy('nombre_examen')->paginate(10)->withQueryString();
        return view('exams.index', compact('exams', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        Exam::create($data);

        return redirect()->route('exams.index')->with('success', 'Examen creado correctamente.');
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $data = $this->validatedData($request, $exam);
        $exam->update($data);

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

    private function validatedData(Request $request, ?Exam $exam = null): array
    {
        $request->merge(['nombre_examen' => trim((string) $request->input('nombre_examen'))]);

        $data = $request->validate([
            'nombre_examen' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exams', 'nombre_examen')->ignore($exam),
            ],
            'activo' => ['nullable', 'boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');
        // Kept as an internal compatibility value for existing schema and pricing.
        $data['tipo_contraste'] = 'Ambos';

        return $data;
    }

}
