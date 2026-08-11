<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $data = $this->validatedData($request, $exam);
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

    private function validatedData(Request $request, ?Exam $exam = null): array
    {
        $payload = $request->input('reagents_payload');
        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (! array_is_list($decoded)) {
                    $decoded = collect($decoded)->flatMap(fn ($rows, $contrast) => collect($rows)
                        ->map(fn ($row) => array_merge($row, ['tipo_contraste' => $contrast])))
                        ->values()
                        ->all();
                }

                // Alpine owns the rows displayed in the three blocks. Taking its
                // snapshot avoids losing disabled or dynamic controls. The
                // object keys are the authoritative contrast, so rows from one
                // visual block cannot be saved under another contrast.
                $request->merge(['reagents' => $decoded]);
            }
        }

        $request->merge(['nombre_examen' => trim((string) $request->input('nombre_examen'))]);

        $data = $request->validate([
            'nombre_examen' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exams', 'nombre_examen')->ignore($exam),
            ],
            'tipo_contraste' => ['required', Rule::in(self::CONTRASTES)],
            'activo' => ['nullable', 'boolean'],
            'reagents_payload' => ['nullable', 'json'],
            'reagents' => ['nullable', 'array'],
            'reagents.*.reagent_id' => ['nullable', 'exists:reagents,id'],
            'reagents.*.nombre' => ['nullable', 'string', 'max:255'],
            'reagents.*.cantidad_estimada' => ['nullable', 'numeric', 'min:0.01'],
            'reagents.*.tipo_contraste' => ['nullable', Rule::in(self::CONTRASTES)],
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
                $contrast = $exam->tipo_contraste === 'Ambos'
                    ? ($row['tipo_contraste'] ?? 'Ambos')
                    : $exam->tipo_contraste;
                $sync[$reagentId.'|'.$contrast] = [
                    'exam_id' => $exam->id,
                    'reagent_id' => $reagentId,
                    'tipo_contraste' => $contrast,
                    'cantidad_estimada' => $row['cantidad_estimada'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($exam, $sync) {
            DB::table('exam_reagent')->where('exam_id', $exam->id)->delete();
            if ($sync !== []) {
                DB::table('exam_reagent')->insert(array_values($sync));
            }
        });
    }
}
