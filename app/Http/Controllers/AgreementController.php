<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgreementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $agreements = Agreement::query()->withCount(['orders', 'prices'])
            ->when($search !== '', fn ($q) => $q->where('nombre_institucion', 'like', "%{$search}%"))
            ->orderBy('nombre_institucion')->paginate(10)->withQueryString();
        return view('agreements.index', compact('agreements', 'search'));
    }
    public function store(Request $request): RedirectResponse
    {
        Agreement::create($this->validatedData($request));
        return redirect()->route('agreements.index')->with('success', 'Convenio creado correctamente.');
    }
    public function update(Request $request, Agreement $agreement): RedirectResponse
    {
        $agreement->update($this->validatedData($request, $agreement));
        return redirect()->route('agreements.index')->with('success', 'Convenio actualizado correctamente.');
    }
    public function destroy(Agreement $agreement): RedirectResponse
    {
        if ($agreement->orders()->exists()) return back()->with('error', 'No se puede eliminar: tiene órdenes asociadas.');
        $agreement->delete();
        return redirect()->route('agreements.index')->with('success', 'Convenio eliminado correctamente.');
    }
    private function validatedData(Request $request, ?Agreement $agreement = null): array
    {
        $data = $request->validate([
            'nombre_institucion' => ['required','string','max:255', Rule::unique('agreements')->ignore($agreement?->id)],
            'activo' => ['nullable','boolean'],
        ]);
        $data['activo'] = $request->boolean('activo');
        return $data;
    }
}
