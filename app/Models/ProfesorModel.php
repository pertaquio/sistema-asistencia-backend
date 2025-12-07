<?php

namespace App\Models;

use CodeIgniter\Model;

class ProfesorModel extends Model
{
    protected $table = 'profesores';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'usuario_id',
        'codigo_profesor',
        'departamento',
        'telefono',
        'direccion',
        'especialidad',
        'fecha_contratacion'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = '';
    protected $updatedField = '';
    protected $deletedField = '';

    protected $validationRules = [
        'usuario_id' => 'required|is_natural_no_zero',
        'codigo_profesor' => 'permit_empty|is_unique[profesores.codigo_profesor,id,{id}]',
        'departamento' => 'permit_empty|max_length[100]',
        'telefono' => 'permit_empty|max_length[20]',
        'direccion' => 'permit_empty|max_length[255]',
        'especialidad' => 'permit_empty|max_length[150]',
        'fecha_contratacion' => 'permit_empty|valid_date'
    ];

    protected $validationMessages = [
        'usuario_id' => [
            'required' => 'El ID de usuario es requerido',
            'is_natural_no_zero' => 'El ID de usuario debe ser válido'
        ],
        'codigo_profesor' => [
            'is_unique' => 'Este código de profesor ya está registrado'
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

    public function getProfesorCompleto(int $id)
    {
        return $this->select('profesores.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre, roles.nombre as rol_nombre')
            ->join('usuarios', 'usuarios.id = profesores.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('profesores.id', $id)
            ->first();
    }

    public function getProfesorPorCodigo(string $codigo)
    {
        return $this->select('profesores.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, usuarios.estado_id, estados.nombre as estado_nombre, roles.nombre as rol_nombre')
            ->join('usuarios', 'usuarios.id = profesores.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('profesores.codigo_profesor', $codigo)
            ->first();
    }

    public function getGrupos(int $profesorId)
    {
        return $this->db->table('grupos')
            ->select('grupos.*, cursos.nombre as curso_nombre, cursos.codigo as curso_codigo, COUNT(inscripciones.id) as total_estudiantes')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->join('inscripciones', 'inscripciones.grupo_id = grupos.id', 'left')
            ->where('grupos.profesor_id', $profesorId)
            ->groupBy('grupos.id')
            ->orderBy('grupos.anio_academico', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getHorarios(int $profesorId)
    {
        return $this->db->table('horarios')
            ->select('horarios.*, grupos.nombre as grupo_nombre, cursos.nombre as curso_nombre')
            ->join('grupos', 'grupos.id = horarios.grupo_id')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->where('grupos.profesor_id', $profesorId)
            ->orderBy('horarios.dia_semana', 'ASC')
            ->orderBy('horarios.hora_inicio', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getSesiones(int $profesorId, ?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->db->table('sesiones')
            ->select('sesiones.*, grupos.nombre as grupo_nombre, cursos.nombre as curso_nombre')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->where('grupos.profesor_id', $profesorId);

        if ($fechaInicio) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        return $builder->orderBy('sesiones.fecha_programada', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getEstadisticas(int $profesorId)
    {
        $grupos = $this->db->table('grupos')
            ->where('profesor_id', $profesorId)
            ->countAllResults();

        $estudiantes = $this->db->table('inscripciones')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->where('grupos.profesor_id', $profesorId)
            ->where('inscripciones.estado', 'activo')
            ->countAllResults();

        $sesiones = $this->db->table('sesiones')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->where('grupos.profesor_id', $profesorId)
            ->countAllResults();

        return [
            'total_grupos' => $grupos,
            'total_estudiantes' => $estudiantes,
            'total_sesiones' => $sesiones
        ];
    }
}