<?php

namespace App\Controllers;

use App\Models\CursoModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Curso extends ResourceController
{
    protected $modelName = 'App\Models\CursoModel';
    protected $format = 'json';
    protected $cursoModel;

    public function __construct()
    {
        $this->cursoModel = new CursoModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $search = $this->request->getVar('search') ?? '';

        $builder = $this->cursoModel;

        if (!empty($search)) {
            $builder = $builder->groupStart()
                ->like('codigo', $search)
                ->orLike('nombre', $search)
                ->orLike('descripcion', $search)
                ->groupEnd();
        }

        $cursos = $builder->paginate($perPage, 'default', $page);
        $pager = $this->cursoModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $cursos,
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
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $curso
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'codigo' => [
                'label' => 'Código',
                'rules' => 'permit_empty|is_unique[cursos.codigo]|max_length[50]',
                'errors' => [
                    'is_unique' => 'Este código de curso ya está registrado',
                    'max_length' => 'El código no puede exceder 50 caracteres'
                ]
            ],
            'nombre' => [
                'label' => 'Nombre',
                'rules' => 'required|min_length[3]|max_length[150]',
                'errors' => [
                    'required' => 'El nombre del curso es requerido',
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 150 caracteres'
                ]
            ],
            'descripcion' => [
                'label' => 'Descripción',
                'rules' => 'permit_empty',
                'errors' => []
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
            'nombre' => $this->request->getVar('nombre'),
            'descripcion' => $this->request->getVar('descripcion')
        ];

        $cursoId = $this->cursoModel->insert($data);

        if (!$cursoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el curso',
                'errors' => $this->cursoModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $curso = $this->cursoModel->find($cursoId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Curso creado exitosamente',
            'data' => $curso
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'codigo' => [
                'label' => 'Código',
                'rules' => "permit_empty|is_unique[cursos.codigo,id,{$id}]|max_length[50]",
                'errors' => [
                    'is_unique' => 'Este código de curso ya está registrado',
                    'max_length' => 'El código no puede exceder 50 caracteres'
                ]
            ],
            'nombre' => [
                'label' => 'Nombre',
                'rules' => 'permit_empty|min_length[3]|max_length[150]',
                'errors' => [
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 150 caracteres'
                ]
            ],
            'descripcion' => [
                'label' => 'Descripción',
                'rules' => 'permit_empty',
                'errors' => []
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

        $updated = $this->cursoModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el curso',
                'errors' => $this->cursoModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $cursoActualizado = $this->cursoModel->find($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Curso actualizado exitosamente',
            'data' => $cursoActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->cursoModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el curso'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Curso eliminado exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function getByCodigo($codigo = null)
    {
        if (!$codigo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Código de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->getCursoPorCodigo($codigo);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $curso
        ], ResponseInterface::HTTP_OK);
    }

    public function getGrupos($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $anioAcademico = $this->request->getVar('anio_academico');
        $grupos = $this->cursoModel->getGrupos($id, $anioAcademico);

        return $this->respond([
            'status' => 'success',
            'data' => $grupos
        ], ResponseInterface::HTTP_OK);
    }

    public function getSesiones($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');

        $sesiones = $this->cursoModel->getSesiones($id, $fechaInicio, $fechaFin);

        return $this->respond([
            'status' => 'success',
            'data' => $sesiones
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstadisticas($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estadisticas = $this->cursoModel->getEstadisticas($id);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstudiantes($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de curso requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $curso = $this->cursoModel->find($id);

        if (!$curso) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Curso no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estudiantes = $this->cursoModel->getEstudiantesInscritos($id);

        return $this->respond([
            'status' => 'success',
            'data' => $estudiantes
        ], ResponseInterface::HTTP_OK);
    }
}