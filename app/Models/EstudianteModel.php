<?php

namespace App\Models;

use CodeIgniter\Model;

class EstudianteModel extends Model
{
    protected $table = 'estudiantes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
    'usuario_id',
    'codigo_estudiante',
    'fecha_nacimiento',
    'genero',
    'telefono',
    'direccion',
    'nombre_tutor'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'extra' => 'json',
    ];
    protected array $castHandlers = [];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = '';
    protected $updatedField = '';
    protected $deletedField = '';

    protected $validationRules = [
        'usuario_id' => 'required|is_natural_no_zero',
        'codigo_estudiante' => 'permit_empty|is_unique[estudiantes.codigo_estudiante,id,{id}]',
        'fecha_nacimiento' => 'permit_empty|valid_date',
        'genero' => 'required|in_list[M,F,O]'
    ];

    protected $validationMessages = [
        'usuario_id' => [
            'required' => 'El ID de usuario es requerido',
            'is_natural_no_zero' => 'El ID de usuario debe ser válido'
        ],
        'codigo_estudiante' => [
            'is_unique' => 'Este código de estudiante ya está registrado'
        ],
        'genero' => [
            'required' => 'El género es requerido',
            'in_list' => 'El género debe ser M, F u O'
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

    public function getEstudianteCompleto(int $id)
    {
        $estudiante = $this->select('estudiantes.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre, roles.nombre as rol_nombre')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('estudiantes.id', $id)
            ->first();

        if ($estudiante && isset($estudiante['extra']) && is_string($estudiante['extra'])) {
            $estudiante['extra'] = json_decode($estudiante['extra'], true);
        }

        return $estudiante;
    }

    public function getEstudiantePorCodigo(string $codigo)
    {
        $estudiante = $this->select('estudiantes.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre, roles.nombre as rol_nombre')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('estudiantes.codigo_estudiante', $codigo)
            ->first();

        if ($estudiante && isset($estudiante['extra']) && is_string($estudiante['extra'])) {
            $estudiante['extra'] = json_decode($estudiante['extra'], true);
        }

        return $estudiante;
    }

    public function getInscripciones(int $estudianteId)
    {
        return $this->db->table('inscripciones')
            ->select('inscripciones.*, grupos.nombre as grupo_nombre, grupos.anio_academico, cursos.nombre as curso_nombre, cursos.codigo as curso_codigo')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->where('inscripciones.estudiante_id', $estudianteId)
            ->orderBy('inscripciones.inscrito_en', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getAsistencias(int $estudianteId, ?string $fechaInicio = null, ?string $fechaFin = null, ?int $grupoId = null, ?string $estado = null)
    {
        $builder = $this->db->table('asistencias')
            ->select('asistencias.*, sesiones.fecha_programada, sesiones.hora_inicio, sesiones.hora_fin, grupos.nombre as grupo_nombre, cursos.nombre as curso_nombre, motivos_ausencia.descripcion as motivo_descripcion')
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

        if ($estado) {
            $builder->where('asistencias.estado', $estado);
        }

        return $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getEstadisticasAsistencia(int $estudianteId, ?int $grupoId = null)
    {
        $builder = $this->db->table('asistencias')
            ->select('asistencias.estado, COUNT(*) as total')
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
}