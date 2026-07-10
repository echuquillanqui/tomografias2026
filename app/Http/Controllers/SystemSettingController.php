<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function index(): View
    {
        return view('system-settings.index', ['setting' => SystemSetting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = SystemSetting::current();
        $data = $request->validate([
            'ruc' => ['nullable', 'string', 'max:20'],
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('system', 'public');
        }
        unset($data['logo']);

        $setting->update($data);

        return redirect()->route('system-settings.index')->with('success', 'Configuración actualizada correctamente.');
    }
}
