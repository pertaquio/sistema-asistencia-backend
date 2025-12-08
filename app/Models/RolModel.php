<?php

namespace App\Models;

use CodeIgniter\Model;

class RolModel extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
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
        'nombre' => 'required|min_length[3]|max_length[50]|is_unique[roles.nombre,id,{id}]',
        'descripcion' => 'permit_empty|max_length[255]'
    ];

    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre del rol es requerido',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede exceder 50 caracteres',
            'is_unique' => 'Este nombre de rol ya está registrado'
        ],
        'descripcion' => [
            'max_length' => 'La descripción no puede exceder 255 caracteres'
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

    public function getUsuariosPorRol(int $rolId)
    {
        return $this->db->table('usuarios')
            ->select('usuarios.*, estados.nombre as estado_nombre')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('usuarios.rol_id', $rolId)
            ->orderBy('usuarios.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getEstadisticas(int $rolId)
    {
        $totalUsuarios = $this->db->table('usuarios')
            ->where('rol_id', $rolId)
            ->countAllResults();

        $usuariosPorEstado = $this->db->table('usuarios')
            ->select('estados.nombre as estado, COUNT(*) as total')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('usuarios.rol_id', $rolId)
            ->groupBy('usuarios.estado_id')
            ->get()
            ->getResultArray();

        $estadisticas = [
            'total_usuarios' => $totalUsuarios,
            'activos' => 0,
            'inactivos' => 0,
            'suspendidos' => 0
        ];

        foreach ($usuariosPorEstado as $estado) {
            $nombreEstado = strtolower($estado['estado']);
            if ($nombreEstado === 'activo') {
                $estadisticas['activos'] = (int)$estado['total'];
            } elseif ($nombreEstado === 'inactivo') {
                $estadisticas['inactivos'] = (int)$estado['total'];
            } elseif ($nombreEstado === 'suspendido') {
                $estadisticas['suspendidos'] = (int)$estado['total'];
            }
        }

        return $estadisticas;
    }

    const ADMINISTRADOR = 1;
    const PROFESOR = 2;
    const ESTUDIANTE = 3;
}