<?php

namespace App\Controllers;

use App\Models\MotivoAusenciaModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class MotivoAusencia extends ResourceController
{
    protected $modelName = 'App\Models\MotivoAusenciaModel';
    protected $format = 'json';
    protected $motivoModel;

    public function __construct()
    {
        $this->motivoModel = new MotivoAusenciaModel();
    }

    public function index()
    {
        $motivos = $this->motivoModel->findAll();

        return $this->respond([
            'status' => 'success',
            'data' => $motivos
        ], ResponseInterface::HTTP_OK);
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de motivo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $motivo = $this->motivoModel->find($id);

        if (!$motivo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Motivo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $motivo
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'codigo' => [
                'label' => 'Código',
                'rules' => 'permit_empty|is_unique[motivos_ausencia.codigo]|max_length[30]',
                'errors' => [
                    'is_unique' => 'Este código ya está registrado',
                    'max_length' => 'El código no puede exceder 30 caracteres'
                ]
            ],
            'descripcion' => [
                'label' => 'Descripción',
                'rules' => 'permit_empty|max_length[150]',
                'errors' => [
                    'max_length' => 'La descripción no puede exceder 150 caracteres'
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
            'codigo' => $this->request->getVar('codigo'),
            'descripcion' => $this->request->getVar('descripcion')
        ];

        $motivoId = $this->motivoModel->insert($data);

        if (!$motivoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el motivo',
                'errors' => $this->motivoModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $motivo = $this->motivoModel->find($motivoId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Motivo creado exitosamente',
            'data' => $motivo
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de motivo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $motivo = $this->motivoModel->find($id);

        if (!$motivo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Motivo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'codigo' => [
                'label' => 'Código',
                'rules' => "permit_empty|is_unique[motivos_ausencia.codigo,id,{$id}]|max_length[30]",
                'errors' => [
                    'is_unique' => 'Este código ya está registrado',
                    'max_length' => 'El código no puede exceder 30 caracteres'
                ]
            ],
            'descripcion' => [
                'label' => 'Descripción',
                'rules' => 'permit_empty|max_length[150]',
                'errors' => [
                    'max_length' => 'La descripción no puede exceder 150 caracteres'
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

        if ($this->request->getVar('codigo') !== null) {
            $data['codigo'] = $this->request->getVar('codigo');
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

        $updated = $this->motivoModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el motivo',
                'errors' => $this->motivoModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $motivoActualizado = $this->motivoModel->find($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Motivo actualizado exitosamente',
            'data' => $motivoActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de motivo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $motivo = $this->motivoModel->find($id);

        if (!$motivo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Motivo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->motivoModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el motivo'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Motivo eliminado exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function getByCodigo($codigo = null)
    {
        if (!$codigo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Código de motivo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $motivo = $this->motivoModel->getMotivoPorCodigo($codigo);

        if (!$motivo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Motivo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $motivo
        ], ResponseInterface::HTTP_OK);
    }
}