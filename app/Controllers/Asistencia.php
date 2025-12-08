<?php

namespace App\Controllers;

use App\Models\AsistenciaModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Asistencia extends ResourceController
{
    protected $modelName = 'App\Models\AsistenciaModel';
    protected $format = 'json';
    protected $asistenciaModel;

    public function __construct()
    {
        $this->asistenciaModel = new AsistenciaModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $sesionId = $this->request->getVar('sesion_id') ?? '';
        $estudianteId = $this->request->getVar('estudiante_id') ?? '';
        $grupoId = $this->request->getVar('grupo_id') ?? '';
        $estado = $this->request->getVar('estado') ?? '';
        $fechaInicio = $this->request->getVar('fecha_inicio') ?? '';
        $fechaFin = $this->request->getVar('fecha_fin') ?? '';

        $builder = $this->asistenciaModel
            ->select('asistencias.*, 
                sesiones.fecha_programada,
                estudiantes.codigo_estudiante,
                usuarios.nombre_completo as estudiante_nombre,
                grupos.nombre as grupo_nombre,
                cursos.nombre as curso_nombre,
                motivos_ausencia.descripcion as motivo_descripcion')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->join('estudiantes', 'estudiantes.id = asistencias.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('motivos_ausencia', 'motivos_ausencia.id = asistencias.motivo_id', 'left');

        if (!empty($sesionId)) {
            $builder->where('asistencias.sesion_id', $sesionId);
        }

        if (!empty($estudianteId)) {
            $builder->where('asistencias.estudiante_id', $estudianteId);
        }

        if (!empty($grupoId)) {
            $builder->where('sesiones.grupo_id', $grupoId);
        }

        if (!empty($estado)) {
            $builder->where('asistencias.estado', $estado);
        }

        if (!empty($fechaInicio)) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if (!empty($fechaFin)) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        $asistencias = $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->paginate($perPage, 'default', $page);

        $pager = $this->asistenciaModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $asistencias,
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
                'message' => 'ID de asistencia requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $asistencia = $this->asistenciaModel->getAsistenciaCompleta($id);

        if (!$asistencia) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Asistencia no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $asistencia
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'sesion_id' => [
                'label' => 'Sesión',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'La sesión es requerida',
                    'is_natural_no_zero' => 'El ID de la sesión debe ser válido'
                ]
            ],
            'estudiante_id' => [
                'label' => 'Estudiante',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El estudiante es requerido',
                    'is_natural_no_zero' => 'El ID del estudiante debe ser válido'
                ]
            ],
            'estado' => [
                'label' => 'Estado',
                'rules' => 'required|in_list[presente,ausente,tarde,justificado]',
                'errors' => [
                    'required' => 'El estado es requerido',
                    'in_list' => 'El estado debe ser presente, ausente, tarde o justificado'
                ]
            ],
            'motivo_id' => [
                'label' => 'Motivo',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El ID del motivo debe ser válido'
                ]
            ],
            'nota' => [
                'label' => 'Nota',
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La nota no puede exceder 255 caracteres'
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

        $sesionId = $this->request->getVar('sesion_id');
        $estudianteId = $this->request->getVar('estudiante_id');

        if ($this->asistenciaModel->verificarAsistenciaExistente($sesionId, $estudianteId)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Ya existe un registro de asistencia para este estudiante en esta sesión'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;

        $data = [
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'estado' => $this->request->getVar('estado'),
            'hora_registro' => $this->request->getVar('hora_registro') ?? date('Y-m-d H:i:s'),
            'motivo_id' => $this->request->getVar('motivo_id'),
            'nota' => $this->request->getVar('nota'),
            'registrado_por' => $usuarioAutenticado->usuario_id ?? null
        ];

        $asistenciaId = $this->asistenciaModel->insert($data);

        if (!$asistenciaId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear la asistencia',
                'errors' => $this->asistenciaModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $asistencia = $this->asistenciaModel->getAsistenciaCompleta($asistenciaId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Asistencia registrada exitosamente',
            'data' => $asistencia
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de asistencia requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $asistencia = $this->asistenciaModel->find($id);

        if (!$asistencia) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Asistencia no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'estado' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|in_list[presente,ausente,tarde,justificado]',
                'errors' => [
                    'in_list' => 'El estado debe ser presente, ausente, tarde o justificado'
                ]
            ],
            'motivo_id' => [
                'label' => 'Motivo',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El ID del motivo debe ser válido'
                ]
            ],
            'nota' => [
                'label' => 'Nota',
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La nota no puede exceder 255 caracteres'
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

        if ($this->request->getVar('estado')) {
            $data['estado'] = $this->request->getVar('estado');
        }

        if ($this->request->getVar('motivo_id') !== null) {
            $data['motivo_id'] = $this->request->getVar('motivo_id');
        }

        if ($this->request->getVar('nota') !== null) {
            $data['nota'] = $this->request->getVar('nota');
        }

        if (empty($data)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No hay datos para actualizar'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->asistenciaModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar la asistencia',
                'errors' => $this->asistenciaModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $asistenciaActualizada = $this->asistenciaModel->getAsistenciaCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Asistencia actualizada exitosamente',
            'data' => $asistenciaActualizada
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de asistencia requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $asistencia = $this->asistenciaModel->find($id);

        if (!$asistencia) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Asistencia no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->asistenciaModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar la asistencia'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Asistencia eliminada exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function registrarMasivo()
    {
        $rules = [
            'sesion_id' => [
                'label' => 'Sesión',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'La sesión es requerida',
                    'is_natural_no_zero' => 'El ID de la sesión debe ser válido'
                ]
            ],
            'asistencias' => [
                'label' => 'Asistencias',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Las asistencias son requeridas'
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

        $sesionId = $this->request->getVar('sesion_id');
        $asistencias = $this->request->getVar('asistencias');

        if (!is_array($asistencias) || empty($asistencias)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'asistencias debe ser un array con al menos un registro'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;
        $registradoPor = $usuarioAutenticado->usuario_id ?? null;

        $resultados = $this->asistenciaModel->registrarMasivo($sesionId, $asistencias, $registradoPor);

        $statusCode = $resultados['exitosos'] > 0 ? ResponseInterface::HTTP_OK : ResponseInterface::HTTP_BAD_REQUEST;

        return $this->respond([
            'status' => 'success',
            'message' => "Proceso completado: {$resultados['exitosos']} exitosos, {$resultados['fallidos']} fallidos",
            'data' => $resultados
        ], $statusCode);
    }

    public function marcarPresente()
    {
        $rules = [
            'sesion_id' => 'required|is_natural_no_zero',
            'estudiante_id' => 'required|is_natural_no_zero'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;

        $asistenciaId = $this->asistenciaModel->marcarPresente(
            $this->request->getVar('sesion_id'),
            $this->request->getVar('estudiante_id'),
            $usuarioAutenticado->usuario_id ?? null
        );

        if (!$asistenciaId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Ya existe un registro de asistencia para este estudiante'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $asistencia = $this->asistenciaModel->getAsistenciaCompleta($asistenciaId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estudiante marcado como presente',
            'data' => $asistencia
        ], ResponseInterface::HTTP_CREATED);
    }

    public function marcarAusente()
    {
        $rules = [
            'sesion_id' => 'required|is_natural_no_zero',
            'estudiante_id' => 'required|is_natural_no_zero',
            'motivo_id' => 'permit_empty|is_natural_no_zero',
            'nota' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;

        $asistenciaId = $this->asistenciaModel->marcarAusente(
            $this->request->getVar('sesion_id'),
            $this->request->getVar('estudiante_id'),
            $usuarioAutenticado->usuario_id ?? null,
            $this->request->getVar('motivo_id'),
            $this->request->getVar('nota')
        );

        if (!$asistenciaId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Ya existe un registro de asistencia para este estudiante'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $asistencia = $this->asistenciaModel->getAsistenciaCompleta($asistenciaId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estudiante marcado como ausente',
            'data' => $asistencia
        ], ResponseInterface::HTTP_CREATED);
    }

    public function marcarTarde()
    {
        $rules = [
            'sesion_id' => 'required|is_natural_no_zero',
            'estudiante_id' => 'required|is_natural_no_zero',
            'nota' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $usuarioAutenticado = $this->request->usuarioAutenticado ?? null;

        $asistenciaId = $this->asistenciaModel->marcarTarde(
            $this->request->getVar('sesion_id'),
            $this->request->getVar('estudiante_id'),
            $usuarioAutenticado->usuario_id ?? null,
            $this->request->getVar('nota')
        );

        if (!$asistenciaId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Ya existe un registro de asistencia para este estudiante'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $asistencia = $this->asistenciaModel->getAsistenciaCompleta($asistenciaId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Estudiante marcado con llegada tarde',
            'data' => $asistencia
        ], ResponseInterface::HTTP_CREATED);
    }

    public function justificarAusencia($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de asistencia requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $rules = [
            'motivo_id' => 'required|is_natural_no_zero',
            'nota' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->asistenciaModel->justificarAusencia(
            $id,
            $this->request->getVar('motivo_id'),
            $this->request->getVar('nota')
        );

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No se pudo justificar la ausencia. Verifique que el registro exista y esté marcado como ausente'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $asistencia = $this->asistenciaModel->getAsistenciaCompleta($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Ausencia justificada exitosamente',
            'data' => $asistencia
        ], ResponseInterface::HTTP_OK);
    }

    public function reportePorEstudiante($estudianteId = null)
    {
        if (!$estudianteId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio') ?: null;
        $fechaFin = $this->request->getVar('fecha_fin') ?: null;
        $grupoId = $this->request->getVar('grupo_id') ? (int)$this->request->getVar('grupo_id') : null;

        $reporte = $this->asistenciaModel->getReportePorEstudiante($estudianteId, $fechaInicio, $fechaFin, $grupoId);

        return $this->respond([
            'status' => 'success',
            'data' => $reporte
        ], ResponseInterface::HTTP_OK);
    }

    public function reportePorGrupo($grupoId = null)
    {
        if (!$grupoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio') ?: null;
        $fechaFin = $this->request->getVar('fecha_fin') ?: null;

        $reporte = $this->asistenciaModel->getReportePorGrupo($grupoId, $fechaInicio, $fechaFin);

        return $this->respond([
            'status' => 'success',
            'data' => $reporte
        ], ResponseInterface::HTTP_OK);
    }

    public function estadisticasPorEstudiante($estudianteId = null)
    {
        if (!$estudianteId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de estudiante requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupoId = $this->request->getVar('grupo_id') ? (int)$this->request->getVar('grupo_id') : null;

        $estadisticas = $this->asistenciaModel->getEstadisticasPorEstudiante($estudianteId, $grupoId);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }

    public function estadisticasPorGrupo($grupoId = null)
    {
        if (!$grupoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $estadisticas = $this->asistenciaModel->getEstadisticasPorGrupo($grupoId);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }
}