<?php

namespace App\Controllers;

use App\Models\HorarioModel;
use App\Models\EstadoModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Horario extends ResourceController
{
    protected $modelName = 'App\Models\HorarioModel';
    protected $format = 'json';
    protected $horarioModel;

    public function __construct()
    {
        $this->horarioModel = new HorarioModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $grupoId = $this->request->getVar('grupo_id');
        $cursoId = $this->request->getVar('curso_id');
        $diaSemana = $this->request->getVar('dia_semana');

        $builder = $this->horarioModel
            ->select('horarios.*, 
                grupos.nombre as grupo_nombre,
                cursos.nombre as curso_nombre,
                estados.nombre as estado_nombre')
            ->join('grupos', 'grupos.id = horarios.grupo_id')
            ->join('cursos', 'cursos.id = horarios.curso_id')
            ->join('estados', 'estados.id = horarios.estado_id');

        if (!empty($grupoId)) {
            $builder->where('horarios.grupo_id', $grupoId);
        }

        if (!empty($cursoId)) {
            $builder->where('horarios.curso_id', $cursoId);
        }

        if (!empty($diaSemana)) {
            $builder->where('horarios.dia_semana', $diaSemana);
        }

        $builder->orderBy('horarios.dia_semana', 'ASC')
            ->orderBy('horarios.hora_inicio', 'ASC');

        $horarios = $builder->paginate($perPage, 'default', $page);
        $pager = $this->horarioModel->pager;

        foreach ($horarios as &$horario) {
            $horario['dia_semana_nombre'] = $this->horarioModel->getNombreDia($horario['dia_semana']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $horarios,
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
                'message' => 'ID de horario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $horario = $this->horarioModel->getHorarioCompleto($id);

        if (!$horario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Horario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $horario['dia_semana_nombre'] = $this->horarioModel->getNombreDia($horario['dia_semana']);

        return $this->respond([
            'status' => 'success',
            'data' => $horario
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
                    'is_natural_no_zero' => 'El grupo debe ser válido'
                ]
            ],
            'curso_id' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El curso es requerido',
                    'is_natural_no_zero' => 'El curso debe ser válido'
                ]
            ],
            'dia_semana' => [
                'label' => 'Día de la semana',
                'rules' => 'required|is_natural_no_zero|in_list[1,2,3,4,5,6,7]',
                'errors' => [
                    'required' => 'El día de la semana es requerido',
                    'is_natural_no_zero' => 'El día debe ser válido',
                    'in_list' => 'El día debe estar entre 1 (Lunes) y 7 (Domingo)'
                ]
            ],
            'hora_inicio' => [
                'label' => 'Hora de inicio',
                'rules' => 'required|regex_match[/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/]',
                'errors' => [
                    'required' => 'La hora de inicio es requerida',
                    'regex_match' => 'La hora de inicio debe tener formato HH:MM o HH:MM:SS'
                ]
            ],
            'hora_fin' => [
                'label' => 'Hora de fin',
                'rules' => 'required|regex_match[/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/]',
                'errors' => [
                    'required' => 'La hora de fin es requerida',
                    'regex_match' => 'La hora de fin debe tener formato HH:MM o HH:MM:SS'
                ]
            ],
            'ubicacion' => [
                'label' => 'Ubicación',
                'rules' => 'permit_empty|max_length[120]',
                'errors' => [
                    'max_length' => 'La ubicación no puede exceder 120 caracteres'
                ]
            ],
            'estado_id' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|is_natural_no_zero|in_list[1,2,3]',
                'errors' => [
                    'is_natural_no_zero' => 'El estado debe ser válido',
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

        $grupoId = $this->request->getVar('grupo_id');
        $diaSemana = $this->request->getVar('dia_semana');
        $horaInicio = $this->request->getVar('hora_inicio');
        $horaFin = $this->request->getVar('hora_fin');

        if (strlen($horaInicio) == 5) {
            $horaInicio .= ':00';
        }
        if (strlen($horaFin) == 5) {
            $horaFin .= ':00';
        }

        if ($horaFin <= $horaInicio) {
            return $this->respond([
                'status' => 'error',
                'message' => 'La hora de fin debe ser mayor que la hora de inicio'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if ($this->horarioModel->verificarConflictoGrupo($grupoId, $diaSemana, $horaInicio, $horaFin)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'El grupo ya tiene un horario asignado en ese día y hora'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $data = [
            'grupo_id' => $grupoId,
            'curso_id' => $this->request->getVar('curso_id'),
            'dia_semana' => $diaSemana,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'ubicacion' => $this->request->getVar('ubicacion'),
            'estado_id' => $this->request->getVar('estado_id') ?? EstadoModel::ACTIVO
        ];

        $horarioId = $this->horarioModel->insert($data);

        if (!$horarioId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el horario',
                'errors' => $this->horarioModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $horario = $this->horarioModel->getHorarioCompleto($horarioId);
        $horario['dia_semana_nombre'] = $this->horarioModel->getNombreDia($horario['dia_semana']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Horario creado exitosamente',
            'data' => $horario
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de horario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $horario = $this->horarioModel->find($id);

        if (!$horario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Horario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'grupo_id' => [
                'label' => 'Grupo',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El grupo debe ser válido'
                ]
            ],
            'curso_id' => [
                'label' => 'Curso',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El curso debe ser válido'
                ]
            ],
            'dia_semana' => [
                'label' => 'Día de la semana',
                'rules' => 'permit_empty|is_natural_no_zero|in_list[1,2,3,4,5,6,7]',
                'errors' => [
                    'is_natural_no_zero' => 'El día debe ser válido',
                    'in_list' => 'El día debe estar entre 1 (Lunes) y 7 (Domingo)'
                ]
            ],
            'hora_inicio' => [
                'label' => 'Hora de inicio',
                'rules' => 'permit_empty|regex_match[/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/]',
                'errors' => [
                    'regex_match' => 'La hora de inicio debe tener formato HH:MM o HH:MM:SS'
                ]
            ],
            'hora_fin' => [
                'label' => 'Hora de fin',
                'rules' => 'permit_empty|regex_match[/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/]',
                'errors' => [
                    'regex_match' => 'La hora de fin debe tener formato HH:MM o HH:MM:SS'
                ]
            ],
            'ubicacion' => [
                'label' => 'Ubicación',
                'rules' => 'permit_empty|max_length[120]',
                'errors' => [
                    'max_length' => 'La ubicación no puede exceder 120 caracteres'
                ]
            ],
            'estado_id' => [
                'label' => 'Estado',
                'rules' => 'permit_empty|is_natural_no_zero|in_list[1,2,3]',
                'errors' => [
                    'is_natural_no_zero' => 'El estado debe ser válido',
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

        if ($this->request->getVar('grupo_id') !== null) {
            $data['grupo_id'] = $this->request->getVar('grupo_id');
        }

        if ($this->request->getVar('curso_id') !== null) {
            $data['curso_id'] = $this->request->getVar('curso_id');
        }

        if ($this->request->getVar('dia_semana') !== null) {
            $data['dia_semana'] = $this->request->getVar('dia_semana');
        }

        if ($this->request->getVar('hora_inicio') !== null) {
            $horaInicio = $this->request->getVar('hora_inicio');
            if (strlen($horaInicio) == 5) {
                $horaInicio .= ':00';
            }
            $data['hora_inicio'] = $horaInicio;
        }

        if ($this->request->getVar('hora_fin') !== null) {
            $horaFin = $this->request->getVar('hora_fin');
            if (strlen($horaFin) == 5) {
                $horaFin .= ':00';
            }
            $data['hora_fin'] = $horaFin;
        }

        if ($this->request->getVar('ubicacion') !== null) {
            $data['ubicacion'] = $this->request->getVar('ubicacion');
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

        $grupoId = $data['grupo_id'] ?? $horario['grupo_id'];
        $diaSemana = $data['dia_semana'] ?? $horario['dia_semana'];
        $horaInicio = $data['hora_inicio'] ?? $horario['hora_inicio'];
        $horaFin = $data['hora_fin'] ?? $horario['hora_fin'];

        if ($horaFin <= $horaInicio) {
            return $this->respond([
                'status' => 'error',
                'message' => 'La hora de fin debe ser mayor que la hora de inicio'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if ($this->horarioModel->verificarConflictoGrupo($grupoId, $diaSemana, $horaInicio, $horaFin, $id)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'El grupo ya tiene un horario asignado en ese día y hora'
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $updated = $this->horarioModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el horario',
                'errors' => $this->horarioModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $horarioActualizado = $this->horarioModel->getHorarioCompleto($id);
        $horarioActualizado['dia_semana_nombre'] = $this->horarioModel->getNombreDia($horarioActualizado['dia_semana']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Horario actualizado exitosamente',
            'data' => $horarioActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de horario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $horario = $this->horarioModel->find($id);

        if (!$horario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Horario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->horarioModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el horario'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Horario eliminado exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function porGrupo($grupoId = null)
    {
        if (!$grupoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $horarios = $this->horarioModel->getHorariosPorGrupo($grupoId);

        foreach ($horarios as &$horario) {
            $horario['dia_semana_nombre'] = $this->horarioModel->getNombreDia($horario['dia_semana']);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $horarios
        ], ResponseInterface::HTTP_OK);
    }

    public function validarConflicto()
    {
        $grupoId = $this->request->getVar('grupo_id');
        $diaSemana = $this->request->getVar('dia_semana');
        $horaInicio = $this->request->getVar('hora_inicio');
        $horaFin = $this->request->getVar('hora_fin');
        $horarioId = $this->request->getVar('horario_id');

        if (!$grupoId || !$diaSemana || !$horaInicio || !$horaFin) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Faltan parámetros requeridos'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if (strlen($horaInicio) == 5) {
            $horaInicio .= ':00';
        }
        if (strlen($horaFin) == 5) {
            $horaFin .= ':00';
        }

        $conflictoGrupo = $this->horarioModel->verificarConflictoGrupo($grupoId, $diaSemana, $horaInicio, $horaFin, $horarioId);

        return $this->respond([
            'status' => 'success',
            'data' => [
                'tiene_conflicto' => $conflictoGrupo,
                'conflicto_grupo' => $conflictoGrupo
            ]
        ], ResponseInterface::HTTP_OK);
    }
}