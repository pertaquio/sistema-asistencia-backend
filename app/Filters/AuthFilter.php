<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTLibrary;
use App\Models\TokenSesionModel;
use App\Models\UsuarioModel;
use App\Models\EstadoModel;

class AuthFilter implements FilterInterface
{
    protected $jwt;
    protected $tokenModel;
    protected $usuarioModel;

    public function __construct()
    {
        $this->jwt = new JWTLibrary();
        $this->tokenModel = new TokenSesionModel();
        $this->usuarioModel = new UsuarioModel();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Token de autenticación no proporcionado'
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        $tokenData = $this->jwt->validateToken($token);

        if (!$tokenData) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Token inválido o expirado'
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $tokenEnBD = $this->tokenModel->validarToken($token);

        if (!$tokenEnBD) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Token revocado o no encontrado'
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $usuario = $this->usuarioModel->find($tokenData->data->usuario_id);

        if (!$usuario || $usuario['estado_id'] != EstadoModel::ACTIVO) {
            return service('response')->setJSON([
                'status' => 'error',
                'message' => 'Usuario no activo'
            ])->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
        }

        $request->usuarioAutenticado = $tokenData->data;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}