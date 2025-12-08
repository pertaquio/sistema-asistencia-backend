<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EstadoModel;

class Estados extends ResourceController
{
    protected $modelName = EstadoModel::class;
    protected $format    = 'json';

    public function index()
    {
        $estados = $this->model->findAll();
        return $this->respond($estados);
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->failValidationError('Se requiere ID.');
        }

        $estado = $this->model->find($id);
        if (!$estado) {
            return $this->failNotFound("Estado con id {$id} no encontrado.");
        }

        return $this->respond($estado);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->failValidationError('JSON inválido o vacío.');
        }

        $rules = [
            'nombre' => 'required|max_length[100]',
            'descripcion' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $id = $this->model->insert($data, true);

        if ($id === false) {
            return $this->failServerError('No se pudo crear el estado.');
        }

        $nuevo = $this->model->find($id);
        return $this->respondCreated($nuevo);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->failValidationError('Se requiere ID.');
        }

        $estado = $this->model->find($id);
        if (!$estado) {
            return $this->failNotFound("Estado con id {$id} no encontrado.");
        }

        $data = $this->request->getJSON(true);
        if (!$data) {
            return $this->failValidationError('JSON inválido o vacío.');
        }

        $rules = [
            'nombre' => 'required|max_length[100]',
            'descripcion' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $updated = $this->model->update($id, $data);

        if ($updated === false) {
            return $this->failServerError('No se pudo actualizar el estado.');
        }

        $actualizado = $this->model->find($id);
        return $this->respond($actualizado);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->failValidationError('Se requiere ID.');
        }

        $estado = $this->model->find($id);
        if (!$estado) {
            return $this->failNotFound("Estado con id {$id} no encontrado.");
        }

        if ($this->model->delete($id)) {
            return $this->respondDeleted(['id' => $id, 'message' => 'Estado eliminado']);
        }

        return $this->failServerError('No se pudo eliminar el estado.');
    }

    public function usuarios($id = null)
    {
        if (!$id) {
            return $this->failValidationError('Se requiere ID.');
        }

        $estado = $this->model->find($id);
        if (!$estado) {
            return $this->failNotFound("Estado con id {$id} no encontrado.");
        }

        try {
            $usuarioModel = new \App\Models\UsuarioModel();
        } catch (\Throwable $e) {
            return $this->failNotImplemented('No existe UsuarioModel en App\Models. Implementa UsuarioModel o ajusta este método.');
        }

        $usuarios = $usuarioModel->where('estado_id', $id)->findAll();
        return $this->respond($usuarios);
    }

    public function estadisticas($id = null)
    {
        if (!$id) {
            return $this->failValidationError('Se requiere ID.');
        }

        $estado = $this->model->find($id);
        if (!$estado) {
            return $this->failNotFound("Estado con id {$id} no encontrado.");
        }

        try {
            $usuarioModel = new \App\Models\UsuarioModel();
        } catch (\Throwable $e) {
            return $this->failNotImplemented('No existe UsuarioModel en App\Models. Implementa UsuarioModel para estadísticas.');
        }

        $total = $usuarioModel->where('estado_id', $id)->countAllResults(false);

        $builder = $usuarioModel->builder();
        $builder->select('rol, COUNT(*) as cantidad')
                ->where('estado_id', $id)
                ->groupBy('rol');
        $query = $builder->get();
        $porRol = $query->getResultArray();

        return $this->respond([
            'estado_id' => $id,
            'total' => (int) $total,
            'por_rol' => $porRol,
        ]);
    }
}
