<?php

use CodeIgniter\Router\RouteCollection;

$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    
    $routes->group('auth', function($routes) {
        $routes->post('login', 'Auth::login');
        $routes->post('refresh', 'Auth::refresh');
        $routes->post('logout', 'Auth::logout', ['filter' => 'auth']);
        $routes->get('me', 'Auth::me', ['filter' => 'auth']);
    });

    $routes->group('usuarios', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Usuario::index');
        $routes->get('(:num)', 'Usuario::show/$1');
        $routes->post('', 'Usuario::create');
        $routes->put('(:num)', 'Usuario::update/$1');
        $routes->delete('(:num)', 'Usuario::delete/$1');
        $routes->patch('(:num)/estado', 'Usuario::cambiarEstado/$1');
        $routes->patch('(:num)/contrasena', 'Usuario::cambiarContrasena/$1');
    });

    $routes->group('roles', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Rol::index');
        $routes->get('(:num)', 'Rol::show/$1');
        $routes->post('', 'Rol::create');
        $routes->put('(:num)', 'Rol::update/$1');
        $routes->delete('(:num)', 'Rol::delete/$1');
        $routes->get('(:num)/usuarios', 'Rol::getUsuarios/$1');
        $routes->get('(:num)/estadisticas', 'Rol::getEstadisticas/$1');
    });

    $routes->group('estudiantes', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Estudiante::index');
        $routes->get('(:num)', 'Estudiante::show/$1');
        $routes->post('', 'Estudiante::create');
        $routes->put('(:num)', 'Estudiante::update/$1');
        $routes->delete('(:num)', 'Estudiante::delete/$1');
        $routes->get('codigo/(:segment)', 'Estudiante::getByCodigo/$1');
        $routes->get('(:num)/inscripciones', 'Estudiante::getInscripciones/$1');
        $routes->get('(:num)/asistencias', 'Estudiante::getAsistencias/$1');
        $routes->patch('(:num)/estado', 'Estudiante::cambiarEstado/$1');
    });

    $routes->group('profesores', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Profesor::index');
        $routes->get('(:num)', 'Profesor::show/$1');
        $routes->post('', 'Profesor::create');
        $routes->put('(:num)', 'Profesor::update/$1');
        $routes->delete('(:num)', 'Profesor::delete/$1');
        $routes->get('codigo/(:segment)', 'Profesor::getByCodigo/$1');
        $routes->get('(:num)/grupos', 'Profesor::getGrupos/$1');
        $routes->get('(:num)/horarios', 'Profesor::getHorarios/$1');
        $routes->get('(:num)/sesiones', 'Profesor::getSesiones/$1');
        $routes->get('(:num)/estadisticas', 'Profesor::getEstadisticas/$1');
        $routes->patch('(:num)/estado', 'Profesor::cambiarEstado/$1');
    });

    $routes->group('cursos', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Curso::index');
        $routes->get('(:num)', 'Curso::show/$1');
        $routes->post('', 'Curso::create');
        $routes->put('(:num)', 'Curso::update/$1');
        $routes->delete('(:num)', 'Curso::delete/$1');
        $routes->get('codigo/(:segment)', 'Curso::getByCodigo/$1');
        $routes->get('(:num)/grupos', 'Curso::getGrupos/$1');
        $routes->get('(:num)/sesiones', 'Curso::getSesiones/$1');
        $routes->get('(:num)/estadisticas', 'Curso::getEstadisticas/$1');
        $routes->get('(:num)/estudiantes', 'Curso::getEstudiantes/$1');
    });

    $routes->group('grupos', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Grupo::index');
        $routes->get('(:num)', 'Grupo::show/$1');
        $routes->post('', 'Grupo::create');
        $routes->put('(:num)', 'Grupo::update/$1');
        $routes->delete('(:num)', 'Grupo::delete/$1');
        $routes->get('(:num)/estudiantes', 'Grupo::getEstudiantes/$1');
        $routes->get('(:num)/horarios', 'Grupo::getHorarios/$1');
        $routes->get('(:num)/sesiones', 'Grupo::getSesiones/$1');
        $routes->get('(:num)/asistencias', 'Grupo::getAsistencias/$1');
        $routes->get('(:num)/estadisticas', 'Grupo::getEstadisticas/$1');
        $routes->patch('(:num)/profesor', 'Grupo::asignarProfesor/$1');
    });

    $routes->group('inscripciones', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Inscripcion::index');
        $routes->get('(:num)', 'Inscripcion::show/$1');
        $routes->post('', 'Inscripcion::create');
        $routes->put('(:num)', 'Inscripcion::update/$1');
        $routes->delete('(:num)', 'Inscripcion::delete/$1');
        $routes->patch('(:num)/estado', 'Inscripcion::cambiarEstado/$1');
        $routes->post('masiva', 'Inscripcion::inscripcionMasiva');
        $routes->get('grupo/(:num)/capacidad', 'Inscripcion::verificarCapacidad/$1');
    });

    $routes->group('sesiones', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Sesion::index');
        $routes->get('(:num)', 'Sesion::show/$1');
        $routes->post('', 'Sesion::create');
        $routes->put('(:num)', 'Sesion::update/$1');
        $routes->delete('(:num)', 'Sesion::delete/$1');
        $routes->get('(:num)/asistencias', 'Sesion::getAsistencias/$1');
        $routes->get('(:num)/estadisticas', 'Sesion::getEstadisticas/$1');
        $routes->patch('(:num)/estado', 'Sesion::cambiarEstado/$1');
        $routes->patch('(:num)/iniciar', 'Sesion::iniciarSesion/$1');
        $routes->patch('(:num)/cancelar', 'Sesion::cancelarSesion/$1');
        $routes->get('fecha/(:segment)', 'Sesion::getSesionesPorFecha/$1');
        $routes->post('generar-masivas', 'Sesion::generarSesionesMasivas');
        $routes->get('(:num)/estudiantes', 'Sesion::getEstudiantesParaAsistencia/$1');
    });

    $routes->group('asistencias', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Asistencia::index');
        $routes->get('(:num)', 'Asistencia::show/$1');
        $routes->post('', 'Asistencia::create');
        $routes->put('(:num)', 'Asistencia::update/$1');
        $routes->delete('(:num)', 'Asistencia::delete/$1');
        $routes->post('registrar-masivo', 'Asistencia::registrarMasivo');
        $routes->post('marcar-presente', 'Asistencia::marcarPresente');
        $routes->post('marcar-ausente', 'Asistencia::marcarAusente');
        $routes->post('marcar-tarde', 'Asistencia::marcarTarde');
        $routes->patch('(:num)/justificar', 'Asistencia::justificarAusencia/$1');
        $routes->get('reporte/estudiante/(:num)', 'Asistencia::reportePorEstudiante/$1');
        $routes->get('reporte/grupo/(:num)', 'Asistencia::reportePorGrupo/$1');
        $routes->get('estadisticas/estudiante/(:num)', 'Asistencia::estadisticasPorEstudiante/$1');
        $routes->get('estadisticas/grupo/(:num)', 'Asistencia::estadisticasPorGrupo/$1');
    });

    $routes->group('motivos-ausencia', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'MotivoAusencia::index');
        $routes->get('(:num)', 'MotivoAusencia::show/$1');
        $routes->post('', 'MotivoAusencia::create');
        $routes->put('(:num)', 'MotivoAusencia::update/$1');
        $routes->delete('(:num)', 'MotivoAusencia::delete/$1');
        $routes->get('codigo/(:segment)', 'MotivoAusencia::getByCodigo/$1');
    });

    $routes->group('horarios', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Horario::index');
        $routes->get('(:num)', 'Horario::show/$1');
        $routes->post('', 'Horario::create');
        $routes->put('(:num)', 'Horario::update/$1');
        $routes->delete('(:num)', 'Horario::delete/$1');
        $routes->get('grupo/(:num)', 'Horario::porGrupo/$1');
        $routes->get('aula/(:num)', 'Horario::porAula/$1');
        $routes->post('validar-conflicto', 'Horario::validarConflicto');
    });
    
    $routes->group('dashboard', ['filter' => 'auth'], function($routes) {
        $routes->get('', 'Dashboard::index');
        $routes->get('asistencias-semana', 'Dashboard::asistenciasSemana');
        $routes->get('asistencias-mes', 'Dashboard::asistenciasMes');
        $routes->get('grupos-activos', 'Dashboard::gruposMasActivos');
        $routes->get('estudiantes-ausencias', 'Dashboard::estudiantesConMasAusencias');
        $routes->get('proximas-sesiones', 'Dashboard::proximasSesiones');
        $routes->get('resumen-general', 'Dashboard::resumenGeneral');
    });
});