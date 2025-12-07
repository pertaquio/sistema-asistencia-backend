<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionModel extends Model
{
    protected $table = 'sesiones';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'grupo_id',
        'curso_id',
        'fecha_programada',
        'hora_inicio',
        'hora_fin',
        'estado',
        'creada_por'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'creado_en';
    protected $updatedField = '';
    protected $deletedField = '';

    protected $validationRules = [
        'grupo_id' => 'required|is_natural_no_zero',
        'curso_id' => 'required|is_natural_no_zero',
        'fecha_programada' => 'required|valid_date',
        'hora_inicio' => 'permit_empty',
        'hora_fin' => 'permit_empty',
        'estado' => 'required|in_list[planificada,realizada,cancelada]'
    ];

    protected $validationMessages = [
        'grupo_id' => [
            'required' => 'El grupo es requerido',
            'is_natural_no_zero' => 'El ID del grupo debe ser válido'
        ],
        'curso_id' => [
            'required' => 'El curso es requerido',
            'is_natural_no_zero' => 'El ID del curso debe ser válido'
        ],
        'fecha_programada' => [
            'required' => 'La fecha programada es requerida',
            'valid_date' => 'La fecha programada debe ser válida'
        ],
        'estado' => [
            'required' => 'El estado es requerido',
            'in_list' => 'El estado debe ser planificada, realizada o cancelada'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    public function getSesionCompleta(int $id)
    {
        return $this->select('sesiones.*, 
            grupos.nombre as grupo_nombre,
            grupos.anio_academico,
            grupos.aula,
            grupos.turno,
            cursos.nombre as curso_nombre,
            cursos.codigo as curso_codigo,
            profesores_usuarios.nombre_completo as profesor_nombre,
            creador_usuarios.nombre_completo as creador_nombre')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios as profesores_usuarios', 'profesores_usuarios.id = profesores.usuario_id', 'left')
            ->join('usuarios as creador_usuarios', 'creador_usuarios.id = sesiones.creada_por', 'left')
            ->where('sesiones.id', $id)
            ->first();
    }

    public function getAsistencias(int $sesionId)
    {
        return $this->db->table('asistencias')
            ->select('asistencias.*, 
                estudiantes.codigo_estudiante,
                usuarios.nombre_completo as estudiante_nombre,
                usuarios.email as estudiante_email,
                motivos_ausencia.descripcion as motivo_descripcion,
                registrador_usuarios.nombre_completo as registrador_nombre')
            ->join('estudiantes', 'estudiantes.id = asistencias.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('motivos_ausencia', 'motivos_ausencia.id = asistencias.motivo_id', 'left')
            ->join('usuarios as registrador_usuarios', 'registrador_usuarios.id = asistencias.registrado_por', 'left')
            ->where('asistencias.sesion_id', $sesionId)
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getEstadisticasAsistencia(int $sesionId)
    {
        $asistencias = $this->db->table('asistencias')
            ->select('estado, COUNT(*) as total')
            ->where('sesion_id', $sesionId)
            ->groupBy('estado')
            ->get()
            ->getResultArray();

        $estadisticas = [
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0,
            'total' => 0,
            'porcentaje_asistencia' => 0
        ];

        foreach ($asistencias as $asistencia) {
            $estadisticas[$asistencia['estado']] = (int)$asistencia['total'];
            $estadisticas['total'] += (int)$asistencia['total'];
        }

        if ($estadisticas['total'] > 0) {
            $asistenciasValidas = $estadisticas['presente'] + $estadisticas['tarde'];
            $estadisticas['porcentaje_asistencia'] = round(($asistenciasValidas / $estadisticas['total']) * 100, 2);
        }

        return $estadisticas;
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['planificada', 'realizada', 'cancelada'])) {
            return false;
        }

        return $this->update($id, ['estado' => $estado]);
    }

    public function iniciarSesion(int $id): bool
    {
        return $this->cambiarEstado($id, 'realizada');
    }

    public function cancelarSesion(int $id): bool
    {
        return $this->cambiarEstado($id, 'cancelada');
    }

    public function getSesionesPorFecha(string $fecha, ?int $grupoId = null, ?int $cursoId = null)
    {
        $builder = $this->select('sesiones.*, 
            grupos.nombre as grupo_nombre,
            cursos.nombre as curso_nombre,
            profesores_usuarios.nombre_completo as profesor_nombre')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios as profesores_usuarios', 'profesores_usuarios.id = profesores.usuario_id', 'left')
            ->where('sesiones.fecha_programada', $fecha);

        if ($grupoId) {
            $builder->where('sesiones.grupo_id', $grupoId);
        }

        if ($cursoId) {
            $builder->where('sesiones.curso_id', $cursoId);
        }

        return $builder->orderBy('sesiones.hora_inicio', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function generarSesionesPorHorario(int $grupoId, string $fechaInicio, string $fechaFin, int $creadoPor): array
    {
        $horarios = $this->db->table('horarios')
            ->where('grupo_id', $grupoId)
            ->get()
            ->getResultArray();

        if (empty($horarios)) {
            return [
                'exitosos' => 0,
                'fallidos' => 0,
                'mensaje' => 'El grupo no tiene horarios configurados'
            ];
        }

        $grupo = $this->db->table('grupos')
            ->select('curso_id')
            ->where('id', $grupoId)
            ->get()
            ->getRowArray();

        if (!$grupo) {
            return [
                'exitosos' => 0,
                'fallidos' => 0,
                'mensaje' => 'Grupo no encontrado'
            ];
        }

        $diasSemana = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];

        $exitosos = 0;
        $fallidos = 0;

        $fechaActual = new \DateTime($fechaInicio);
        $fechaFinal = new \DateTime($fechaFin);

        while ($fechaActual <= $fechaFinal) {
            $diaSemana = (int)$fechaActual->format('N');

            foreach ($horarios as $horario) {
                if ((int)$horario['dia_semana'] === $diaSemana) {
                    $fechaStr = $fechaActual->format('Y-m-d');

                    $existe = $this->where('grupo_id', $grupoId)
                        ->where('fecha_programada', $fechaStr)
                        ->where('hora_inicio', $horario['hora_inicio'])
                        ->first();

                    if (!$existe) {
                        $data = [
                            'grupo_id' => $grupoId,
                            'curso_id' => $grupo['curso_id'],
                            'fecha_programada' => $fechaStr,
                            'hora_inicio' => $horario['hora_inicio'],
                            'hora_fin' => $horario['hora_fin'],
                            'estado' => 'planificada',
                            'creada_por' => $creadoPor
                        ];

                        if ($this->insert($data)) {
                            $exitosos++;
                        } else {
                            $fallidos++;
                        }
                    }
                }
            }

            $fechaActual->modify('+1 day');
        }

        return [
            'exitosos' => $exitosos,
            'fallidos' => $fallidos,
            'mensaje' => "Se generaron {$exitosos} sesiones exitosamente"
        ];
    }

    public function getEstudiantesParaAsistencia(int $sesionId)
    {
        $sesion = $this->find($sesionId);

        if (!$sesion) {
            return [];
        }

        return $this->db->table('inscripciones')
            ->select('inscripciones.*, 
                estudiantes.id as estudiante_id,
                estudiantes.codigo_estudiante,
                usuarios.nombre_completo as estudiante_nombre,
                usuarios.email as estudiante_email')
            ->join('estudiantes', 'estudiantes.id = inscripciones.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->where('inscripciones.grupo_id', $sesion['grupo_id'])
            ->where('inscripciones.estado', 'activo')
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }
}