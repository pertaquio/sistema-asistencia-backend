<?php

namespace App\Models;

use CodeIgniter\Model;

class HorarioModel extends Model
{
    protected $table = 'horarios';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'grupo_id',
        'curso_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'ubicacion',
        'estado_id'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'grupo_id' => 'int',
        'dia_semana' => 'int',
        'estado_id' => 'int',
    ];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'creado_en';
    protected $updatedField = 'actualizado_en';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'grupo_id' => 'required|is_natural_no_zero',
        'dia_semana' => 'required|is_natural_no_zero|in_list[1,2,3,4,5,6,7]',
        'hora_inicio' => 'required|regex_match[/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/]',
        'hora_fin' => 'required|regex_match[/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/]',
        'estado_id' => 'required|is_natural_no_zero'
    ];

    protected $validationMessages = [
        'grupo_id' => [
            'required' => 'El grupo es requerido',
            'is_natural_no_zero' => 'El grupo debe ser válido'
        ],
        'aula_id' => [
            'required' => 'El aula es requerida',
            'is_natural_no_zero' => 'El aula debe ser válida'
        ],
        'curso_id' => [
            'required' => 'El curso es requerido',
            'is_natural_no_zero' => 'El curso debe ser válido'
        ],
        'dia_semana' => [
            'required' => 'El día de la semana es requerido',
            'is_natural_no_zero' => 'El día debe ser válido',
            'in_list' => 'El día debe estar entre 1 (Lunes) y 7 (Domingo)'
        ],
        'hora_inicio' => [
            'required' => 'La hora de inicio es requerida',
            'regex_match' => 'La hora de inicio debe tener formato HH:MM o HH:MM:SS'
        ],
        'hora_fin' => [
            'required' => 'La hora de fin es requerida',
            'regex_match' => 'La hora de fin debe tener formato HH:MM o HH:MM:SS'
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

    public function getHorarioCompleto(int $id)
    {
        return $this->select('horarios.*, 
                grupos.nombre as grupo_nombre,
                estados.nombre as estado_nombre')
            ->join('grupos', 'grupos.id = horarios.grupo_id')
            ->join('estados', 'estados.id = horarios.estado_id')
            ->where('horarios.id', $id)
            ->first();
    }

    public function getHorariosPorGrupo(int $grupoId)
    {
        return $this->select('horarios.*, 
                estados.nombre as estado_nombre')
            ->join('estados', 'estados.id = horarios.estado_id')
            ->where('horarios.grupo_id', $grupoId)
            ->orderBy('horarios.dia_semana', 'ASC')
            ->orderBy('horarios.hora_inicio', 'ASC')
            ->findAll();
    }

    public function getHorariosPorAula(int $aulaId)
    {
        return [];
    }

    public function getHorariosPorDia(int $diaSemana)
    {
        return $this->select('horarios.*, 
                grupos.nombre as grupo_nombre,
                estados.nombre as estado_nombre')
            ->join('grupos', 'grupos.id = horarios.grupo_id')
            ->join('estados', 'estados.id = horarios.estado_id')
            ->where('horarios.dia_semana', $diaSemana)
            ->orderBy('horarios.hora_inicio', 'ASC')
            ->findAll();
    }

    public function verificarConflictoAula(int $aulaId, int $diaSemana, string $horaInicio, string $horaFin, ?int $horarioId = null): bool
    {
        return false;
    }

    public function verificarConflictoGrupo(int $grupoId, int $diaSemana, string $horaInicio, string $horaFin, ?int $horarioId = null): bool
    {
        $builder = $this->where('grupo_id', $grupoId)
            ->where('dia_semana', $diaSemana)
            ->where('estado_id', EstadoModel::ACTIVO)
            ->groupStart()
                ->groupStart()
                    ->where('hora_inicio <=', $horaInicio)
                    ->where('hora_fin >', $horaInicio)
                ->groupEnd()
                ->orGroupStart()
                    ->where('hora_inicio <', $horaFin)
                    ->where('hora_fin >=', $horaFin)
                ->groupEnd()
                ->orGroupStart()
                    ->where('hora_inicio >=', $horaInicio)
                    ->where('hora_fin <=', $horaFin)
                ->groupEnd()
            ->groupEnd();

        if ($horarioId) {
            $builder->where('id !=', $horarioId);
        }

        return $builder->countAllResults() > 0;
    }

    public function getNombreDia(int $dia): string
    {
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];

        return $dias[$dia] ?? 'Desconocido';
    }
}