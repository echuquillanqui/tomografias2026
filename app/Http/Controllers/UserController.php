<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const ROLES = ['Admin', 'Recepción', 'Médico', 'Almacén'];

    private const TIPOS_MEDICO = ['De Informe'];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('rol', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre_completo')
            ->orderBy('username')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'tiposMedico' => self::TIPOS_MEDICO,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['password'] = Hash::make($data['password']);
        $data['activo'] = $request->boolean('activo');
        unset($data['firma']);
        $data = $this->normalizeMedicalFields($data);
        if ($request->hasFile('firma')) {
            $data['firma_path'] = $request->file('firma')->store('firmas', 'public');
        }

        $user = User::create($data);
        $this->syncRole($user, $data['rol']);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['activo'] = $request->boolean('activo');
        unset($data['firma']);
        $data = $this->normalizeMedicalFields($data);
        if ($request->hasFile('firma')) {
            if ($user->firma_path) {
                Storage::disk('public')->delete($user->firma_path);
            }
            $data['firma_path'] = $request->file('firma')->store('firmas', 'public');
        }

        $user->update($data);
        $this->syncRole($user, $data['rol']);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $userId = $user?->id;
        $passwordRules = $user ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'];

        return $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'nombre_completo' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $passwordRules,
            'rol' => ['required', Rule::in(self::ROLES)],
            'tipo_medico' => ['nullable', Rule::requiredIf($request->input('rol') === 'Médico'), Rule::in(self::TIPOS_MEDICO)],
            'cmp' => ['nullable', 'string', 'max:50'],
            'rne' => ['nullable', 'string', 'max:50'],
            'comision_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'activo' => ['nullable', 'boolean'],
            'firma' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    private function normalizeMedicalFields(array $data): array
    {
        if (($data['rol'] ?? null) !== 'Médico') {
            $data['tipo_medico'] = null;
            $data['cmp'] = null;
            $data['rne'] = null;
            $data['comision_porcentaje'] = null;
            $data['firma_path'] = null;
        } else {
            $data['tipo_medico'] = 'De Informe';
        }

        return $data;
    }

    private function syncRole(User $user, string $role): void
    {
        Role::findOrCreate($role, 'web');
        $user->syncRoles([$role]);
    }
}
