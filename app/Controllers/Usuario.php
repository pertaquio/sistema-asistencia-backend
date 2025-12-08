<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\EstadoModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Usuario extends ResourceController
{
    protected $modelName = 'App\Models\UsuarioModel';
    protected $format = 'json';
    protected $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    private function validatePasswordRequirements(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < 8) {
            $errors[] = "La contraseña debe tener al menos 8 caracteres";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Falta una letra mayúscula";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Falta una letra minúscula";
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Falta un número";
        }
        if (!preg_match('/[\W_]/', $password)) {
            $errors[] = "Falta un carácter especial";
        }

        return $errors;
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $search = $this->request->getVar('search') ?? '';

        $builder = $this->usuarioModel
            ->select('usuarios.*, roles.nombre as rol_nombre, estados.nombre as estado_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->join('estados', 'estados.id = usuarios.estado_id');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('usuarios.nombre_usuario', $search)
                ->orLike('usuarios.email', $search)
                ->orLike('usuarios.nombre_completo', $search)
                ->groupEnd();
        }

        $usuarios = $builder->paginate($perPage, 'default', $page);
        $pager = $this->usuarioModel->pager;

        foreach ($usuarios as &$usuario) {
            unset($usuario['contrasena_hash']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $usuarios,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'per_page' => $pager->getPerPage(),
                'total' => $pager->getTotal(),
                'total_pages' => $pager->getPageCount()
            ]
        ], ResponseInterface::HTTP_OK);
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioModel->getUsuarioConRol($id);

        if (!$usuario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        unset($usuario['contrasena_hash']);

        return $this->respond([
            'status' => 'success',
            'data' => $usuario
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'nombre_usuario' => [
                'label' => 'Nombre de usuario',
                'rules' => 'required|valid_email|is_unique[usuarios.nombre_usuario]',
                'errors' => [
                    'required' => 'El nombre de usuario es requerido',
                    'valid_email' => 'El nombre de usuario debe ser un correo electrónico válido',
                    'is_unique' => 'Este nombre de usuario ya está registrado'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'permit_empty|valid_email|is_unique[usuarios.email]',
                'errors' => [
                    'valid_email' => 'El email debe ser válido',
                    'is_unique' => 'Este email ya está registrado'
                ]
            ],
            'contrasena' => [
                'label' => 'Contraseña',
                'rules' => 'required|min_length[8]|max_length[12]',
                'errors' => [
                    'required' => 'La contraseña es requerida',
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres',
                    'max_length' => 'La contraseña no puede exceder 12 caracteres'
                ]
            ],
            'nombre_completo' => [
                'label' => 'Nombre completo',
                'rules' => 'required|min_length[3]|max_length[150]',
                'errors' => [
                    'required' => 'El nombre completo es requerido',
                    'min_length' => 'El nombre completo debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre completo no puede exceder 150 caracteres'
                ]
            ],
            'rol_id' => [
                'label' => 'Rol',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El rol es requerido',
                    'is_natural_no_zero' => 'El rol debe ser un número válido'
                ]
            ],
            'estado_id' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|is_natural_no_zero|in_list[1,2,3]',
                'errors' => [
                    'is_natural_no_zero' => 'El estado debe ser un número válido',
                    'in_list' => 'El estado debe ser 1 (Activo), 2 (Inactivo) o 3 (Suspendido)'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $password = $this->request->getVar('contrasena') ?? '';
        $pwdErrors = $this->validatePasswordRequirements($password);
        if (!empty($pwdErrors)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Contraseña no cumple requisitos',
                'errors' => ['contrasena' => $pwdErrors]
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $data = [
            'nombre_usuario' => $this->request->getVar('nombre_usuario'),
            'email' => $this->request->getVar('email') ?? $this->request->getVar('nombre_usuario'),
            'contrasena_hash' => password_hash($password, PASSWORD_DEFAULT),
            'nombre_completo' => $this->request->getVar('nombre_completo'),
            'rol_id' => $this->request->getVar('rol_id'),
            'estado_id' => $this->request->getVar('estado_id') ?? EstadoModel::ACTIVO
        ];

        $usuarioId = $this->usuarioModel->insert($data);

        if (!$usuarioId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el usuario',
                'errors' => $this->usuarioModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $usuario = $this->usuarioModel->getUsuarioConRol($usuarioId);
        unset($usuario['contrasena_hash']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Usuario creado exitosamente',
            'data' => $usuario
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'nombre_usuario' => [
                'label' => 'Nombre de usuario',
                'rules' => "permit_empty|valid_email|is_unique[usuarios.nombre_usuario,id,{$id}]",
                'errors' => [
                    'valid_email' => 'El nombre de usuario debe ser un correo electrónico válido',
                    'is_unique' => 'Este nombre de usuario ya está registrado'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => "permit_empty|valid_email|is_unique[usuarios.email,id,{$id}]",
                'errors' => [
                    'valid_email' => 'El email debe ser válido',
                    'is_unique' => 'Este email ya está registrado'
                ]
            ],
            'contrasena' => [
                'label' => 'Contraseña',
                'rules' => 'permit_empty|min_length[8]|max_length[12]',
                'errors' => [
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres',
                    'max_length' => 'La contraseña no puede exceder 12 caracteres'
                ]
            ],
            'nombre_completo' => [
                'label' => 'Nombre completo',
                'rules' => 'permit_empty|min_length[3]|max_length[150]',
                'errors' => [
                    'min_length' => 'El nombre completo debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre completo no puede exceder 150 caracteres'
                ]
            ],
            'rol_id' => [
                'label' => 'Rol',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El rol debe ser un número válido'
                ]
            ],
            'estado_id' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|is_natural_no_zero|in_list[1,2,3]',
                'errors' => [
                    'is_natural_no_zero' => 'El estado debe ser un número válido',
                    'in_list' => 'El estado debe ser 1 (Activo), 2 (Inactivo) o 3 (Suspendido)'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $data = [];

        if ($this->request->getVar('nombre_usuario')) {
            $data['nombre_usuario'] = $this->request->getVar('nombre_usuario');
        }

        if ($this->request->getVar('email')) {
            $data['email'] = $this->request->getVar('email');
        }

        if ($this->request->getVar('contrasena')) {
            $password = $this->request->getVar('contrasena');
            $pwdErrors = $this->validatePasswordRequirements($password);
            if (!empty($pwdErrors)) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Contraseña no cumple requisitos',
                    'errors' => ['contrasena' => $pwdErrors]
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $data['contrasena_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($this->request->getVar('nombre_completo')) {
            $data['nombre_completo'] = $this->request->getVar('nombre_completo');
        }

        if ($this->request->getVar('rol_id')) {
            $data['rol_id'] = $this->request->getVar('rol_id');
        }

        if ($this->request->getVar('estado_id') !== null) {
            $data['estado_id'] = $this->request->getVar('estado_id');
        }

        if (empty($data)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No hay datos para actualizar'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->usuarioModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el usuario',
                'errors' => $this->usuarioModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $usuarioActualizado = $this->usuarioModel->getUsuarioConRol($id);
        unset($usuarioActualizado['contrasena_hash']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente',
            'data' => $usuarioActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->usuarioModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el usuario'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Usuario eliminado exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function cambiarEstado($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'estado_id' => [
                'label' => 'Estado',
                'rules' => 'required|is_natural_no_zero|in_list[1,2,3]',
                'errors' => [
                    'required' => 'El estado es requerido',
                    'is_natural_no_zero' => 'El estado debe ser un número válido',
                    'in_list' => 'El estado debe ser 1 (Activo), 2 (Inactivo) o 3 (Suspendido)'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estadoId = $this->request->getVar('estado_id');

        $updated = $this->usuarioModel->update($id, ['estado_id' => $estadoId]);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cambiar el estado del usuario'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $usuarioActualizado = $this->usuarioModel->getUsuarioConRol($id);
        unset($usuarioActualizado['contrasena_hash']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estado del usuario actualizado exitosamente',
            'data' => $usuarioActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function cambiarContrasena($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarioModel->find($id);

        if (!$usuario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'contrasena_actual' => [
                'label' => 'Contraseña actual',
                'rules' => 'required',
                'errors' => [
                    'required' => 'La contraseña actual es requerida'
                ]
            ],
            'contrasena_nueva' => [
                'label' => 'Contraseña nueva',
                'rules' => 'required|min_length[8]|max_length[12]',
                'errors' => [
                    'required' => 'La contraseña nueva es requerida',
                    'min_length' => 'La contraseña debe tener al menos 8 caracteres',
                    'max_length' => 'La contraseña no puede exceder 12 caracteres'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $contrasenaActual = $this->request->getVar('contrasena_actual');
        $contrasenaNueva = $this->request->getVar('contrasena_nueva');

        if (!password_verify($contrasenaActual, $usuario['contrasena_hash'])) {
            return $this->respond([
                'status' => 'error',
                'message' => 'La contraseña actual es incorrecta'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $pwdErrors = $this->validatePasswordRequirements($contrasenaNueva);
        if (!empty($pwdErrors)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Contraseña nueva no cumple requisitos',
                'errors' => ['contrasena_nueva' => $pwdErrors]
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->usuarioModel->update($id, [
            'contrasena_hash' => password_hash($contrasenaNueva, PASSWORD_DEFAULT)
        ]);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cambiar la contraseña'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Contraseña actualizada exitosamente'
        ], ResponseInterface::HTTP_OK);
    }
}
