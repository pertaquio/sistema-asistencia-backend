<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

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
});