<?php

namespace App\Controllers;

use App\Models\RolModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Rol extends ResourceController
{
    protected $modelName = 'App\Models\RolModel';
    protected $format = 'json';
    protected $rolModel;

    public function __construct()
    {
        $this->rolModel = new RolModel();
    }

    public function index()
    {
        $roles = $this->rolModel->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $roles
        ], ResponseInterface::HTTP_OK);
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de rol requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $rol = $this->rolModel->find($id);

        if (!$rol) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Rol no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $rol
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'nombre' => [
                'label' => 'Nombre',
                'rules' => 'required|min_length[3]|max_length[50]|is_unique[roles.nombre]',
                'errors' => [
                    'required' => 'El nombre del rol es requerido',
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 50 caracteres',
                    'is_unique' => 'Este nombre de rol ya está registrado'
                ]
            ],
            'descripcion' => [
                'label' => 'Descripción',
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La descripción no puede exceder 255 caracteres'
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

        $data = [
            'nombre' => $this->request->getVar('nombre'),
            'descripcion' => $this->request->getVar('descripcion')
        ];

        $rolId = $this->rolModel->insert($data);

        if (!$rolId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el rol',
                'errors' => $this->rolModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $rol = $this->rolModel->find($rolId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Rol creado exitosamente',
            'data' => $rol
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de rol requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $rol = $this->rolModel->find($id);

        if (!$rol) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Rol no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'nombre' => [
                'label' => 'Nombre',
                'rules' => "permit_empty|min_length[3]|max_length[50]|is_unique[roles.nombre,id,{$id}]",
                'errors' => [
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 50 caracteres',
                    'is_unique' => 'Este nombre de rol ya está registrado'
                ]
            ],
            'descripcion' => [
                'label' => 'Descripción',
                'rules' => 'permit_empty|max_length[255]',
                'errors' => [
                    'max_length' => 'La descripción no puede exceder 255 caracteres'
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

        $data = [];

        if ($this->request->getVar('nombre')) {
            $data['nombre'] = $this->request->getVar('nombre');
        }

        if ($this->request->getVar('descripcion') !== null) {
            $data['descripcion'] = $this->request->getVar('descripcion');
        }

        if (empty($data)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No hay datos para actualizar'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->rolModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el rol',
                'errors' => $this->rolModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $rolActualizado = $this->rolModel->find($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Rol actualizado exitosamente',
            'data' => $rolActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de rol requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $rol = $this->rolModel->find($id);

        if (!$rol) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Rol no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $usuariosConRol = $this->rolModel->getUsuariosPorRol($id);

        if (count($usuariosConRol) > 0) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados',
                'data' => [
                    'total_usuarios' => count($usuariosConRol)
                ]
            ], ResponseInterface::HTTP_CONFLICT);
        }

        $deleted = $this->rolModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el rol'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Rol eliminado exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function getUsuarios($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de rol requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $rol = $this->rolModel->find($id);

        if (!$rol) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Rol no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $usuarios = $this->rolModel->getUsuariosPorRol($id);

        return $this->respond([
            'status' => 'success',
            'data' => $usuarios
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstadisticas($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de rol requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $rol = $this->rolModel->find($id);

        if (!$rol) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Rol no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estadisticas = $this->rolModel->getEstadisticas($id);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }
}