<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Services\UserCreationService;
use Spatie\Permission\Models\Role;

class UsuariosController extends Controller
{
    use \App\Http\Controllers\Concerns\AccionesEnLote;


    private UserCreationService $userService;

    public function __construct()
    {
        $this->userService = new UserCreationService();
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // `with('roles')`: el listado llama getRoleNames() por fila y sin precargar
            // los roles eso era una consulta por usuario.
            $users = User::query()->with('roles')->where('id', '!=', 1);

            if ($request->boolean('solo_ids')) {
                return $this->idsDelFiltro($request, $users, 'users.id');
            }

            return DataTables::of($users)
                    ->addColumn('seleccion', fn($u) =>
                        '<input type="checkbox" class="fila-lote" value="'.$u->id.'" aria-label="Seleccionar usuario">')
                    ->addColumn('roles', function($u) {
                        return $u->getRoleNames()
                                ->map(fn($r) => ucfirst($r))
                                ->join(', ');
                    })
                    ->addColumn('estado', function($u) {
                        return $u->activo
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>';
                    })
                    ->addColumn('action', function ($user) {
                        $editUrl    = route('usuarios.form', $user->id);
                        $toggleUrl  = route('usuarios.toggle-activo', $user->id);
                        $deleteUrl  = route('usuarios.eliminar', $user->id);
                        $csrf       = csrf_token();

                        $toggleIcon  = $user->activo ? 'bi-toggle-on' : 'bi-toggle-off';
                        $toggleClass = $user->activo ? 'btn-outline-warning' : 'btn-outline-success';
                        $toggleTitle = $user->activo ? 'Inactivar' : 'Activar';

                        $html  = '<div class="d-flex justify-content-center align-items-center gap-1">';
                        $html .= '<a href="'.$editUrl.'" class="btn btn-outline-info btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>';

                        $html .= '<form method="POST" action="'.$toggleUrl.'" style="display:inline">';
                        $html .= '<input type="hidden" name="_token" value="'.$csrf.'">';
                        $html .= '<button type="submit" class="btn '.$toggleClass.' btn-sm" title="'.$toggleTitle.'"><i class="bi '.$toggleIcon.'"></i></button>';
                        $html .= '</form>';

                        $html .= '<form method="POST" action="'.$deleteUrl.'" style="display:inline" onsubmit="return confirm(\'¿Eliminar este usuario? Sus datos relacionados (cotizaciones, clientes, etc.) se conservarán.\');">';
                        $html .= '<input type="hidden" name="_token" value="'.$csrf.'">';
                        $html .= '<input type="hidden" name="_method" value="DELETE">';
                        $html .= '<button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>';
                        $html .= '</form>';

                        $html .= '</div>';
                        return $html;
                    })
                ->rawColumns(['seleccion', 'action', 'estado'])
                ->make(true);
        }

        return view('usuarios.usuarios_index');
    }



    public function form(?User $user = null)
    {
        $user  = $user ?? new User();
        $roles = Role::pluck('name','name');

        return view('usuarios.usuarios_form', compact('user','roles'));
    }

    public function guardar(Request $request)
    {
        $user = $request->id ? User::findOrFail($request->id) : null;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                // Ignorar usuarios eliminados (soft-delete): su correo se puede reutilizar.
                // Al crear, si el correo pertenece a un usuario borrado, se restaura y sobrescribe.
                Rule::unique('users')->ignore($user?->id)->whereNull('deleted_at')
            ],
            'password' => $user ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'],
            'role'     => ['required','exists:roles,name'],
            'activo'   => ['nullable', 'boolean'],
        ];

        $messages = [
            'required' => 'Este campo es obligatorio.',
            'email' => 'Debe ser un correo válido.',
            'max' => 'No debe superar los :max caracteres.',
            'unique' => 'Ya existe un usuario con este correo.',
            'min' => 'Debe tener al menos :min caracteres.',
        ];

        $data = $request->validate($rules, $messages);
        $data['activo'] = $request->boolean('activo');

        if ($user) {
            $this->userService->update($user, $data);
        } else {
            $user = $this->userService->create($data);
        }
        $user->syncRoles($data['role']);
        return redirect()->route('usuarios')->with('success', 'Usuario guardado correctamente.');
    }

    public function toggleActivo(User $user)
    {
        if ($user->id === 1 || $user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar el estado de este usuario.');
        }

        $user->update(['activo' => ! $user->activo]);

        $msg = $user->activo ? 'Usuario activado.' : 'Usuario inactivado.';
        return back()->with('success', $msg);
    }

    public function eliminar(User $user)
    {
        if ($user->id === 1 || $user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar este usuario.');
        }

        $user->delete();
        return redirect()->route('usuarios')->with('success', 'Usuario eliminado.');
    }

    /**
     * Activar, desactivar o eliminar varios usuarios de una vez.
     *
     * Se respetan las mismas dos protecciones del borrado individual: el usuario
     * 1 (que ademas ni siquiera aparece en el listado) y uno mismo, para que
     * nadie se deje fuera de su propia plataforma sin darse cuenta.
     */
    public function accionEnLote(Request $request)
    {
        [$accion, $ids] = $this->datosDelLote($request, ['activar', 'desactivar', 'eliminar']);

        $protegidos = array_values(array_intersect($ids, [1, (int) auth()->id()]));
        $ids        = array_values(array_diff($ids, $protegidos));
        $omitidos   = $protegidos ? ['son tu propio usuario o el administrador principal' => count($protegidos)] : [];

        if (! $ids) {
            return $this->respuestaDelLote($accion, 0, $omitidos);
        }

        if ($accion === 'eliminar') {
            $aplicados = 0;
            foreach (User::whereIn('id', $ids)->get() as $usuario) {
                $usuario->delete();
                $aplicados++;
            }

            return $this->respuestaDelLote($accion, $aplicados, $omitidos);
        }

        $aplicados = User::whereIn('id', $ids)->update(['activo' => $accion === 'activar']);

        return $this->respuestaDelLote($accion, $aplicados, $omitidos);
    }
}
