<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\TokenSesionModel;
use App\Models\EstadoModel;
use App\Libraries\JWTLibrary;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends ResourceController
{
    protected $modelName = 'App\Models\UsuarioModel';
    protected $format = 'json';
    protected $usuarioModel;
    protected $tokenModel;
    protected $jwt;
    protected $cache;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->tokenModel = new TokenSesionModel();
        $this->jwt = new JWTLibrary();
        $this->cache = \Config\Services::cache();
    }

    public function login()
    {
        $rules = [
            'identificador' => [
                'label' => 'Identificador',
                'rules' => 'required|valid_email',
                'errors' => [
                    'required' => 'El correo electrónico es requerido',
                    'valid_email' => 'Debe proporcionar un correo electrónico válido'
                ]
            ],
            'contrasena' => [
                'label' => 'Contraseña',
                'rules' => 'required|min_length[6]|max_length[12]',
                'errors' => [
                    'required' => 'La contraseña es requerida',
                    'min_length' => 'La contraseña debe tener al menos 6 caracteres',
                    'max_length' => 'La contraseña no puede exceder 12 caracteres'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Datos de entrada inválidos',
                'errors' => $this->validator->getErrors()
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $identificador = $this->request->getVar('identificador');
        $contrasena = $this->request->getVar('contrasena');

        $ipAddress = str_replace([':', '.', '/'], '_', $this->request->getIPAddress());
        $rateLimitKey = 'login_attempts_' . $ipAddress;
        $attempts = $this->cache->get($rateLimitKey) ?? 0;

        if ($attempts >= 5) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Demasiados intentos de login. Intente más tarde.'
            ], ResponseInterface::HTTP_TOO_MANY_REQUESTS);
        }

        $usuario = $this->usuarioModel->verificarCredenciales($identificador, $contrasena);

        if (!$usuario) {
            $this->cache->save($rateLimitKey, $attempts + 1, 900);
            
            return $this->respond([
                'status' => 'error',
                'message' => 'Credenciales inválidas'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        if ($usuario['estado_id'] == EstadoModel::INACTIVO) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario inactivo. Contacte al administrador.'
            ], ResponseInterface::HTTP_FORBIDDEN);
        }

        if ($usuario['estado_id'] == EstadoModel::SUSPENDIDO) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario suspendido. Contacte al administrador.'
            ], ResponseInterface::HTTP_FORBIDDEN);
        }

        if ($usuario['estado_id'] != EstadoModel::ACTIVO) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no disponible. Contacte al administrador.'
            ], ResponseInterface::HTTP_FORBIDDEN);
        }

        $this->cache->delete($rateLimitKey);

        $usuarioCompleto = $this->usuarioModel->getUsuarioConRol($usuario['id']);

        $tokenPayload = [
            'usuario_id' => $usuarioCompleto['id'],
            'nombre_usuario' => $usuarioCompleto['nombre_usuario'],
            'rol_id' => $usuarioCompleto['rol_id'],
            'rol_nombre' => $usuarioCompleto['rol_nombre'],
            'estado_id' => $usuarioCompleto['estado_id'],
            'estado_nombre' => $usuarioCompleto['estado_nombre']
        ];

        $accessToken = $this->jwt->generateAccessToken($tokenPayload);
        $refreshToken = $this->jwt->generateRefreshToken(['usuario_id' => $usuarioCompleto['id']]);

        $this->tokenModel->guardarToken(
            $usuarioCompleto['id'],
            $accessToken,
            $refreshToken,
            $this->jwt->getTokenExpiration('access')
        );

        unset($usuarioCompleto['contrasena_hash']);

        return $this->respond([
            'status' => 'success',
            'message' => 'Login exitoso',
            'data' => [
                'usuario' => $usuarioCompleto,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->jwt->getTokenExpiration('access')
            ]
        ], ResponseInterface::HTTP_OK);
    }

    public function refresh()
    {
        $refreshToken = $this->request->getVar('refresh_token');

        if (!$refreshToken) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Refresh token requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $tokenData = $this->jwt->validateToken($refreshToken);

        if (!$tokenData || !isset($tokenData->type) || $tokenData->type !== 'refresh') {
            return $this->respond([
                'status' => 'error',
                'message' => 'Refresh token inválido'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $tokenEnBD = $this->tokenModel->validarRefreshToken($refreshToken);

        if (!$tokenEnBD) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Refresh token inválido o expirado'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $usuario = $this->usuarioModel->getUsuarioConRol($tokenData->data->usuario_id);

        if (!$usuario || $usuario['estado_id'] != EstadoModel::ACTIVO) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no disponible'
            ], ResponseInterface::HTTP_FORBIDDEN);
        }

        $tokenPayload = [
            'usuario_id' => $usuario['id'],
            'nombre_usuario' => $usuario['nombre_usuario'],
            'rol_id' => $usuario['rol_id'],
            'rol_nombre' => $usuario['rol_nombre'],
            'estado_id' => $usuario['estado_id'],
            'estado_nombre' => $usuario['estado_nombre']
        ];

        $newAccessToken = $this->jwt->generateAccessToken($tokenPayload);
        $newRefreshToken = $this->jwt->generateRefreshToken(['usuario_id' => $usuario['id']]);

        $this->tokenModel->revocarToken($tokenEnBD['token']);

        $this->tokenModel->guardarToken(
            $usuario['id'],
            $newAccessToken,
            $newRefreshToken,
            $this->jwt->getTokenExpiration('access')
        );

        return $this->respond([
            'status' => 'success',
            'message' => 'Token renovado exitosamente',
            'data' => [
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->jwt->getTokenExpiration('access')
            ]
        ], ResponseInterface::HTTP_OK);
    }

    public function logout()
    {
        $token = $this->request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);

        if (!$token) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Token no proporcionado'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $this->tokenModel->revocarToken($token);

        return $this->respond([
            'status' => 'success',
            'message' => 'Logout exitoso'
        ], ResponseInterface::HTTP_OK);
    }

    public function me()
    {
        $token = $this->request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $token);

        if (!$token) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Token no proporcionado'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $tokenData = $this->jwt->validateToken($token);

        if (!$tokenData) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Token inválido'
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $usuario = $this->usuarioModel->getUsuarioConRol($tokenData->data->usuario_id);

        if (!$usuario) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Usuario no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        unset($usuario['contrasena_hash']);

        return $this->respond([
            'status' => 'success',
            'data' => $usuario
        ], ResponseInterface::HTTP_OK);
    }
}