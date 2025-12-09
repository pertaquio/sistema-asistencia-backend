<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditoriaModel extends Model
{
    protected $table = 'auditorias';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'tabla',
        'accion',
        'registro_id',
        'datos_antiguos',
        'datos_nuevos',
        'usuario_id',
        'ip_address'
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

    public function getAuditoriaCompleta(int $id)
    {
        return $this->select('auditorias.*, 
            usuarios.nombre_usuario,
            usuarios.nombre_completo as usuario_nombre,
            usuarios.email as usuario_email')
            ->join('usuarios', 'usuarios.id = auditorias.usuario_id', 'left')
            ->where('auditorias.id', $id)
            ->first();
    }

    public function getAuditoriasConFiltros(
        ?string $tabla = null,
        ?string $accion = null,
        ?int $usuarioId = null,
        ?int $registroId = null,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        int $page = 1,
        int $perPage = 10
    ) {
        $builder = $this->select('auditorias.*, 
            usuarios.nombre_usuario,
            usuarios.nombre_completo as usuario_nombre,
            usuarios.email as usuario_email')
            ->join('usuarios', 'usuarios.id = auditorias.usuario_id', 'left');

        if ($tabla) {
            $builder->where('auditorias.tabla', $tabla);
        }

        if ($accion) {
            $builder->where('auditorias.accion', $accion);
        }

        if ($usuarioId) {
            $builder->where('auditorias.usuario_id', $usuarioId);
        }

        if ($registroId) {
            $builder->where('auditorias.registro_id', $registroId);
        }

        if ($fechaInicio) {
            $builder->where('auditorias.creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('auditorias.creado_en <=', $fechaFin);
        }

        return $builder->orderBy('auditorias.creado_en', 'DESC')
            ->paginate($perPage, 'default', $page);
    }

    public function getAuditoriasPorUsuario(int $usuarioId, ?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->select('auditorias.*, 
            usuarios.nombre_usuario,
            usuarios.nombre_completo as usuario_nombre')
            ->join('usuarios', 'usuarios.id = auditorias.usuario_id', 'left')
            ->where('auditorias.usuario_id', $usuarioId);

        if ($fechaInicio) {
            $builder->where('auditorias.creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('auditorias.creado_en <=', $fechaFin);
        }

        return $builder->orderBy('auditorias.creado_en', 'DESC')
            ->findAll();
    }

    public function getAuditoriasPorTabla(string $tabla, ?int $registroId = null, ?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->select('auditorias.*, 
            usuarios.nombre_usuario,
            usuarios.nombre_completo as usuario_nombre')
            ->join('usuarios', 'usuarios.id = auditorias.usuario_id', 'left')
            ->where('auditorias.tabla', $tabla);

        if ($registroId) {
            $builder->where('auditorias.registro_id', $registroId);
        }

        if ($fechaInicio) {
            $builder->where('auditorias.creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('auditorias.creado_en <=', $fechaFin);
        }

        return $builder->orderBy('auditorias.creado_en', 'DESC')
            ->findAll();
    }

    public function getHistorialRegistro(string $tabla, int $registroId)
    {
        return $this->select('auditorias.*, 
            usuarios.nombre_usuario,
            usuarios.nombre_completo as usuario_nombre')
            ->join('usuarios', 'usuarios.id = auditorias.usuario_id', 'left')
            ->where('auditorias.tabla', $tabla)
            ->where('auditorias.registro_id', $registroId)
            ->orderBy('auditorias.creado_en', 'ASC')
            ->findAll();
    }

    public function getEstadisticasGenerales(?string $fechaInicio = null, ?string $fechaFin = null)
    {
        $builder = $this->db->table('auditorias');

        if ($fechaInicio) {
            $builder->where('creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('creado_en <=', $fechaFin);
        }

        $totalRegistros = $builder->countAllResults(false);

        $porAccion = $this->db->table('auditorias')
            ->select('accion, COUNT(*) as total')
            ->groupBy('accion');

        if ($fechaInicio) {
            $porAccion->where('creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $porAccion->where('creado_en <=', $fechaFin);
        }

        $porAccionResultado = $porAccion->get()->getResultArray();

        $accionesProcesadas = [
            'INSERT' => 0,
            'UPDATE' => 0,
            'DELETE' => 0
        ];

        foreach ($porAccionResultado as $row) {
            $accionesProcesadas[$row['accion']] = (int)$row['total'];
        }

        $porTabla = $this->db->table('auditorias')
            ->select('tabla, COUNT(*) as total')
            ->groupBy('tabla')
            ->orderBy('total', 'DESC');

        if ($fechaInicio) {
            $porTabla->where('creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $porTabla->where('creado_en <=', $fechaFin);
        }

        $porTablaResultado = $porTabla->get()->getResultArray();

        $usuariosMasActivos = $this->db->table('auditorias')
            ->select('auditorias.usuario_id, usuarios.nombre_completo, usuarios.nombre_usuario, COUNT(*) as total_acciones')
            ->join('usuarios', 'usuarios.id = auditorias.usuario_id', 'left')
            ->groupBy('auditorias.usuario_id')
            ->orderBy('total_acciones', 'DESC')
            ->limit(10);

        if ($fechaInicio) {
            $usuariosMasActivos->where('auditorias.creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $usuariosMasActivos->where('auditorias.creado_en <=', $fechaFin);
        }

        $usuariosMasActivosResultado = $usuariosMasActivos->get()->getResultArray();

        return [
            'total_registros' => $totalRegistros,
            'por_accion' => $accionesProcesadas,
            'por_tabla' => $porTablaResultado,
            'usuarios_mas_activos' => $usuariosMasActivosResultado
        ];
    }

    public function getActividadPorDia(?string $fechaInicio = null, ?string $fechaFin = null, int $dias = 30)
    {
        $builder = $this->db->table('auditorias')
            ->select('DATE(creado_en) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha', 'DESC')
            ->limit($dias);

        if ($fechaInicio) {
            $builder->where('creado_en >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('creado_en <=', $fechaFin);
        }

        return $builder->get()->getResultArray();
    }

    public function getActividadPorHora(?string $fecha = null)
    {
        $builder = $this->db->table('auditorias')
            ->select('HOUR(creado_en) as hora, COUNT(*) as total')
            ->groupBy('hora')
            ->orderBy('hora', 'ASC');

        if ($fecha) {
            $builder->where('DATE(creado_en)', $fecha);
        }

        return $builder->get()->getResultArray();
    }

    public function getTablasAuditadas()
    {
        return $this->db->table('auditorias')
            ->select('tabla')
            ->distinct()
            ->orderBy('tabla', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getAccionesDisponibles()
    {
        return [
            ['accion' => 'INSERT', 'descripcion' => 'Creación'],
            ['accion' => 'UPDATE', 'descripcion' => 'Actualización'],
            ['accion' => 'DELETE', 'descripcion' => 'Eliminación']
        ];
    }

    public function compararVersiones(int $auditoriaId1, int $auditoriaId2)
    {
        $auditoria1 = $this->find($auditoriaId1);
        $auditoria2 = $this->find($auditoriaId2);

        if (!$auditoria1 || !$auditoria2) {
            return null;
        }

        if ($auditoria1['tabla'] !== $auditoria2['tabla'] || $auditoria1['registro_id'] !== $auditoria2['registro_id']) {
            return null;
        }

        $datos1 = json_decode($auditoria1['datos_nuevos'] ?: $auditoria1['datos_antiguos'], true);
        $datos2 = json_decode($auditoria2['datos_nuevos'] ?: $auditoria2['datos_antiguos'], true);

        $diferencias = [];

        $todasLasClaves = array_unique(array_merge(array_keys($datos1 ?: []), array_keys($datos2 ?: [])));

        foreach ($todasLasClaves as $clave) {
            $valor1 = $datos1[$clave] ?? null;
            $valor2 = $datos2[$clave] ?? null;

            if ($valor1 !== $valor2) {
                $diferencias[$clave] = [
                    'version_1' => $valor1,
                    'version_2' => $valor2
                ];
            }
        }

        return [
            'auditoria_1' => $auditoria1,
            'auditoria_2' => $auditoria2,
            'diferencias' => $diferencias
        ];
    }
}