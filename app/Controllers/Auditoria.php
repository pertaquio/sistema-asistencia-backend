<?php

namespace App\Controllers;

use App\Models\AuditoriaModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Auditoria extends ResourceController
{
    protected $modelName = 'App\Models\AuditoriaModel';
    protected $format = 'json';
    protected $auditoriaModel;

    public function __construct()
    {
        $this->auditoriaModel = new AuditoriaModel();
    }

    public function index()
    {
        $page = (int)($this->request->getVar('page') ?? 1);
        $perPage = (int)($this->request->getVar('per_page') ?? 10);
        
        $tabla = $this->request->getVar('tabla');
        $tabla = ($tabla && $tabla !== '') ? $tabla : null;
        
        $accion = $this->request->getVar('accion');
        $accion = ($accion && $accion !== '') ? $accion : null;
        
        $usuarioId = $this->request->getVar('usuario_id');
        $usuarioId = ($usuarioId && $usuarioId !== '') ? (int)$usuarioId : null;
        
        $registroId = $this->request->getVar('registro_id');
        $registroId = ($registroId && $registroId !== '') ? (int)$registroId : null;
        
        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaInicio = ($fechaInicio && $fechaInicio !== '') ? $fechaInicio : null;
        
        $fechaFin = $this->request->getVar('fecha_fin');
        $fechaFin = ($fechaFin && $fechaFin !== '') ? $fechaFin : null;

        $auditorias = $this->auditoriaModel->getAuditoriasConFiltros(
            $tabla,
            $accion,
            $usuarioId,
            $registroId,
            $fechaInicio,
            $fechaFin,
            $page,
            $perPage
        );

        $pager = $this->auditoriaModel->pager;

        foreach ($auditorias as &$auditoria) {
            if ($auditoria['datos_antiguos']) {
                $auditoria['datos_antiguos'] = json_decode($auditoria['datos_antiguos'], true);
            }
            if ($auditoria['datos_nuevos']) {
                $auditoria['datos_nuevos'] = json_decode($auditoria['datos_nuevos'], true);
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => $auditorias,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'per_page' => $pager->getPerPage(),
                'total' => $pager->getTotal(),
                'total_pages' => $pager->getPageCount()
            ]
        ], ResponseInterface::HTTP_OK);
    }
    public function show($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de auditoría requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $auditoria = $this->auditoriaModel->getAuditoriaCompleta($id);

        if (!$auditoria) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Auditoría no encontrada'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        if ($auditoria['datos_antiguos']) {
            $auditoria['datos_antiguos'] = json_decode($auditoria['datos_antiguos'], true);
        }
        if ($auditoria['datos_nuevos']) {
            $auditoria['datos_nuevos'] = json_decode($auditoria['datos_nuevos'], true);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $auditoria
        ], ResponseInterface::HTTP_OK);
    }

    public function porUsuario($usuarioId = null)
    {
        if (!$usuarioId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de usuario requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaInicio = ($fechaInicio && $fechaInicio !== '') ? $fechaInicio : null;
        
        $fechaFin = $this->request->getVar('fecha_fin');
        $fechaFin = ($fechaFin && $fechaFin !== '') ? $fechaFin : null;

        $auditorias = $this->auditoriaModel->getAuditoriasPorUsuario((int)$usuarioId, $fechaInicio, $fechaFin);

        foreach ($auditorias as &$auditoria) {
            if ($auditoria['datos_antiguos']) {
                $auditoria['datos_antiguos'] = json_decode($auditoria['datos_antiguos'], true);
            }
            if ($auditoria['datos_nuevos']) {
                $auditoria['datos_nuevos'] = json_decode($auditoria['datos_nuevos'], true);
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => $auditorias
        ], ResponseInterface::HTTP_OK);
    }

    public function porTabla($tabla = null)
    {
        if (!$tabla) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Nombre de tabla requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $registroId = $this->request->getVar('registro_id');
        $registroId = ($registroId && $registroId !== '') ? (int)$registroId : null;
        
        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaInicio = ($fechaInicio && $fechaInicio !== '') ? $fechaInicio : null;
        
        $fechaFin = $this->request->getVar('fecha_fin');
        $fechaFin = ($fechaFin && $fechaFin !== '') ? $fechaFin : null;

        $auditorias = $this->auditoriaModel->getAuditoriasPorTabla($tabla, $registroId, $fechaInicio, $fechaFin);

        foreach ($auditorias as &$auditoria) {
            if ($auditoria['datos_antiguos']) {
                $auditoria['datos_antiguos'] = json_decode($auditoria['datos_antiguos'], true);
            }
            if ($auditoria['datos_nuevos']) {
                $auditoria['datos_nuevos'] = json_decode($auditoria['datos_nuevos'], true);
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => $auditorias
        ], ResponseInterface::HTTP_OK);
    }

    public function historial()
    {
        $tabla = $this->request->getVar('tabla');
        $registroId = $this->request->getVar('registro_id');

        if (!$tabla || !$registroId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Tabla y registro_id son requeridos'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $historial = $this->auditoriaModel->getHistorialRegistro($tabla, $registroId);

        foreach ($historial as &$auditoria) {
            if ($auditoria['datos_antiguos']) {
                $auditoria['datos_antiguos'] = json_decode($auditoria['datos_antiguos'], true);
            }
            if ($auditoria['datos_nuevos']) {
                $auditoria['datos_nuevos'] = json_decode($auditoria['datos_nuevos'], true);
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => $historial
        ], ResponseInterface::HTTP_OK);
    }

    public function estadisticas()
    {
        $fechaInicio = $this->request->getVar('fecha_inicio') ?? null;
        $fechaFin = $this->request->getVar('fecha_fin') ?? null;

        $estadisticas = $this->auditoriaModel->getEstadisticasGenerales($fechaInicio, $fechaFin);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }

    public function actividadPorDia()
    {
        $fechaInicio = $this->request->getVar('fecha_inicio') ?? null;
        $fechaFin = $this->request->getVar('fecha_fin') ?? null;
        $dias = $this->request->getVar('dias') ?? 30;

        $actividad = $this->auditoriaModel->getActividadPorDia($fechaInicio, $fechaFin, $dias);

        return $this->respond([
            'status' => 'success',
            'data' => $actividad
        ], ResponseInterface::HTTP_OK);
    }

    public function actividadPorHora()
    {
        $fecha = $this->request->getVar('fecha') ?? null;

        $actividad = $this->auditoriaModel->getActividadPorHora($fecha);

        return $this->respond([
            'status' => 'success',
            'data' => $actividad
        ], ResponseInterface::HTTP_OK);
    }

    public function tablas()
    {
        $tablas = $this->auditoriaModel->getTablasAuditadas();

        return $this->respond([
            'status' => 'success',
            'data' => $tablas
        ], ResponseInterface::HTTP_OK);
    }

    public function acciones()
    {
        $acciones = $this->auditoriaModel->getAccionesDisponibles();

        return $this->respond([
            'status' => 'success',
            'data' => $acciones
        ], ResponseInterface::HTTP_OK);
    }

    public function comparar()
    {
        $auditoriaId1 = $this->request->getVar('auditoria_id_1');
        $auditoriaId2 = $this->request->getVar('auditoria_id_2');

        if (!$auditoriaId1 || !$auditoriaId2) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Se requieren dos IDs de auditoría para comparar'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $comparacion = $this->auditoriaModel->compararVersiones($auditoriaId1, $auditoriaId2);

        if (!$comparacion) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No se pueden comparar las auditorías. Verifique que existan y correspondan al mismo registro.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        if ($comparacion['auditoria_1']['datos_antiguos']) {
            $comparacion['auditoria_1']['datos_antiguos'] = json_decode($comparacion['auditoria_1']['datos_antiguos'], true);
        }
        if ($comparacion['auditoria_1']['datos_nuevos']) {
            $comparacion['auditoria_1']['datos_nuevos'] = json_decode($comparacion['auditoria_1']['datos_nuevos'], true);
        }
        if ($comparacion['auditoria_2']['datos_antiguos']) {
            $comparacion['auditoria_2']['datos_antiguos'] = json_decode($comparacion['auditoria_2']['datos_antiguos'], true);
        }
        if ($comparacion['auditoria_2']['datos_nuevos']) {
            $comparacion['auditoria_2']['datos_nuevos'] = json_decode($comparacion['auditoria_2']['datos_nuevos'], true);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $comparacion
        ], ResponseInterface::HTTP_OK);
    }
}