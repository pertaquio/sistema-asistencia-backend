<?php

namespace App\Models;

use CodeIgniter\Model;

class GrupoModel extends Model
{
    protected $table = 'grupos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'curso_id',
        'nombre',
        'anio_academico',
        'profesor_id',
        'extra'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'extra' => 'json',
        'anio_academico' => 'int'
    ];
    protected array $castHandlers = [];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = '';
    protected $updatedField = '';
    protected $deletedField = '';

    protected $validationRules = [
        'curso_id' => 'required|is_natural_no_zero',
        'nombre' => 'required|min_length[3]|max_length[80]',
        'anio_academico' => 'required|integer|min_length[4]|max_length[4]',
        'profesor_id' => 'permit_empty|is_natural_no_zero'
    ];

    protected $validationMessages = [
        'curso_id' => [
            'required' => 'El curso es requerido',
            'is_natural_no_zero' => 'El ID del curso debe ser válido'
        ],
        'nombre' => [
            'required' => 'El nombre del grupo es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede exceder 80 caracteres'
        ],
        'anio_academico' => [
            'required' => 'El año académico es requerido',
            'integer' => 'El año académico debe ser un número',
            'min_length' => 'El año académico debe tener 4 dígitos',
            'max_length' => 'El año académico debe tener 4 dígitos'
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

    public function getGrupoCompleto(int $id)
    {
        $grupo = $this->select('grupos.*, cursos.nombre as curso_nombre, cursos.codigo as curso_codigo, usuarios.nombre_completo as profesor_nombre, profesores.codigo_profesor')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios', 'usuarios.id = profesores.usuario_id', 'left')
            ->where('grupos.id', $id)
            ->first();

        if ($grupo && isset($grupo['extra']) && is_string($grupo['extra'])) {
            $grupo['extra'] = json_decode($grupo['extra'], true);
        }

        return $grupo;
    }

    public function getEstudiantes(int $grupoId, ?string $estadoInscripcion = null)
    {
        $builder = $this->db->table('inscripciones')
            ->select('estudiantes.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre, inscripciones.estado as estado_inscripcion, inscripciones.inscrito_en')
            ->join('estudiantes', 'estudiantes.id = inscripciones.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('inscripciones.grupo_id', $grupoId);

        if ($estadoInscripcion) {
            $builder->where('inscripciones.estado', $estadoInscripcion);
        }

        return $builder->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getHorarios(int $grupoId)
    {
        return $this->db->table('horarios')
            ->where('grupo_id', $grupoId)
            ->orderBy('dia_semana', 'ASC')
            ->orderBy('hora_inicio', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSesiones(int $grupoId, ?string $fechaInicio = null, ?string $fechaFin = null, ?string $estado = null)
    {
        $builder = $this->db->table('sesiones')
            ->select('sesiones.*, cursos.nombre as curso_nombre, usuarios.nombre_completo as creador_nombre')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('usuarios', 'usuarios.id = sesiones.creada_por', 'left')
            ->where('sesiones.grupo_id', $grupoId);

        if ($fechaInicio) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        if ($estado) {
            $builder->where('sesiones.estado', $estado);
        }

        return $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getAsistencias(int $grupoId, ?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->db->table('asistencias')
            ->select('asistencias.*, sesiones.fecha_programada, sesiones.hora_inicio, sesiones.hora_fin, estudiantes.codigo_estudiante, usuarios.nombre_completo as estudiante_nombre')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->join('estudiantes', 'estudiantes.id = asistencias.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
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

    public function getEstadisticas(int $grupoId)
    {
        $totalEstudiantes = $this->db->table('inscripciones')
            ->where('grupo_id', $grupoId)
            ->where('estado', 'activo')
            ->countAllResults();

        $totalSesiones = $this->db->table('sesiones')
            ->where('grupo_id', $grupoId)
            ->countAllResults();

        $sesionesPorEstado = $this->db->table('sesiones')
            ->select('estado, COUNT(*) as total')
            ->where('grupo_id', $grupoId)
            ->groupBy('estado')
            ->get()
            ->getResultArray();

        $estadosSesiones = [
            'planificada' => 0,
            'realizada' => 0,
            'cancelada' => 0
        ];

        foreach ($sesionesPorEstado as $estado) {
            $estadosSesiones[$estado['estado']] = (int)$estado['total'];
        }

        $totalAsistencias = $this->db->table('asistencias')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.grupo_id', $grupoId)
            ->countAllResults();

        $asistenciasPorEstado = $this->db->table('asistencias')
            ->select('asistencias.estado, COUNT(*) as total')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.grupo_id', $grupoId)
            ->groupBy('asistencias.estado')
            ->get()
            ->getResultArray();

        $estadosAsistencia = [
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0
        ];

        foreach ($asistenciasPorEstado as $asistencia) {
            $estadosAsistencia[$asistencia['estado']] = (int)$asistencia['total'];
        }

        $porcentajeAsistencia = 0;
        if ($totalAsistencias > 0) {
            $asistenciasValidas = $estadosAsistencia['presente'] + $estadosAsistencia['tarde'];
            $porcentajeAsistencia = round(($asistenciasValidas / $totalAsistencias) * 100, 2);
        }

        return [
            'total_estudiantes' => $totalEstudiantes,
            'total_sesiones' => $totalSesiones,
            'sesiones_por_estado' => $estadosSesiones,
            'total_asistencias' => $totalAsistencias,
            'asistencias_por_estado' => $estadosAsistencia,
            'porcentaje_asistencia' => $porcentajeAsistencia
        ];
    }

    public function asignarProfesor(int $grupoId, ?int $profesorId)
    {
        return $this->update($grupoId, ['profesor_id' => $profesorId]);
    }
}