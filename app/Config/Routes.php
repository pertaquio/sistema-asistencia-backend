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
});