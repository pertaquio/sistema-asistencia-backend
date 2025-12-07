<?php

namespace App\Controllers;

use App\Models\EstudianteModel;
use App\Models\UsuarioModel;
use App\Models\EstadoModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Estudiante extends ResourceController
{
    protected $modelName = 'App\Models\EstudianteModel';
    protected $format = 'json';
    protected $estudianteModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->estudianteModel = new EstudianteModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $search = $this->request->getVar('search') ?? '';
        $genero = $this->request->getVar('genero') ?? '';

        $builder = $this->estudianteModel
            ->select('estudiantes.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('estudiantes.codigo_estudiante', $search)
                ->orLike('usuarios.nombre_usuario', $search)
                ->orLike('usuarios.email', $search)
                ->orLike('usuarios.nombre_completo', $search)
                ->groupEnd();
        }

        if (!empty($genero)) {
            $builder->where('estudiantes.genero', $genero);
        }

        $estudiantes = $builder->paginate($perPage, 'default', $page);
        $pager = $this->estudianteModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $estudiantes,
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
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->getEstudianteCompleto($id);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $estudiante
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
            'codigo_estudiante' => [
                'label' => 'Código de estudiante',
                'rules' => 'permit_empty|is_unique[estudiantes.codigo_estudiante]',
                'errors' => [
                    'is_unique' => 'Este código de estudiante ya está registrado'
                ]
            ],
            'fecha_nacimiento' => [
                'label' => 'Fecha de nacimiento',
                'rules' => 'permit_empty|valid_date',
                'errors' => [
                    'valid_date' => 'La fecha de nacimiento debe ser válida'
                ]
            ],
            'genero' => [
                'label' => 'Género',
                'rules' => 'permit_empty|in_list[M,F,O]',
                'errors' => [
                    'in_list' => 'El género debe ser M (Masculino), F (Femenino) u O (Otro)'
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
                'rol_id' => 3,
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

            $estudianteData = [
                'usuario_id' => $usuarioId,
                'codigo_estudiante' => $this->request->getVar('codigo_estudiante'),
                'fecha_nacimiento' => $this->request->getVar('fecha_nacimiento'),
                'genero' => $this->request->getVar('genero') ?? 'O',
                'telefono' => $this->request->getVar('telefono') ?? null,
                'direccion' => $this->request->getVar('direccion') ?? null,
                'nombre_tutor' => $this->request->getVar('nombre_tutor') ?? null
            ];

            $estudianteId = $this->estudianteModel->insert($estudianteData);

            if (!$estudianteId) {
                $db->transRollback();
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error al crear el estudiante',
                    'errors' => $this->estudianteModel->errors()
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error en la transacción'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $estudiante = $this->estudianteModel->getEstudianteCompleto($estudianteId);

            return $this->respond([
                'status' => 'success',
                'message' => 'Estudiante creado exitosamente',
                'data' => $estudiante
            ], ResponseInterface::HTTP_CREATED);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el estudiante: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->find($id);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'nombre_usuario' => [
                'label' => 'Nombre de usuario',
                'rules' => "permit_empty|valid_email|is_unique[usuarios.nombre_usuario,id,{$estudiante['usuario_id']}]",
                'errors' => [
                    'valid_email' => 'El nombre de usuario debe ser un correo electrónico válido',
                    'is_unique' => 'Este nombre de usuario ya está registrado'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => "permit_empty|valid_email|is_unique[usuarios.email,id,{$estudiante['usuario_id']}]",
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
            'codigo_estudiante' => [
                'label' => 'Código de estudiante',
                'rules' => "permit_empty|is_unique[estudiantes.codigo_estudiante,id,{$id}]",
                'errors' => [
                    'is_unique' => 'Este código de estudiante ya está registrado'
                ]
            ],
            'fecha_nacimiento' => [
                'label' => 'Fecha de nacimiento',
                'rules' => 'permit_empty|valid_date',
                'errors' => [
                    'valid_date' => 'La fecha de nacimiento debe ser válida'
                ]
            ],
            'genero' => [
                'label' => 'Género',
                'rules' => 'permit_empty|in_list[M,F,O]',
                'errors' => [
                    'in_list' => 'El género debe ser M (Masculino), F (Femenino) u O (Otro)'
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
                $updated = $this->usuarioModel->update($estudiante['usuario_id'], $usuarioData);
                if (!$updated) {
                    $db->transRollback();
                    return $this->respond([
                        'status' => 'error',
                        'message' => 'Error al actualizar el usuario',
                        'errors' => $this->usuarioModel->errors()
                    ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $estudianteData = [];

            if ($this->request->getVar('codigo_estudiante') !== null) {
                $estudianteData['codigo_estudiante'] = $this->request->getVar('codigo_estudiante');
            }

            if ($this->request->getVar('fecha_nacimiento') !== null) {
                $estudianteData['fecha_nacimiento'] = $this->request->getVar('fecha_nacimiento');
            }

            if ($this->request->getVar('genero') !== null) {
                $estudianteData['genero'] = $this->request->getVar('genero');
            }

            if ($this->request->getVar('telefono') !== null) {
                $estudianteData['telefono'] = $this->request->getVar('telefono');
            }

            if ($this->request->getVar('direccion') !== null) {
                $estudianteData['direccion'] = $this->request->getVar('direccion');
            }

            if ($this->request->getVar('nombre_tutor') !== null) {
                $estudianteData['nombre_tutor'] = $this->request->getVar('nombre_tutor');
            }

            if (!empty($estudianteData)) {
                $updated = $this->estudianteModel->update($id, $estudianteData);
                if (!$updated) {
                    $db->transRollback();
                    return $this->respond([
                        'status' => 'error',
                        'message' => 'Error al actualizar el estudiante',
                        'errors' => $this->estudianteModel->errors()
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

            $estudianteActualizado = $this->estudianteModel->getEstudianteCompleto($id);

            return $this->respond([
                'status' => 'success',
                'message' => 'Estudiante actualizado exitosamente',
                'data' => $estudianteActualizado
            ], ResponseInterface::HTTP_OK);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el estudiante: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->find($id);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $this->estudianteModel->delete($id);
            $this->usuarioModel->delete($estudiante['usuario_id']);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Error en la transacción'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->respond([
                'status' => 'success',
                'message' => 'Estudiante eliminado exitosamente'
            ], ResponseInterface::HTTP_OK);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el estudiante: ' . $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getByCodigo($codigo = null)
    {
        if (!$codigo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Código de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->getEstudiantePorCodigo($codigo);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $estudiante
        ], ResponseInterface::HTTP_OK);
    }

    public function getInscripciones($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->find($id);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $inscripciones = $this->estudianteModel->getInscripciones($id);

        return $this->respond([
            'status' => 'success',
            'data' => $inscripciones
        ], ResponseInterface::HTTP_OK);
    }

    public function getAsistencias($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->find($id);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');
        $grupoId = $this->request->getVar('grupo_id');
        $estado = $this->request->getVar('estado');

        $asistencias = $this->estudianteModel->getAsistencias($id, $fechaInicio, $fechaFin, $grupoId, $estado);

        return $this->respond([
            'status' => 'success',
            'data' => $asistencias
        ], ResponseInterface::HTTP_OK);
    }

    public function cambiarEstado($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estudiante = $this->estudianteModel->find($id);

        if (!$estudiante) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Estudiante no encontrado'
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

        $updated = $this->usuarioModel->update($estudiante['usuario_id'], ['estado_id' => $estadoId]);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cambiar el estado del estudiante'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $estudianteActualizado = $this->estudianteModel->getEstudianteCompleto($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estado del estudiante actualizado exitosamente',
            'data' => $estudianteActualizado
        ], ResponseInterface::HTTP_OK);
    }
}