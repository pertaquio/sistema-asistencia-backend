<?php

namespace App\Models;

use CodeIgniter\Model;

class AsistenciaModel extends Model
{
    protected $table = 'asistencias';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'sesion_id',
        'estudiante_id',
        'estado',
        'hora_registro',
        'motivo_id',
        'nota',
        'registrado_por'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'registrado_en';
    protected $updatedField = '';
    protected $deletedField = '';

    protected $validationRules = [
        'sesion_id' => 'required|is_natural_no_zero',
        'estudiante_id' => 'required|is_natural_no_zero',
        'estado' => 'required|in_list[presente,ausente,tarde,justificado]',
        'hora_registro' => 'permit_empty',
        'motivo_id' => 'permit_empty|is_natural_no_zero',
        'nota' => 'permit_empty|max_length[255]'
    ];

    protected $validationMessages = [
        'sesion_id' => [
            'required' => 'La sesión es requerida',
            'is_natural_no_zero' => 'El ID de la sesión debe ser válido'
        ],
        'estudiante_id' => [
            'required' => 'El estudiante es requerido',
            'is_natural_no_zero' => 'El ID del estudiante debe ser válido'
        ],
        'estado' => [
            'required' => 'El estado es requerido',
            'in_list' => 'El estado debe ser presente, ausente, tarde o justificado'
        ],
        'nota' => [
            'max_length' => 'La nota no puede exceder 255 caracteres'
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

    public function getAsistenciaCompleta(int $id)
    {
        return $this->select('asistencias.*, 
            sesiones.fecha_programada,
            sesiones.hora_inicio as sesion_hora_inicio,
            sesiones.hora_fin as sesion_hora_fin,
            sesiones.estado as sesion_estado,
            estudiantes.codigo_estudiante,
            usuarios.nombre_completo as estudiante_nombre,
            usuarios.email as estudiante_email,
            grupos.nombre as grupo_nombre,
            cursos.nombre as curso_nombre,
            motivos_ausencia.codigo as motivo_codigo,
            motivos_ausencia.descripcion as motivo_descripcion,
            registrador_usuarios.nombre_completo as registrador_nombre')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->join('estudiantes', 'estudiantes.id = asistencias.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('motivos_ausencia', 'motivos_ausencia.id = asistencias.motivo_id', 'left')
            ->join('usuarios as registrador_usuarios', 'registrador_usuarios.id = asistencias.registrado_por', 'left')
            ->where('asistencias.id', $id)
            ->first();
    }

    public function verificarAsistenciaExistente(int $sesionId, int $estudianteId): bool
    {
        $asistencia = $this->where('sesion_id', $sesionId)
            ->where('estudiante_id', $estudianteId)
            ->first();

        return $asistencia !== null;
    }

    public function registrarMasivo(int $sesionId, array $asistencias, int $registradoPor): array
    {
        $resultados = [
            'exitosos' => 0,
            'fallidos' => 0,
            'detalles' => []
        ];

        foreach ($asistencias as $asistencia) {
            if (!isset($asistencia['estudiante_id']) || !isset($asistencia['estado'])) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $asistencia['estudiante_id'] ?? null,
                    'estado' => 'error',
                    'mensaje' => 'Datos incompletos'
                ];
                continue;
            }

            $estudianteId = $asistencia['estudiante_id'];

            if ($this->verificarAsistenciaExistente($sesionId, $estudianteId)) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'estado' => 'error',
                    'mensaje' => 'Ya existe un registro de asistencia para este estudiante'
                ];
                continue;
            }

            $data = [
                'sesion_id' => $sesionId,
                'estudiante_id' => $estudianteId,
                'estado' => $asistencia['estado'],
                'hora_registro' => $asistencia['hora_registro'] ?? date('Y-m-d H:i:s'),
                'motivo_id' => $asistencia['motivo_id'] ?? null,
                'nota' => $asistencia['nota'] ?? null,
                'registrado_por' => $registradoPor
            ];

            $asistenciaId = $this->insert($data);

            if ($asistenciaId) {
                $resultados['exitosos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'asistencia_id' => $asistenciaId,
                    'estado' => 'success',
                    'mensaje' => 'Asistencia registrada exitosamente'
                ];
            } else {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'estado' => 'error',
                    'mensaje' => 'Error al registrar la asistencia'
                ];
            }
        }

        return $resultados;
    }

    public function cambiarEstado(int $id, string $estado, ?int $motivoId = null, ?string $nota = null): bool
    {
        if (!in_array($estado, ['presente', 'ausente', 'tarde', 'justificado'])) {
            return false;
        }

        $data = ['estado' => $estado];

        if ($motivoId !== null) {
            $data['motivo_id'] = $motivoId;
        }

        if ($nota !== null) {
            $data['nota'] = $nota;
        }

        return $this->update($id, $data);
    }

    public function marcarPresente(int $sesionId, int $estudianteId, int $registradoPor): ?int
    {
        if ($this->verificarAsistenciaExistente($sesionId, $estudianteId)) {
            return null;
        }

        $data = [
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'estado' => 'presente',
            'hora_registro' => date('Y-m-d H:i:s'),
            'registrado_por' => $registradoPor
        ];

        return $this->insert($data);
    }

    public function marcarAusente(int $sesionId, int $estudianteId, int $registradoPor, ?int $motivoId = null, ?string $nota = null): ?int
    {
        if ($this->verificarAsistenciaExistente($sesionId, $estudianteId)) {
            return null;
        }

        $data = [
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'estado' => 'ausente',
            'hora_registro' => date('Y-m-d H:i:s'),
            'motivo_id' => $motivoId,
            'nota' => $nota,
            'registrado_por' => $registradoPor
        ];

        return $this->insert($data);
    }

    public function marcarTarde(int $sesionId, int $estudianteId, int $registradoPor, ?string $nota = null): ?int
    {
        if ($this->verificarAsistenciaExistente($sesionId, $estudianteId)) {
            return null;
        }

        $data = [
            'sesion_id' => $sesionId,
            'estudiante_id' => $estudianteId,
            'estado' => 'tarde',
            'hora_registro' => date('Y-m-d H:i:s'),
            'nota' => $nota,
            'registrado_por' => $registradoPor
        ];

        return $this->insert($data);
    }

    public function justificarAusencia(int $id, int $motivoId, ?string $nota = null): bool
    {
        $asistencia = $this->find($id);

        if (!$asistencia || $asistencia['estado'] !== 'ausente') {
            return false;
        }

        return $this->update($id, [
            'estado' => 'justificado',
            'motivo_id' => $motivoId,
            'nota' => $nota
        ]);
    }

    public function getReportePorEstudiante(int $estudianteId, ?string $fechaInicio = null, ?string $fechaFin = null, ?int $grupoId = null)
    {
        $builder = $this->select('asistencias.*, 
            sesiones.fecha_programada,
            sesiones.hora_inicio,
            sesiones.hora_fin,
            grupos.nombre as grupo_nombre,
            cursos.nombre as curso_nombre,
            motivos_ausencia.descripcion as motivo_descripcion')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('motivos_ausencia', 'motivos_ausencia.id = asistencias.motivo_id', 'left')
            ->where('asistencias.estudiante_id', $estudianteId);

        if ($fechaInicio) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        if ($grupoId) {
            $builder->where('sesiones.grupo_id', $grupoId);
        }

        return $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getReportePorGrupo(int $grupoId, ?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->select('asistencias.*, 
            sesiones.fecha_programada,
            sesiones.hora_inicio,
            sesiones.hora_fin,
            estudiantes.codigo_estudiante,
            usuarios.nombre_completo as estudiante_nombre,
            motivos_ausencia.descripcion as motivo_descripcion')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->join('estudiantes', 'estudiantes.id = asistencias.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('motivos_ausencia', 'motivos_ausencia.id = asistencias.motivo_id', 'left')
            ->where('sesiones.grupo_id', $grupoId);

        if ($fechaInicio) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        return $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getEstadisticasPorEstudiante(int $estudianteId, ?int $grupoId = null)
    {
        $builder = $this->select('asistencias.estado, COUNT(*) as total')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('asistencias.estudiante_id', $estudianteId)
            ->groupBy('asistencias.estado');

        if ($grupoId) {
            $builder->where('sesiones.grupo_id', $grupoId);
        }

        $resultados = $builder->get()->getResultArray();

        $estadisticas = [
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0,
            'total' => 0,
            'porcentaje_asistencia' => 0
        ];

        foreach ($resultados as $resultado) {
            $estadisticas[$resultado['estado']] = (int)$resultado['total'];
            $estadisticas['total'] += (int)$resultado['total'];
        }

        if ($estadisticas['total'] > 0) {
            $asistenciasValidas = $estadisticas['presente'] + $estadisticas['tarde'];
            $estadisticas['porcentaje_asistencia'] = round(($asistenciasValidas / $estadisticas['total']) * 100, 2);
        }

        return $estadisticas;
    }

    public function getEstadisticasPorGrupo(int $grupoId)
    {
        $totalEstudiantes = $this->db->table('inscripciones')
            ->where('grupo_id', $grupoId)
            ->where('estado', 'activo')
            ->countAllResults();

        $asistenciasPorEstado = $this->select('asistencias.estado, COUNT(*) as total')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.grupo_id', $grupoId)
            ->groupBy('asistencias.estado')
            ->get()
            ->getResultArray();

        $estadisticas = [
            'total_estudiantes' => $totalEstudiantes,
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0,
            'total_registros' => 0,
            'porcentaje_asistencia' => 0
        ];

        foreach ($asistenciasPorEstado as $asistencia) {
            $estadisticas[$asistencia['estado']] = (int)$asistencia['total'];
            $estadisticas['total_registros'] += (int)$asistencia['total'];
        }

        if ($estadisticas['total_registros'] > 0) {
            $asistenciasValidas = $estadisticas['presente'] + $estadisticas['tarde'];
            $estadisticas['porcentaje_asistencia'] = round(($asistenciasValidas / $estadisticas['total_registros']) * 100, 2);
        }

        return $estadisticas;
    }
}