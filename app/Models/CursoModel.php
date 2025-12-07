<?php

namespace App\Models;

use CodeIgniter\Model;

class CursoModel extends Model
{
    protected $table = 'cursos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'codigo',
        'nombre',
        'descripcion'
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
        'codigo' => 'permit_empty|is_unique[cursos.codigo,id,{id}]|max_length[50]',
        'nombre' => 'required|min_length[3]|max_length[150]',
        'descripcion' => 'permit_empty'
    ];

    protected $validationMessages = [
        'codigo' => [
            'is_unique' => 'Este código de curso ya está registrado',
            'max_length' => 'El código no puede exceder 50 caracteres'
        ],
        'nombre' => [
            'required' => 'El nombre del curso es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede exceder 150 caracteres'
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

    public function getCursoPorCodigo(string $codigo)
    {
        return $this->where('codigo', $codigo)->first();
    }

    public function getGrupos(int $cursoId, ?int $anioAcademico = null)
    {
        $builder = $this->db->table('grupos')
            ->select('grupos.*, COUNT(inscripciones.id) as total_estudiantes, usuarios.nombre_completo as profesor_nombre')
            ->join('inscripciones', 'inscripciones.grupo_id = grupos.id', 'left')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios', 'usuarios.id = profesores.usuario_id', 'left')
            ->where('grupos.curso_id', $cursoId)
            ->groupBy('grupos.id');

        if ($anioAcademico) {
            $builder->where('grupos.anio_academico', $anioAcademico);
        }

        return $builder->orderBy('grupos.anio_academico', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getSesiones(int $cursoId, ?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->db->table('sesiones')
            ->select('sesiones.*, grupos.nombre as grupo_nombre')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->where('sesiones.curso_id', $cursoId);

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

    public function getEstadisticas(int $cursoId)
    {
        $grupos = $this->db->table('grupos')
            ->where('curso_id', $cursoId)
            ->countAllResults();

        $estudiantes = $this->db->table('inscripciones')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->where('grupos.curso_id', $cursoId)
            ->where('inscripciones.estado', 'activo')
            ->countAllResults();

        $sesiones = $this->db->table('sesiones')
            ->where('curso_id', $cursoId)
            ->countAllResults();

        $sesionesPorEstado = $this->db->table('sesiones')
            ->select('estado, COUNT(*) as total')
            ->where('curso_id', $cursoId)
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

        return [
            'total_grupos' => $grupos,
            'total_estudiantes' => $estudiantes,
            'total_sesiones' => $sesiones,
            'sesiones_por_estado' => $estadosSesiones
        ];
    }

    public function getEstudiantesInscritos(int $cursoId)
    {
        return $this->db->table('estudiantes')
            ->select('estudiantes.*, usuarios.nombre_usuario, usuarios.email, usuarios.nombre_completo, grupos.nombre as grupo_nombre, inscripciones.estado as estado_inscripcion')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('inscripciones', 'inscripciones.estudiante_id = estudiantes.id')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->where('grupos.curso_id', $cursoId)
            ->where('inscripciones.estado', 'activo')
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }
}