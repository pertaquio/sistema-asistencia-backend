<?php

namespace App\Models;

use CodeIgniter\Model;

class TokenSesionModel extends Model
{
    protected $table = 'tokens_sesion';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'usuario_id',
        'token',
        'refresh_token',
        'expira_en',
        'revocado'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'revocado' => 'int',
    ];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'creado_en';
    protected $updatedField = '';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [];
    protected $validationMessages = [];
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

    public function guardarToken(int $usuarioId, string $token, string $refreshToken, int $expiracion): bool
    {
        $data = [
            'usuario_id' => $usuarioId,
            'token' => hash('sha256', $token),
            'refresh_token' => hash('sha256', $refreshToken),
            'expira_en' => date('Y-m-d H:i:s', time() + $expiracion),
            'revocado' => 0
        ];

        return $this->insert($data) !== false;
    }

    public function validarToken(string $token): ?array
    {
        $hashedToken = hash('sha256', $token);
        
        return $this->where('token', $hashedToken)
            ->where('revocado', 0)
            ->where('expira_en >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function validarRefreshToken(string $refreshToken): ?array
    {
        $hashedToken = hash('sha256', $refreshToken);
        
        return $this->where('refresh_token', $hashedToken)
            ->where('revocado', 0)
            ->where('expira_en >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function revocarToken(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        
        return $this->where('token', $hashedToken)
            ->set(['revocado' => 1])
            ->update();
    }

    public function revocarTokensPorUsuario(int $usuarioId): bool
    {
        return $this->where('usuario_id', $usuarioId)
            ->set(['revocado' => 1])
            ->update();
    }

    public function limpiarTokensExpirados(): int
    {
        return $this->where('expira_en <', date('Y-m-d H:i:s'))
            ->delete();
    }
}