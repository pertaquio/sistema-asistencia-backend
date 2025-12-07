<?php

namespace App\Controllers;

use App\Models\InscripcionModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Inscripcion extends ResourceController
{
    protected $modelName = 'App\Models\InscripcionModel';
    protected $format = 'json';
    protected $inscripcionModel;

    public function __construct()
    {
        $this->inscripcionModel = new InscripcionModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $estudianteId = $this->request->getVar('estudiante_id') ?? '';
        $grupoId = $this->request->getVar('grupo_id') ?? '';
        $estado = $this->request->getVar('estado') ?? '';

        $builder = $this->inscripcionModel
            ->select('inscripciones.*, 
                estudiantes.codigo_estudiante,
                usuarios.nombre_completo as estudiante_nombre,
                grupos.nombre as grupo_nombre,
                grupos.anio_academico,
                cursos.nombre as curso_nombre')
            ->join('estudiantes', 'estudiantes.id = inscripciones.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->join('cursos', 'cursos.id = grupos.curso_id');

        if (!empty($estudianteId)) {
            $builder->where('inscripciones.estudiante_id', $estudianteId);
        }

        if (!empty($grupoId)) {
            $builder->where('inscripciones.grupo_id', $grupoId);
        }

        if (!empty($estado)) {
            $builder->where('inscripciones.estado', $estado);
        }

        $inscripciones = $builder->paginate($perPage, 'default', $page);
        $pager = $this->inscripcionModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $inscripciones,
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
                'message' => 'ID de inscripción requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $inscripcion = $this->inscripcionModel->getInscripcionCompleta($id);

        if (!$inscripcion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Inscripción no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $inscripcion
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'estudiante_id' => [
                'label' => 'Estudiante',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El estudiante es requerido',
                    'is_natural_no_zero' => 'El ID del estudiante debe ser válido'
                ]
            ],
            'grupo_id' => [
                'label' => 'Grupo',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El grupo es requerido',
                    'is_natural_no_zero' => 'El ID del grupo debe ser válido'
                ]
            ],
            'estado' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|in_list[activo,inactivo,graduado]',
                'errors' => [
                    'in_list' => 'El estado debe ser activo, inactivo o graduado'
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

        $estudianteId = $this->request->getVar('estudiante_id');
        $grupoId = $this->request->getVar('grupo_id');

        if ($this->inscripcionModel->verificarInscripcionExistente($estudianteId, $grupoId)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'El estudiante ya está inscrito en este grupo'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $capacidad = $this->inscripcionModel->verificarCapacidadGrupo($grupoId);

        if (!$capacidad['tiene_capacidad']) {
            return $this->respond([
                'status' => 'error',
                'message' => 'El grupo ha alcanzado su capacidad máxima',
                'capacidad' => $capacidad
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $data = [
            'estudiante_id' => $estudianteId,
            'grupo_id' => $grupoId,
            'estado' => $this->request->getVar('estado') ?? 'activo'
        ];

        $inscripcionId = $this->inscripcionModel->insert($data);

        if (!$inscripcionId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear la inscripción',
                'errors' => $this->inscripcionModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $inscripcion = $this->inscripcionModel->getInscripcionCompleta($inscripcionId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Inscripción creada exitosamente',
            'data' => $inscripcion
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de inscripción requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $inscripcion = $this->inscripcionModel->find($id);

        if (!$inscripcion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Inscripción no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'estado' => [
                'label' => 'Estado',
                'rules' => 'required|in_list[activo,inactivo,graduado]',
                'errors' => [
                    'required' => 'El estado es requerido',
                    'in_list' => 'El estado debe ser activo, inactivo o graduado'
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

        $updated = $this->inscripcionModel->update($id, ['estado' => $estado]);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar la inscripción',
                'errors' => $this->inscripcionModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $inscripcionActualizada = $this->inscripcionModel->getInscripcionCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Inscripción actualizada exitosamente',
            'data' => $inscripcionActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de inscripción requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $inscripcion = $this->inscripcionModel->find($id);

        if (!$inscripcion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Inscripción no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->inscripcionModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar la inscripción'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Inscripción eliminada exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function cambiarEstado($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de inscripción requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $inscripcion = $this->inscripcionModel->find($id);

        if (!$inscripcion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Inscripción no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'estado' => [
                'label' => 'Estado',
                'rules' => 'required|in_list[activo,inactivo,graduado]',
                'errors' => [
                    'required' => 'El estado es requerido',
                    'in_list' => 'El estado debe ser activo, inactivo o graduado'
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

        $updated = $this->inscripcionModel->cambiarEstado($id, $estado);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al cambiar el estado de la inscripción'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $inscripcionActualizada = $this->inscripcionModel->getInscripcionCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estado cambiado exitosamente',
            'data' => $inscripcionActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function inscripcionMasiva()
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
            'estudiantes_ids' => [
                'label' => 'Estudiantes',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Los IDs de estudiantes son requeridos'
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
        $estudiantesIds = $this->request->getVar('estudiantes_ids');

        if (!is_array($estudiantesIds) || empty($estudiantesIds)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'estudiantes_ids debe ser un array con al menos un ID'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $resultados = $this->inscripcionModel->inscripcionesMasivas($grupoId, $estudiantesIds);

        $statusCode = $resultados['exitosos'] > 0 ? ResponseInterface::HTTP_OK : ResponseInterface::HTTP_BAD_REQUEST;

        return $this->respond([
            'status' => 'success',
            'message' => "Proceso completado: {$resultados['exitosos']} exitosos, {$resultados['fallidos']} fallidos",
            'data' => $resultados
        ], $statusCode);
    }

    public function verificarCapacidad($grupoId = null)
    {
        if (!$grupoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $capacidad = $this->inscripcionModel->verificarCapacidadGrupo($grupoId);

        return $this->respond([
            'status' => 'success',
            'data' => $capacidad
        ], ResponseInterface::HTTP_OK);
    }
}