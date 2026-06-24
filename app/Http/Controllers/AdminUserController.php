<?php

namespace App\Http\Controllers;

use App\Models\TaskRecord;
use App\Models\User;
use App\Support\AdminPasswordVerifier;
use App\Support\CurrentUserPasswordVerifier;
use App\Support\TextFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private const DEFAULT_AREAS = [
        'AREA PLANIFICACION',
        'AREA PROYECTOS',
        'AREA SDIS-VE',
        'AREA CAPACITACION Y ACREDITACION PROFESIONAL',
        'AREA COMUNICACION SOCIAL',
        'AREA SISTEMAS',
        'OTRA AREA',
    ];

    public function __construct(
        private readonly AdminPasswordVerifier $adminPasswordVerifier,
        private readonly CurrentUserPasswordVerifier $currentUserPasswordVerifier
    ) {
    }

    public function index(Request $request)
    {
        $query = User::with(['latestAssignedTask' => fn ($q) => $q->withCount('taskFiles')])
            ->where('active', true)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%")
                    ->orWhere('cargo', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(12)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $this->normalizeUserData($this->validatedUserData($request));
        $data['active'] = true;

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function baseAdminSetup()
    {
        $user = User::query()->whereKey(session('auth_user_id'))->firstOrFail();

        if (! $this->needsBaseAdminSetup($user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.users.base_setup', compact('user'));
    }

    public function updateBaseAdminSetup(Request $request)
    {
        $user = User::query()->whereKey(session('auth_user_id'))->firstOrFail();

        if (! $this->needsBaseAdminSetup($user)) {
            return redirect()->route('admin.dashboard');
        }

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'password' => $this->passwordRules(true),
            'password_confirmation' => ['required', 'same:password'],
        ], $this->passwordMessages());

        $user->forceFill([
            'username' => $data['username'],
            'password' => $data['password'],
            'is_base_admin' => false,
            'base_setup_completed_at' => now(),
        ])->save();

        $sessionUser = session('auth_user', []);
        $sessionUser['username'] = $user->username;
        session(['auth_user' => $sessionUser]);

        return redirect()->route('admin.dashboard')->with('success', 'Cuenta administradora configurada correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->normalizeUserData($this->validatedUserData($request, $user));

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    private function normalizeUserData(array $data): array
    {
        $data['name'] = TextFormatter::title($data['name'] ?? null);
        $data['ci'] = TextFormatter::title($data['ci'] ?? null);
        $data['cargo'] = TextFormatter::title($data['cargo'] ?? null);
        $data['phone'] = TextFormatter::title($data['phone'] ?? null);
        if (($data['area'] ?? null) === 'OTRA AREA') {
            $data['area'] = mb_strtoupper(trim((string) ($data['area_other'] ?? '')), 'UTF-8');
        }
        unset($data['area_other']);

        return $data;
    }

    private function validatedUserData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ci' => ['nullable', 'string', 'max:30'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'admission_date' => ['nullable', 'date'],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user?->id),
            ],
            'password' => $this->passwordRules($user === null),
            'role' => ['required', 'in:admin,user,tecnico'],
            'area' => ['required', Rule::in(self::DEFAULT_AREAS)],
            'area_other' => ['nullable', 'required_if:area,OTRA AREA', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
        ], $this->passwordMessages());
    }

    public function show(User $user)
    {
        $user->load('activePasswordRecoveryToken');
        $pendingAdminPermissionRequest = $user->role === 'tecnico'
            ? $user->adminPermissionRequests()->pending()->latest('requested_at')->first()
            : null;
        $activeTemporaryAdminPermission = $user->role === 'tecnico'
            ? $user->temporaryAdminPermissions()->active()->latest('ends_at')->first()
            : null;
        $tasks = TaskRecord::withCount('taskFiles')
            ->where('technician_id', $user->id)
            ->latest()
            ->paginate(10);
        $counts = [
            'pendientes' => TaskRecord::where('technician_id', $user->id)->where('state', 'pendiente')->count(),
            'cumplidos' => TaskRecord::where('technician_id', $user->id)->where('state', 'cumplido')->count(),
            'no_cumplidos' => TaskRecord::where('technician_id', $user->id)->where('state', 'no cumplido')->count(),
            'retrasos' => TaskRecord::where('technician_id', $user->id)->where('state', 'retraso')->count(),
        ];
        $viewerRole = session('auth_user.role');
        $passwordRecoveryRoute = $viewerRole === 'tecnico'
            ? route('tecnico.users.password.recover', $user)
            : route('admin.users.password.recover', $user);
        $hasActiveRecoveryToken = (bool) $user->activePasswordRecoveryToken;
        $canRequestPasswordRecovery = ! $user->trashed()
            && in_array($viewerRole, ['admin', 'tecnico'], true)
            && (int) $user->id !== (int) session('auth_user_id');
        $canGrantTemporaryAdminPermission = request()->routeIs('admin.*')
            && ! $user->trashed()
            && $user->role === 'tecnico';
        $autoOpenAdminPermissionModal = $canGrantTemporaryAdminPermission
            && request()->boolean('grant_admin');
        $adminPermissionRequestId = request()->integer('permission_request') ?: $pendingAdminPermissionRequest?->id;

        return view('admin.users.show', compact(
            'user',
            'tasks',
            'counts',
            'viewerRole',
            'passwordRecoveryRoute',
            'hasActiveRecoveryToken',
            'canRequestPasswordRecovery',
            'pendingAdminPermissionRequest',
            'activeTemporaryAdminPermission',
            'canGrantTemporaryAdminPermission',
            'autoOpenAdminPermissionModal',
            'adminPermissionRequestId'
        ));
    }

    public function recoverPassword(Request $request, User $user)
    {
        $currentUser = $this->currentUserPasswordVerifier->verify($request);
        $viewerRole = session('auth_user.role', $currentUser->role);

        abort_unless(in_array($viewerRole, ['admin', 'tecnico'], true), 403);
        abort_if($user->trashed(), 404);

        if ((int) $user->id === (int) $currentUser->id) {
            return back()->with('password_denied', 'No puede recuperar la contraseña del usuario que tiene la sesión actual.');
        }

        $activeToken = $user->passwordRecoveryTokens()->active()->first();

        if ($viewerRole === 'tecnico') {
            if ($user->role === 'admin') {
                return back()->with('password_denied', 'El rol técnico no puede recuperar contraseñas de administradores.');
            }

            if (! $activeToken) {
                return back()->with('password_denied', 'Primero se debe obtener el token de recuperación desde el apartado de Olvidó su Contraseña.');
            }
        }

        $temporaryPassword = 'SEDES-'.Str::upper(Str::random(8));
        $user->forceFill(['password' => $temporaryPassword])->save();

        if ($viewerRole === 'tecnico' && $activeToken) {
            $activeToken->update(['used_at' => now()]);
        }

        return back()
            ->with('temporary_password_user_id', $user->id)
            ->with('temporary_password', $temporaryPassword)
            ->with('success', 'Contraseña temporal generada correctamente. Entréguela solo al usuario verificado.');
    }

    public function destroy(Request $request, User $user)
    {
        if ((int) $user->id === (int) session('auth_user_id')) {
            return back()->with('error', 'No puede eliminar el usuario que tiene la sesión actual.');
        }

        if ($user->role === 'admin' && ! $this->anotherActiveAdminExists($user)) {
            return back()->with('error', 'No se puede eliminar esta cuenta porque el sistema debe conservar al menos una cuenta administradora activa.');
        }

        $this->adminPasswordVerifier->verify($request);

        $user->update(['active' => false]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario retirado de la lista activa. Queda guardado en historial.');
    }

    public function history(Request $request)
    {
        $query = User::withTrashed()
            ->with(['latestAssignedTask' => fn ($q) => $q->withCount('taskFiles')])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.history', compact('users'));
    }

    private function passwordRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:6',
            'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
        ];
    }

    private function passwordMessages(): array
    {
        return [
            'password.min' => 'La contraseña debe tener mínimo 6 caracteres.',
            'password.regex' => 'La contraseña debe contener letras y números.',
            'password_confirmation.same' => 'La confirmación de contraseña no coincide.',
        ];
    }

    private function needsBaseAdminSetup(User $user): bool
    {
        return $user->is_base_admin && ! $user->base_setup_completed_at;
    }

    private function anotherActiveAdminExists(User $user): bool
    {
        return User::query()
            ->where('role', 'admin')
            ->where('active', true)
            ->whereNull('deleted_at')
            ->where('id', '<>', $user->id)
            ->exists();
    }
}
