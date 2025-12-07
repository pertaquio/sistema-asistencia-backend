<?php

namespace App\Controllers;

use App\Models\SesionModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Sesion extends ResourceController
{
    protected $modelName = 'App\Models\SesionModel';
    protected $format = 'json';
    protected $sesionModel;

    public function __construct()
    {
        $this->sesionModel = new SesionModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $grupoId = $this->request->getVar('grupo_id') ?? '';
        $cursoId = $this->request->getVar('curso_id') ?? '';
        $estado = $this->request->getVar('estado') ?? '';
        $fechaInicio = $this->request->getVar('fecha_inicio') ?? '';
        $fechaFin = $this->request->getVar('fecha_fin') ?? '';

        $builder = $this->sesionModel
            ->select('sesiones.*, 
                grupos.nombre as grupo_nombre,
                grupos.anio_academico,
                cursos.nombre as curso_nombre,
                cursos.codigo as curso_codigo,
                profesores_usuarios.nombre_completo as profesor_nombre')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios as profesores_usuarios', 'profesores_usuarios.id = profesores.usuario_id', 'left');

        if (!empty($grupoId)) {
            $builder->where('sesiones.grupo_id', $grupoId);
        }

        if (!empty($cursoId)) {
            $builder->where('sesiones.curso_id', $cursoId);
        }

        if (!empty($estado)) {
            $builder->where('sesiones.estado', $estado);
        }

        if (!empty($fechaInicio)) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if (!empty($fechaFin)) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        $sesiones = $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->orderBy('sesiones.hora_inicio', 'ASC')
            ->paginate($perPage, 'default', $page);

        $pager = $this->sesionModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $sesiones,
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
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->getSesionCompleta($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $sesion
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'grupo_id' => [
                'label' => 'Grupo',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El grupo es requerido',
                    'is_natural_no_zero' => 'El ID del grupo debe ser válido'
                ]
            ],
            'curso_id' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El curso es requerido',
                    'is_natural_no_zero' => 'El ID del curso debe ser válido'
                ]
            ],
            'fecha_programada' => [
                'label' => 'Fecha programada',
                'rules' => 'required|valid_date',
                'errors' => [
                    'required' => 'La fecha programada es requerida',
                    'valid_date' => 'La fecha programada debe ser válida'
                ]
            ],
            'hora_inicio' => [
                'label' => 'Hora de inicio',
                'rules' => 'permit_empty',
                'errors' => []
            ],
            'hora_fin' => [
                'label' => 'Hora de fin',
                'rules' => 'permit_empty',
                'errors' => []
            ],
            'estado' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|in_list[planificada,realizada,cancelada]',
                'errors' => [
                    'in_list' => 'El estado debe ser planificada, realizada o cancelada'
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

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;

        $data = [
            'grupo_id' => $this->request->getVar('grupo_id'),
            'curso_id' => $this->request->getVar('curso_id'),
            'fecha_programada' => $this->request->getVar('fecha_programada'),
            'hora_inicio' => $this->request->getVar('hora_inicio'),
            'hora_fin' => $this->request->getVar('hora_fin'),
            'estado' => $this->request->getVar('estado') ?? 'planificada',
            'creada_por' => $usuarioAutenticado->usuario_id ?? null
        ];

        $sesionId = $this->sesionModel->insert($data);

        if (!$sesionId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear la sesión',
                'errors' => $this->sesionModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $sesion = $this->sesionModel->getSesionCompleta($sesionId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Sesión creada exitosamente',
            'data' => $sesion
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'fecha_programada' => [
                'label' => 'Fecha programada',
                'rules' => 'permit_empty|valid_date',
                'errors' => [
                    'valid_date' => 'La fecha programada debe ser válida'
                ]
            ],
            'hora_inicio' => [
                'label' => 'Hora de inicio',
                'rules' => 'permit_empty',
                'errors' => []
            ],
            'hora_fin' => [
                'label' => 'Hora de fin',
                'rules' => 'permit_empty',
                'errors' => []
            ],
            'estado' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|in_list[planificada,realizada,cancelada]',
                'errors' => [
                    'in_list' => 'El estado debe ser planificada, realizada o cancelada'
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

        if ($this->request->getVar('fecha_programada')) {
            $data['fecha_programada'] = $this->request->getVar('fecha_programada');
        }

        if ($this->request->getVar('hora_inicio') !== null) {
            $data['hora_inicio'] = $this->request->getVar('hora_inicio');
        }

        if ($this->request->getVar('hora_fin') !== null) {
            $data['hora_fin'] = $this->request->getVar('hora_fin');
        }

        if ($this->request->getVar('estado')) {
            $data['estado'] = $this->request->getVar('estado');
        }

        if (empty($data)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No hay datos para actualizar'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->sesionModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar la sesión',
                'errors' => $this->sesionModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $sesionActualizada = $this->sesionModel->getSesionCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Sesión actualizada exitosamente',
            'data' => $sesionActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->sesionModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar la sesión'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Sesión eliminada exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function getAsistencias($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $asistencias = $this->sesionModel->getAsistencias($id);

        return $this->respond([
            'status' => 'success',
            'data' => $asistencias
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstadisticas($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estadisticas = $this->sesionModel->getEstadisticasAsistencia($id);

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
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'estado' => [
                'label' => 'Estado',
                'rules' => 'required|in_list[planificada,realizada,cancelada]',
                'errors' => [
                    'required' => 'El estado es requerido',
                    'in_list' => 'El estado debe ser planificada, realizada o cancelada'
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

        $estado = $this->request->getVar('estado');

        $updated = $this->sesionModel->cambiarEstado($id, $estado);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cambiar el estado de la sesión'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $sesionActualizada = $this->sesionModel->getSesionCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estado cambiado exitosamente',
            'data' => $sesionActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function iniciarSesion($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $updated = $this->sesionModel->iniciarSesion($id);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al iniciar la sesión'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $sesionActualizada = $this->sesionModel->getSesionCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Sesión iniciada exitosamente',
            'data' => $sesionActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function cancelarSesion($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $updated = $this->sesionModel->cancelarSesion($id);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cancelar la sesión'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $sesionActualizada = $this->sesionModel->getSesionCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Sesión cancelada exitosamente',
            'data' => $sesionActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function getSesionesPorFecha($fecha = null)
    {
        if (!$fecha) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Fecha requerida'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupoId = $this->request->getVar('grupo_id');
        $cursoId = $this->request->getVar('curso_id');

        $sesiones = $this->sesionModel->getSesionesPorFecha($fecha, $grupoId, $cursoId);

        return $this->respond([
            'status' => 'success',
            'data' => $sesiones
        ], ResponseInterface::HTTP_OK);
    }

    public function generarSesionesMasivas()
    {
        $rules = [
            'grupo_id' => [
                'label' => 'Grupo',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El grupo es requerido',
                    'is_natural_no_zero' => 'El ID del grupo debe ser válido'
                ]
            ],
            'fecha_inicio' => [
                'label' => 'Fecha de inicio',
                'rules' => 'required|valid_date',
                'errors' => [
                    'required' => 'La fecha de inicio es requerida',
                    'valid_date' => 'La fecha de inicio debe ser válida'
                ]
            ],
            'fecha_fin' => [
                'label' => 'Fecha de fin',
                'rules' => 'required|valid_date',
                'errors' => [
                    'required' => 'La fecha de fin es requerida',
                    'valid_date' => 'La fecha de fin debe ser válida'
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

        $grupoId = $this->request->getVar('grupo_id');
        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;
        $creadoPor = $usuarioAutenticado->usuario_id ?? null;

        $resultado = $this->sesionModel->generarSesionesPorHorario($grupoId, $fechaInicio, $fechaFin, $creadoPor);

        return $this->respond([
            'status' => 'success',
            'message' => $resultado['mensaje'],
            'data' => $resultado
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstudiantesParaAsistencia($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de sesión requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $sesion = $this->sesionModel->find($id);

        if (!$sesion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Sesión no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estudiantes = $this->sesionModel->getEstudiantesParaAsistencia($id);

        return $this->respond([
            'status' => 'success',
            'data' => $estudiantes
        ], ResponseInterface::HTTP_OK);
    }
}