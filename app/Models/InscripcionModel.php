<?php

namespace App\Models;

use CodeIgniter\Model;

class InscripcionModel extends Model
{
    protected $table = 'inscripciones';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'estudiante_id',
        'grupo_id',
        'estado'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'inscrito_en';
    protected $updatedField = '';
    protected $deletedField = '';

    protected $validationRules = [
        'estudiante_id' => 'required|is_natural_no_zero',
        'grupo_id' => 'required|is_natural_no_zero',
        'estado' => 'required|in_list[activo,inactivo,graduado]'
    ];

    protected $validationMessages = [
        'estudiante_id' => [
            'required' => 'El estudiante es requerido',
            'is_natural_no_zero' => 'El ID del estudiante debe ser válido'
        ],
        'grupo_id' => [
            'required' => 'El grupo es requerido',
            'is_natural_no_zero' => 'El ID del grupo debe ser válido'
        ],
        'estado' => [
            'required' => 'El estado es requerido',
            'in_list' => 'El estado debe ser activo, inactivo o graduado'
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

    public function getInscripcionCompleta(int $id)
    {
        return $this->select('inscripciones.*, 
            estudiantes.codigo_estudiante,
            usuarios.nombre_usuario as estudiante_usuario,
            usuarios.email as estudiante_email,
            usuarios.nombre_completo as estudiante_nombre,
            grupos.nombre as grupo_nombre,
            grupos.anio_academico,
            cursos.nombre as curso_nombre,
            cursos.codigo as curso_codigo,
            profesores_usuarios.nombre_completo as profesor_nombre')
            ->join('estudiantes', 'estudiantes.id = inscripciones.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios as profesores_usuarios', 'profesores_usuarios.id = profesores.usuario_id', 'left')
            ->where('inscripciones.id', $id)
            ->first();
    }

    public function verificarInscripcionExistente(int $estudianteId, int $grupoId): bool
    {
        $inscripcion = $this->where('estudiante_id', $estudianteId)
            ->where('grupo_id', $grupoId)
            ->first();

        return $inscripcion !== null;
    }

    public function verificarCapacidadGrupo(int $grupoId): array
    {
        $grupo = $this->db->table('grupos')
            ->select('capacidad_maxima')
            ->where('id', $grupoId)
            ->get()
            ->getRowArray();

        if (!$grupo || $grupo['capacidad_maxima'] === null) {
            return ['tiene_capacidad' => true, 'capacidad_maxima' => null, 'inscritos' => 0];
        }

        $totalInscritos = $this->where('grupo_id', $grupoId)
            ->where('estado', 'activo')
            ->countAllResults();

        return [
            'tiene_capacidad' => $totalInscritos < $grupo['capacidad_maxima'],
            'capacidad_maxima' => $grupo['capacidad_maxima'],
            'inscritos' => $totalInscritos,
            'disponibles' => $grupo['capacidad_maxima'] - $totalInscritos
        ];
    }

    public function getInscripcionesPorEstudiante(int $estudianteId, ?string $estado = null)
    {
        $builder = $this->select('inscripciones.*, 
            grupos.nombre as grupo_nombre,
            grupos.anio_academico,
            cursos.nombre as curso_nombre,
            cursos.codigo as curso_codigo,
            profesores_usuarios.nombre_completo as profesor_nombre')
            ->join('grupos', 'grupos.id = inscripciones.grupo_id')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios as profesores_usuarios', 'profesores_usuarios.id = profesores.usuario_id', 'left')
            ->where('inscripciones.estudiante_id', $estudianteId);

        if ($estado) {
            $builder->where('inscripciones.estado', $estado);
        }

        return $builder->orderBy('inscripciones.inscrito_en', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getInscripcionesPorGrupo(int $grupoId, ?string $estado = null)
    {
        $builder = $this->select('inscripciones.*, 
            estudiantes.codigo_estudiante,
            usuarios.nombre_usuario as estudiante_usuario,
            usuarios.email as estudiante_email,
            usuarios.nombre_completo as estudiante_nombre,
            estados.nombre as estudiante_estado')
            ->join('estudiantes', 'estudiantes.id = inscripciones.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('inscripciones.grupo_id', $grupoId);

        if ($estado) {
            $builder->where('inscripciones.estado', $estado);
        }

        return $builder->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['activo', 'inactivo', 'graduado'])) {
            return false;
        }

        return $this->update($id, ['estado' => $estado]);
    }

    public function inscripcionesMasivas(int $grupoId, array $estudiantesIds): array
    {
        $resultados = [
            'exitosos' => 0,
            'fallidos' => 0,
            'detalles' => []
        ];

        $capacidad = $this->verificarCapacidadGrupo($grupoId);

        foreach ($estudiantesIds as $estudianteId) {
            if (!$capacidad['tiene_capacidad']) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'estado' => 'error',
                    'mensaje' => 'Grupo sin capacidad disponible'
                ];
                continue;
            }

            if ($this->verificarInscripcionExistente($estudianteId, $grupoId)) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'estado' => 'error',
                    'mensaje' => 'El estudiante ya está inscrito en este grupo'
                ];
                continue;
            }

            $data = [
                'estudiante_id' => $estudianteId,
                'grupo_id' => $grupoId,
                'estado' => 'activo'
            ];

            $inscripcionId = $this->insert($data);

            if ($inscripcionId) {
                $resultados['exitosos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'inscripcion_id' => $inscripcionId,
                    'estado' => 'success',
                    'mensaje' => 'Inscripción exitosa'
                ];

                $capacidad = $this->verificarCapacidadGrupo($grupoId);
            } else {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'estudiante_id' => $estudianteId,
                    'estado' => 'error',
                    'mensaje' => 'Error al crear la inscripción'
                ];
            }
        }

        return $resultados;
    }
}