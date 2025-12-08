<?php

namespace App\Controllers;

use App\Models\EstudianteModel;
use App\Models\ProfesorModel;
use App\Models\GrupoModel;
use App\Models\CursoModel;
use App\Models\SesionModel;
use App\Models\AsistenciaModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Dashboard extends ResourceController
{
    protected $format = 'json';
    protected $estudianteModel;
    protected $profesorModel;
    protected $grupoModel;
    protected $cursoModel;
    protected $sesionModel;
    protected $asistenciaModel;

    public function __construct()
    {
        $this->estudianteModel = new EstudianteModel();
        $this->profesorModel = new ProfesorModel();
        $this->grupoModel = new GrupoModel();
        $this->cursoModel = new CursoModel();
        $this->sesionModel = new SesionModel();
        $this->asistenciaModel = new AsistenciaModel();
    }

    public function index()
    {
        $totalEstudiantes = $this->estudianteModel
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->where('usuarios.estado_id', 1)
            ->countAllResults();

        $totalProfesores = $this->profesorModel
            ->join('usuarios', 'usuarios.id = profesores.usuario_id')
            ->where('usuarios.estado_id', 1)
            ->countAllResults();

        $totalGrupos = $this->grupoModel->countAllResults();
        
        $totalCursos = $this->cursoModel->countAllResults();

        $totalSesionesHoy = $this->sesionModel
            ->where('fecha_programada', date('Y-m-d'))
            ->countAllResults();

        $totalAsistenciasHoy = $this->asistenciaModel
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.fecha_programada', date('Y-m-d'))
            ->countAllResults();

        $asistenciasPorEstado = $this->asistenciaModel
            ->select('asistencias.estado, COUNT(*) as total')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.fecha_programada', date('Y-m-d'))
            ->groupBy('asistencias.estado')
            ->findAll();

        $estadosAsistencia = [
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0
        ];

        foreach ($asistenciasPorEstado as $estado) {
            $estadosAsistencia[$estado['estado']] = (int)$estado['total'];
        }

        $porcentajeAsistenciaHoy = 0;
        if ($totalAsistenciasHoy > 0) {
            $asistenciasValidas = $estadosAsistencia['presente'] + $estadosAsistencia['tarde'];
            $porcentajeAsistenciaHoy = round(($asistenciasValidas / $totalAsistenciasHoy) * 100, 2);
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'totales' => [
                    'estudiantes' => $totalEstudiantes,
                    'profesores' => $totalProfesores,
                    'grupos' => $totalGrupos,
                    'cursos' => $totalCursos,
                    'sesiones_hoy' => $totalSesionesHoy,
                    'asistencias_hoy' => $totalAsistenciasHoy
                ],
                'asistencia_hoy' => [
                    'presente' => $estadosAsistencia['presente'],
                    'ausente' => $estadosAsistencia['ausente'],
                    'tarde' => $estadosAsistencia['tarde'],
                    'justificado' => $estadosAsistencia['justificado'],
                    'total' => $totalAsistenciasHoy,
                    'porcentaje' => $porcentajeAsistenciaHoy
                ]
            ]
        ], ResponseInterface::HTTP_OK);
    }

    public function asistenciasSemana()
    {
        $fechaInicio = date('Y-m-d', strtotime('monday this week'));
        $fechaFin = date('Y-m-d', strtotime('sunday this week'));

        $asistenciasPorDia = $this->asistenciaModel
            ->select("DATE(sesiones.fecha_programada) as fecha, 
                      asistencias.estado, 
                      COUNT(*) as total")
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.fecha_programada >=', $fechaInicio)
            ->where('sesiones.fecha_programada <=', $fechaFin)
            ->groupBy('DATE(sesiones.fecha_programada), asistencias.estado')
            ->orderBy('sesiones.fecha_programada', 'ASC')
            ->findAll();

        $resultado = [];
        $fechaActual = new \DateTime($fechaInicio);
        $fechaFinal = new \DateTime($fechaFin);

        while ($fechaActual <= $fechaFinal) {
            $fechaStr = $fechaActual->format('Y-m-d');
            $resultado[$fechaStr] = [
                'fecha' => $fechaStr,
                'dia_nombre' => $this->getNombreDia($fechaActual->format('N')),
                'presente' => 0,
                'ausente' => 0,
                'tarde' => 0,
                'justificado' => 0,
                'total' => 0
            ];
            $fechaActual->modify('+1 day');
        }

        foreach ($asistenciasPorDia as $asistencia) {
            if (isset($resultado[$asistencia['fecha']])) {
                $resultado[$asistencia['fecha']][$asistencia['estado']] = (int)$asistencia['total'];
                $resultado[$asistencia['fecha']]['total'] += (int)$asistencia['total'];
            }
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'asistencias' => array_values($resultado)
            ]
        ], ResponseInterface::HTTP_OK);
    }

    public function asistenciasMes()
    {
        $mes = $this->request->getVar('mes') ?? date('m');
        $anio = $this->request->getVar('anio') ?? date('Y');

        $fechaInicio = date('Y-m-01', strtotime("$anio-$mes-01"));
        $fechaFin = date('Y-m-t', strtotime("$anio-$mes-01"));

        $asistenciasPorDia = $this->asistenciaModel
            ->select("DATE(sesiones.fecha_programada) as fecha, 
                      asistencias.estado, 
                      COUNT(*) as total")
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.fecha_programada >=', $fechaInicio)
            ->where('sesiones.fecha_programada <=', $fechaFin)
            ->groupBy('DATE(sesiones.fecha_programada), asistencias.estado')
            ->orderBy('sesiones.fecha_programada', 'ASC')
            ->findAll();

        $asistenciasPorEstado = $this->asistenciaModel
            ->select('asistencias.estado, COUNT(*) as total')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.fecha_programada >=', $fechaInicio)
            ->where('sesiones.fecha_programada <=', $fechaFin)
            ->groupBy('asistencias.estado')
            ->findAll();

        $resumenEstados = [
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0,
            'total' => 0
        ];

        foreach ($asistenciasPorEstado as $estado) {
            $resumenEstados[$estado['estado']] = (int)$estado['total'];
            $resumenEstados['total'] += (int)$estado['total'];
        }

        $porcentajeAsistencia = 0;
        if ($resumenEstados['total'] > 0) {
            $asistenciasValidas = $resumenEstados['presente'] + $resumenEstados['tarde'];
            $porcentajeAsistencia = round(($asistenciasValidas / $resumenEstados['total']) * 100, 2);
        }

        $asistenciasPorDiaAgrupadas = [];
        foreach ($asistenciasPorDia as $asistencia) {
            $fecha = $asistencia['fecha'];
            if (!isset($asistenciasPorDiaAgrupadas[$fecha])) {
                $asistenciasPorDiaAgrupadas[$fecha] = [
                    'fecha' => $fecha,
                    'presente' => 0,
                    'ausente' => 0,
                    'tarde' => 0,
                    'justificado' => 0,
                    'total' => 0
                ];
            }
            $asistenciasPorDiaAgrupadas[$fecha][$asistencia['estado']] = (int)$asistencia['total'];
            $asistenciasPorDiaAgrupadas[$fecha]['total'] += (int)$asistencia['total'];
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'mes' => (int)$mes,
                'anio' => (int)$anio,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'resumen' => [
                    'presente' => $resumenEstados['presente'],
                    'ausente' => $resumenEstados['ausente'],
                    'tarde' => $resumenEstados['tarde'],
                    'justificado' => $resumenEstados['justificado'],
                    'total' => $resumenEstados['total'],
                    'porcentaje_asistencia' => $porcentajeAsistencia
                ],
                'asistencias_por_dia' => array_values($asistenciasPorDiaAgrupadas)
            ]
        ], ResponseInterface::HTTP_OK);
    }

    public function gruposMasActivos()
    {
        $limite = $this->request->getVar('limite') ?? 10;

        $grupos = $this->asistenciaModel
            ->select('grupos.id, 
                      grupos.nombre as grupo_nombre, 
                      cursos.nombre as curso_nombre,
                      COUNT(DISTINCT asistencias.sesion_id) as total_sesiones,
                      COUNT(asistencias.id) as total_asistencias,
                      SUM(CASE WHEN asistencias.estado IN ("presente", "tarde") THEN 1 ELSE 0 END) as asistencias_validas')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->groupBy('grupos.id')
            ->orderBy('total_sesiones', 'DESC')
            ->limit($limite)
            ->findAll();

        foreach ($grupos as &$grupo) {
            $total = (int)$grupo['total_asistencias'];
            $validas = (int)$grupo['asistencias_validas'];
            $grupo['porcentaje_asistencia'] = $total > 0 ? round(($validas / $total) * 100, 2) : 0;
        }

        return $this->respond([
            'status' => 'success',
            'data' => $grupos
        ], ResponseInterface::HTTP_OK);
    }

    public function estudiantesConMasAusencias()
    {
        $limite = $this->request->getVar('limite') ?? 10;
        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');

        $builder = $this->asistenciaModel
            ->select('estudiantes.id,
                      estudiantes.codigo_estudiante,
                      usuarios.nombre_completo as estudiante_nombre,
                      COUNT(CASE WHEN asistencias.estado = "ausente" THEN 1 END) as total_ausencias,
                      COUNT(CASE WHEN asistencias.estado = "tarde" THEN 1 END) as total_tardes,
                      COUNT(asistencias.id) as total_asistencias')
            ->join('estudiantes', 'estudiantes.id = asistencias.estudiante_id')
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id');

        if ($fechaInicio) {
            $builder->where('sesiones.fecha_programada >=', $fechaInicio);
        }

        if ($fechaFin) {
            $builder->where('sesiones.fecha_programada <=', $fechaFin);
        }

        $estudiantes = $builder
            ->groupBy('estudiantes.id')
            ->orderBy('total_ausencias', 'DESC')
            ->limit($limite)
            ->findAll();

        foreach ($estudiantes as &$estudiante) {
            $total = (int)$estudiante['total_asistencias'];
            $ausencias = (int)$estudiante['total_ausencias'];
            $estudiante['porcentaje_ausencias'] = $total > 0 ? round(($ausencias / $total) * 100, 2) : 0;
        }

        return $this->respond([
            'status' => 'success',
            'data' => $estudiantes
        ], ResponseInterface::HTTP_OK);
    }

    public function proximasSesiones()
    {
        $limite = $this->request->getVar('limite') ?? 10;
        $fechaDesde = $this->request->getVar('fecha_desde') ?? date('Y-m-d');

        $sesiones = $this->sesionModel
            ->select('sesiones.*, 
                      grupos.nombre as grupo_nombre,
                      cursos.nombre as curso_nombre,
                      profesores_usuarios.nombre_completo as profesor_nombre')
            ->join('grupos', 'grupos.id = sesiones.grupo_id')
            ->join('cursos', 'cursos.id = sesiones.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios as profesores_usuarios', 'profesores_usuarios.id = profesores.usuario_id', 'left')
            ->where('sesiones.fecha_programada >=', $fechaDesde)
            ->where('sesiones.estado', 'planificada')
            ->orderBy('sesiones.fecha_programada', 'ASC')
            ->orderBy('sesiones.hora_inicio', 'ASC')
            ->limit($limite)
            ->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $sesiones
        ], ResponseInterface::HTTP_OK);
    }

    public function resumenGeneral()
    {
        $fechaInicio = $this->request->getVar('fecha_inicio') ?? date('Y-m-01');
        $fechaFin = $this->request->getVar('fecha_fin') ?? date('Y-m-d');

        $totalEstudiantes = $this->estudianteModel
            ->join('usuarios', 'usuarios.id = estudiantes.usuario_id')
            ->where('usuarios.estado_id', 1)
            ->countAllResults();

        $totalSesiones = $this->sesionModel
            ->where('fecha_programada >=', $fechaInicio)
            ->where('fecha_programada <=', $fechaFin)
            ->countAllResults();

        $sesionesPorEstado = $this->sesionModel
            ->select('estado, COUNT(*) as total')
            ->where('fecha_programada >=', $fechaInicio)
            ->where('fecha_programada <=', $fechaFin)
            ->groupBy('estado')
            ->findAll();

        $estadosSesiones = [
            'planificada' => 0,
            'realizada' => 0,
            'cancelada' => 0
        ];

        foreach ($sesionesPorEstado as $estado) {
            $estadosSesiones[$estado['estado']] = (int)$estado['total'];
        }

        $asistenciasPorEstado = $this->asistenciaModel
            ->select('asistencias.estado, COUNT(*) as total')
            ->join('sesiones', 'sesiones.id = asistencias.sesion_id')
            ->where('sesiones.fecha_programada >=', $fechaInicio)
            ->where('sesiones.fecha_programada <=', $fechaFin)
            ->groupBy('asistencias.estado')
            ->findAll();

        $estadosAsistencia = [
            'presente' => 0,
            'ausente' => 0,
            'tarde' => 0,
            'justificado' => 0,
            'total' => 0
        ];

        foreach ($asistenciasPorEstado as $estado) {
            $estadosAsistencia[$estado['estado']] = (int)$estado['total'];
            $estadosAsistencia['total'] += (int)$estado['total'];
        }

        $porcentajeAsistencia = 0;
        if ($estadosAsistencia['total'] > 0) {
            $asistenciasValidas = $estadosAsistencia['presente'] + $estadosAsistencia['tarde'];
            $porcentajeAsistencia = round(($asistenciasValidas / $estadosAsistencia['total']) * 100, 2);
        }

        return $this->respond([
            'status' => 'success',
            'data' => [
                'periodo' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ],
                'estudiantes' => [
                    'total_activos' => $totalEstudiantes
                ],
                'sesiones' => [
                    'total' => $totalSesiones,
                    'planificada' => $estadosSesiones['planificada'],
                    'realizada' => $estadosSesiones['realizada'],
                    'cancelada' => $estadosSesiones['cancelada']
                ],
                'asistencias' => [
                    'total' => $estadosAsistencia['total'],
                    'presente' => $estadosAsistencia['presente'],
                    'ausente' => $estadosAsistencia['ausente'],
                    'tarde' => $estadosAsistencia['tarde'],
                    'justificado' => $estadosAsistencia['justificado'],
                    'porcentaje_asistencia' => $porcentajeAsistencia
                ]
            ]
        ], ResponseInterface::HTTP_OK);
    }

    private function getNombreDia(int $dia): string
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