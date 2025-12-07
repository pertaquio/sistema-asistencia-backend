<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'rol_id',
        'nombre_usuario',
        'email',
        'contrasena_hash',
        'nombre_completo',
        'estado_id'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'estado_id' => 'int',
    ];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'creado_en';
    protected $updatedField = 'actualizado_en';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'nombre_usuario' => 'required|valid_email|min_length[3]|max_length[80]|is_unique[usuarios.nombre_usuario,id,{id}]',
        'email' => 'permit_empty|valid_email|max_length[150]|is_unique[usuarios.email,id,{id}]',
        'contrasena_hash' => 'required',
        'rol_id' => 'required|is_natural_no_zero',
        'estado_id' => 'required|is_natural_no_zero'
    ];

    protected $validationMessages = [
        'nombre_usuario' => [
            'required' => 'El nombre de usuario es requerido',
            'valid_email' => 'El nombre de usuario debe ser un correo electrónico válido',
            'min_length' => 'El nombre de usuario debe tener al menos 3 caracteres',
            'max_length' => 'El nombre de usuario no puede exceder 80 caracteres',
            'is_unique' => 'Este nombre de usuario ya está registrado'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = ['hashPassword'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['hashPassword'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['contrasena_hash']) && !empty($data['data']['contrasena_hash'])) {
            if (strlen($data['data']['contrasena_hash']) < 60) {
                $data['data']['contrasena_hash'] = password_hash($data['data']['contrasena_hash'], PASSWORD_DEFAULT);
            }
        }
        return $data;
    }

    public function getUsuarioConRol(int $id)
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, estados.nombre as estado_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('usuarios.id', $id)
            ->first();
    }

    public function getUsuarioPorNombreUsuario(string $nombreUsuario)
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, estados.nombre as estado_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('usuarios.nombre_usuario', $nombreUsuario)
            ->first();
    }

    public function getUsuarioPorEmail(string $email)
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, estados.nombre as estado_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->join('estados', 'estados.id = usuarios.estado_id')
            ->where('usuarios.email', $email)
            ->first();
    }

    public function verificarCredenciales(string $identificador, string $contrasena)
    {
        $usuario = $this->where('nombre_usuario', $identificador)
            ->orWhere('email', $identificador)
            ->first();

        if (!$usuario) {
            return false;
        }

        if (password_verify($contrasena, $usuario['contrasena_hash'])) {
            return $usuario;
        }

        return false;
    }
}