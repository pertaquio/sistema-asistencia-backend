<?php

namespace App\Controllers;

use App\Models\ProfesorModel;
use App\Models\UsuarioModel;
use App\Models\EstadoModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Profesor extends ResourceController
{
    protected $modelName = 'App\Models\ProfesorModel';
    protected $format = 'json';
    protected $profesorModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->profesorModel = new ProfesorModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $search = $this->request->getVar('search') ?? '';
        $departamento = $this->request->getVar('departamento') ?? '';
        $especialidad = $this->request->getVar('especialidad') ?? '';

        $builder = $this->profesorModel
            ->select('profesores.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre')
            ->join('usuarios', 'usuarios.id = profesores.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('profesores.codigo_profesor', $search)
                ->orLike('usuarios.nombre_usuario', $search)
                ->orLike('usuarios.email', $search)
                ->orLike('usuarios.nombre_completo', $search)
                ->groupEnd();
        }

        if (!empty($departamento)) {
            $builder->where('profesores.departamento', $departamento);
        }

        if (!empty($especialidad)) {
            $builder->like('profesores.especialidad', $especialidad);
        }

        $profesores = $builder->paginate($perPage, 'default', $page);
        $pager = $this->profesorModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $profesores,
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
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->getProfesorCompleto($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $profesor
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
                'rules' => 'required|min_length[6]|max_length[12]',
                'errors' => [
                    'required' => 'La contraseña es requerida',
                    'min_length' => 'La contraseña debe tener al menos 6 caracteres',
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
            'codigo_profesor' => [
                'label' => 'Código de profesor',
                'rules' => 'permit_empty|is_unique[profesores.codigo_profesor]',
                'errors' => [
                    'is_unique' => 'Este código de profesor ya está registrado'
                ]
            ],
            'departamento' => [
                'label' => 'Departamento',
                'rules' => 'permit_empty|max_length[100]',
                'errors' => [
                    'max_length' => 'El departamento no puede exceder 100 caracteres'
                ]
            ],
            'telefono' => [
                'label' => 'Teléfono',
                'rules' => 'permit_empty|max_length[20]',
                'errors' => [
                    'max_length' => 'El teléfono no puede exceder 20 caracteres'
                ]
            ],
            'direccion' => [
                'label' => 'Dirección',
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La dirección no puede exceder 255 caracteres'
                ]
            ],
            'especialidad' => [
                'label' => 'Especialidad',
                'rules' => 'permit_empty|max_length[150]',
                'errors' => [
                    'max_length' => 'La especialidad no puede exceder 150 caracteres'
                ]
            ],
            'fecha_contratacion' => [
                'label' => 'Fecha de contratación',
                'rules' => 'permit_empty|valid_date',
                'errors' => [
                    'valid_date' => 'La fecha de contratación debe ser válida'
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

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $usuarioData = [
                'nombre_usuario' => $this->request->getVar('nombre_usuario'),
                'email' => $this->request->getVar('email') ?? $this->request->getVar('nombre_usuario'),
                'contrasena_hash' => $this->request->getVar('contrasena'),
                'nombre_completo' => $this->request->getVar('nombre_completo'),
                'rol_id' => 2,
                'estado_id' => EstadoModel::ACTIVO
            ];

            $usuarioId = $this->usuarioModel->insert($usuarioData);

            if (!$usuarioId) {
                $db->transRollback();
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error al crear el usuario',
                    'errors' => $this->usuarioModel->errors()
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $profesorData = [
                'usuario_id' => $usuarioId,
                'codigo_profesor' => $this->request->getVar('codigo_profesor'),
                'departamento' => $this->request->getVar('departamento'),
                'telefono' => $this->request->getVar('telefono'),
                'direccion' => $this->request->getVar('direccion'),
                'especialidad' => $this->request->getVar('especialidad'),
                'fecha_contratacion' => $this->request->getVar('fecha_contratacion')
            ];

            $profesorId = $this->profesorModel->insert($profesorData);

            if (!$profesorId) {
                $db->transRollback();
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error al crear el profesor',
                    'errors' => $this->profesorModel->errors()
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error en la transacción'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $profesor = $this->profesorModel->getProfesorCompleto($profesorId);

            return $this->respond([
                'status' => 'success',
                'message' => 'Profesor creado exitosamente',
                'data' => $profesor
            ], ResponseInterface::HTTP_CREATED);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el profesor: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'nombre_usuario' => [
                'label' => 'Nombre de usuario',
                'rules' => "permit_empty|valid_email|is_unique[usuarios.nombre_usuario,id,{$profesor['usuario_id']}]",
                'errors' => [
                    'valid_email' => 'El nombre de usuario debe ser un correo electrónico válido',
                    'is_unique' => 'Este nombre de usuario ya está registrado'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => "permit_empty|valid_email|is_unique[usuarios.email,id,{$profesor['usuario_id']}]",
                'errors' => [
                    'valid_email' => 'El email debe ser válido',
                    'is_unique' => 'Este email ya está registrado'
                ]
            ],
            'contrasena' => [
                'label' => 'Contraseña',
                'rules' => 'permit_empty|min_length[6]|max_length[12]',
                'errors' => [
                    'min_length' => 'La contraseña debe tener al menos 6 caracteres',
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
            'codigo_profesor' => [
                'label' => 'Código de profesor',
                'rules' => "permit_empty|is_unique[profesores.codigo_profesor,id,{$id}]",
                'errors' => [
                    'is_unique' => 'Este código de profesor ya está registrado'
                ]
            ],
            'departamento' => [
                'label' => 'Departamento',
                'rules' => 'permit_empty|max_length[100]',
                'errors' => [
                    'max_length' => 'El departamento no puede exceder 100 caracteres'
                ]
            ],
            'telefono' => [
                'label' => 'Teléfono',
                'rules' => 'permit_empty|max_length[20]',
                'errors' => [
                    'max_length' => 'El teléfono no puede exceder 20 caracteres'
                ]
            ],
            'direccion' => [
                'label' => 'Dirección',
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La dirección no puede exceder 255 caracteres'
                ]
            ],
            'especialidad' => [
                'label' => 'Especialidad',
                'rules' => 'permit_empty|max_length[150]',
                'errors' => [
                    'max_length' => 'La especialidad no puede exceder 150 caracteres'
                ]
            ],
            'fecha_contratacion' => [
                'label' => 'Fecha de contratación',
                'rules' => 'permit_empty|valid_date',
                'errors' => [
                    'valid_date' => 'La fecha de contratación debe ser válida'
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

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $usuarioData = [];

            if ($this->request->getVar('nombre_usuario')) {
                $usuarioData['nombre_usuario'] = $this->request->getVar('nombre_usuario');
            }

            if ($this->request->getVar('email')) {
                $usuarioData['email'] = $this->request->getVar('email');
            }

            if ($this->request->getVar('contrasena')) {
                $usuarioData['contrasena_hash'] = $this->request->getVar('contrasena');
            }

            if ($this->request->getVar('nombre_completo')) {
                $usuarioData['nombre_completo'] = $this->request->getVar('nombre_completo');
            }

            if (!empty($usuarioData)) {
                $updated = $this->usuarioModel->update($profesor['usuario_id'], $usuarioData);
                if (!$updated) {
                    $db->transRollback();
                    return $this->respond([
                        'status' => 'error',
                        'message' => 'Error al actualizar el usuario',
                        'errors' => $this->usuarioModel->errors()
                    ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $profesorData = [];

            if ($this->request->getVar('codigo_profesor') !== null) {
                $profesorData['codigo_profesor'] = $this->request->getVar('codigo_profesor');
            }

            if ($this->request->getVar('departamento') !== null) {
                $profesorData['departamento'] = $this->request->getVar('departamento');
            }

            if ($this->request->getVar('telefono') !== null) {
                $profesorData['telefono'] = $this->request->getVar('telefono');
            }

            if ($this->request->getVar('direccion') !== null) {
                $profesorData['direccion'] = $this->request->getVar('direccion');
            }

            if ($this->request->getVar('especialidad') !== null) {
                $profesorData['especialidad'] = $this->request->getVar('especialidad');
            }

            if ($this->request->getVar('fecha_contratacion')) {
                $profesorData['fecha_contratacion'] = $this->request->getVar('fecha_contratacion');
            }

            if (!empty($profesorData)) {
                $updated = $this->profesorModel->update($id, $profesorData);
                if (!$updated) {
                    $db->transRollback();
                    return $this->respond([
                        'status' => 'error',
                        'message' => 'Error al actualizar el profesor',
                        'errors' => $this->profesorModel->errors()
                    ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error en la transacción'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $profesorActualizado = $this->profesorModel->getProfesorCompleto($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Profesor actualizado exitosamente',
                'data' => $profesorActualizado
            ], ResponseInterface::HTTP_OK);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el profesor: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $this->profesorModel->delete($id);
            $this->usuarioModel->delete($profesor['usuario_id']);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error en la transacción'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->respond([
                'status' => 'success',
                'message' => 'Profesor eliminado exitosamente'
            ], ResponseInterface::HTTP_OK);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el profesor: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getByCodigo($codigo = null)
    {
        if (!$codigo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Código de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->getProfesorPorCodigo($codigo);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $profesor
        ], ResponseInterface::HTTP_OK);
    }

    public function getGrupos($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $grupos = $this->profesorModel->getGrupos($id);

        return $this->respond([
            'status' => 'success',
            'data' => $grupos
        ], ResponseInterface::HTTP_OK);
    }

    public function getHorarios($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $horarios = $this->profesorModel->getHorarios($id);

        return $this->respond([
            'status' => 'success',
            'data' => $horarios
        ], ResponseInterface::HTTP_OK);
    }

    public function getSesiones($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');

        $sesiones = $this->profesorModel->getSesiones($id, $fechaInicio, $fechaFin);

        return $this->respond([
            'status' => 'success',
            'data' => $sesiones
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstadisticas($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estadisticas = $this->profesorModel->getEstadisticas($id);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }

    public function cambiarEstado($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de profesor requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $profesor = $this->profesorModel->find($id);

        if (!$profesor) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Profesor no encontrado'
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

        $updated = $this->usuarioModel->update($profesor['usuario_id'], ['estado_id' => $estadoId]);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cambiar el estado del profesor'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $profesorActualizado = $this->profesorModel->getProfesorCompleto($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estado del profesor actualizado exitosamente',
            'data' => $profesorActualizado
        ], ResponseInterface::HTTP_OK);
    }
}