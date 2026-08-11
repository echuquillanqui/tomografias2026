<?php

namespace App\Http\Controllers;

use App\Models\GlobalContrastConsumable;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GlobalContrastConsumableController extends Controller
{
    private const CONTRASTES = ['Con contraste', 'Sin contraste'];

    public function index(): View
    {
        $reagents = Reagent::where('activo', true)->orderBy('nombre')->get();
        $configurations = GlobalContrastConsumable::with('reagent')->get()
            ->groupBy('tipo_contraste');

        return view('global-contrast-consumables.index', compact('reagents', 'configurations'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'configurations' => ['nullable', 'array'],
            'configurations.*' => ['nullable', 'array'],
            'configurations.*.*.reagent_id' => ['required', 'integer', 'exists:reagents,id'],
            'configurations.*.*.cantidad_estimada' => ['required', 'numeric', 'min:0.01'],
        ]);

        $rows = collect($data['configurations'] ?? [])->flatMap(function ($items, $contrast) {
            if (! in_array($contrast, self::CONTRASTES, true)) {
                return [];
            }

            return collect($items)->map(fn ($item) => $item + ['tipo_contraste' => $contrast]);
        })->unique(fn ($item) => $item['tipo_contraste'].'|'.$item['reagent_id']);

        DB::transaction(function () use ($rows): void {
            GlobalContrastConsumable::query()->delete();
            GlobalContrastConsumable::insert($rows->map(fn ($row) => $row + [
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all());
        });

        return redirect()->route('global-contrast-consumables.index')
            ->with('success', 'La asignación global de insumos se actualizó correctamente.');
    }
}
