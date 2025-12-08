<?php

namespace App\Models;

use CodeIgniter\Model;

class MotivoAusenciaModel extends Model
{
    protected $table = 'motivos_ausencia';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'codigo',
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
        'codigo' => 'permit_empty|is_unique[motivos_ausencia.codigo,id,{id}]|max_length[30]',
        'descripcion' => 'permit_empty|max_length[150]'
    ];

    protected $validationMessages = [
        'codigo' => [
            'is_unique' => 'Este código ya está registrado',
            'max_length' => 'El código no puede exceder 30 caracteres'
        ],
        'descripcion' => [
            'max_length' => 'La descripción no puede exceder 150 caracteres'
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

    public function getMotivoPorCodigo(string $codigo)
    {
        return $this->where('codigo', $codigo)->first();
    }
}