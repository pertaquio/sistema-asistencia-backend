<?php

namespace App\Controllers;

use App\Models\GrupoModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Grupo extends ResourceController
{
    protected $modelName = 'App\Models\GrupoModel';
    protected $format = 'json';
    protected $grupoModel;

    public function __construct()
    {
        $this->grupoModel = new GrupoModel();
    }

    public function index()
    {
        $page = $this->request->getVar('page') ?? 1;
        $perPage = $this->request->getVar('per_page') ?? 10;
        $search = $this->request->getVar('search') ?? '';
        $cursoId = $this->request->getVar('curso_id') ?? '';
        $anioAcademico = $this->request->getVar('anio_academico') ?? '';
        $profesorId = $this->request->getVar('profesor_id') ?? '';

        $builder = $this->grupoModel
            ->select('grupos.*, cursos.nombre as curso_nombre, cursos.codigo as curso_codigo, usuarios.nombre_completo as profesor_nombre, profesores.codigo_profesor')
            ->join('cursos', 'cursos.id = grupos.curso_id')
            ->join('profesores', 'profesores.id = grupos.profesor_id', 'left')
            ->join('usuarios', 'usuarios.id = profesores.usuario_id', 'left');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('grupos.nombre', $search)
                ->orLike('cursos.nombre', $search)
                ->orLike('cursos.codigo', $search)
                ->groupEnd();
        }

        if (!empty($cursoId)) {
            $builder->where('grupos.curso_id', $cursoId);
        }

        if (!empty($anioAcademico)) {
            $builder->where('grupos.anio_academico', $anioAcademico);
        }

        if (!empty($profesorId)) {
            $builder->where('grupos.profesor_id', $profesorId);
        }

        $grupos = $builder->paginate($perPage, 'default', $page);
        $pager = $this->grupoModel->pager;

        return $this->respond([
            'status' => 'success',
            'data' => $grupos,
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
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->getGrupoCompleto($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        return $this->respond([
            'status' => 'success',
            'data' => $grupo
        ], ResponseInterface::HTTP_OK);
    }

    public function create()
    {
        $rules = [
            'curso_id' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El curso es requerido',
                    'is_natural_no_zero' => 'El ID del curso debe ser válido'
                ]
            ],
            'nombre' => [
                'label' => 'Nombre',
                'rules' => 'required|min_length[3]|max_length[80]',
                'errors' => [
                    'required' => 'El nombre del grupo es requerido',
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 80 caracteres'
                ]
            ],
            'anio_academico' => [
                'label' => 'Año académico',
                'rules' => 'required|integer|exact_length[4]',
                'errors' => [
                    'required' => 'El año académico es requerido',
                    'integer' => 'El año académico debe ser un número',
                    'exact_length' => 'El año académico debe tener 4 dígitos'
                ]
            ],
            'profesor_id' => [
                'label' => 'Profesor',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El ID del profesor debe ser válido'
                ]
            ],
            'capacidad_maxima' => [
                'label' => 'Capacidad máxima',
                'rules' => 'permit_empty|integer',
                'errors' => [
                    'integer' => 'La capacidad máxima debe ser un número'
                ]
            ],
            'aula' => [
                'label' => 'Aula',
                'rules' => 'permit_empty|max_length[50]',
                'errors' => [
                    'max_length' => 'El aula no puede exceder 50 caracteres'
                ]
            ],
            'turno' => [
                'label' => 'Turno',
                'rules' => 'permit_empty|in_list[Mañana,Tarde,Noche]',
                'errors' => [
                    'in_list' => 'El turno debe ser Mañana, Tarde o Noche'
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
            'curso_id' => $this->request->getVar('curso_id'),
            'nombre' => $this->request->getVar('nombre'),
            'anio_academico' => $this->request->getVar('anio_academico'),
            'profesor_id' => $this->request->getVar('profesor_id'),
            'capacidad_maxima' => $this->request->getVar('capacidad_maxima'),
            'aula' => $this->request->getVar('aula'),
            'turno' => $this->request->getVar('turno')
        ];

        $grupoId = $this->grupoModel->insert($data);

        if (!$grupoId) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al crear el grupo',
                'errors' => $this->grupoModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $grupo = $this->grupoModel->getGrupoCompleto($grupoId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Grupo creado exitosamente',
            'data' => $grupo
        ], ResponseInterface::HTTP_CREATED);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'curso_id' => [
                'label' => 'Curso',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El ID del curso debe ser válido'
                ]
            ],
            'nombre' => [
                'label' => 'Nombre',
                'rules' => 'permit_empty|min_length[3]|max_length[80]',
                'errors' => [
                    'min_length' => 'El nombre debe tener al menos 3 caracteres',
                    'max_length' => 'El nombre no puede exceder 80 caracteres'
                ]
            ],
            'anio_academico' => [
                'label' => 'Año académico',
                'rules' => 'permit_empty|integer|exact_length[4]',
                'errors' => [
                    'integer' => 'El año académico debe ser un número',
                    'exact_length' => 'El año académico debe tener 4 dígitos'
                ]
            ],
            'profesor_id' => [
                'label' => 'Profesor',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El ID del profesor debe ser válido'
                ]
            ],
            'capacidad_maxima' => [
                'label' => 'Capacidad máxima',
                'rules' => 'permit_empty|integer',
                'errors' => [
                    'integer' => 'La capacidad máxima debe ser un número'
                ]
            ],
            'aula' => [
                'label' => 'Aula',
                'rules' => 'permit_empty|max_length[50]',
                'errors' => [
                    'max_length' => 'El aula no puede exceder 50 caracteres'
                ]
            ],
            'turno' => [
                'label' => 'Turno',
                'rules' => 'permit_empty|in_list[Mañana,Tarde,Noche]',
                'errors' => [
                    'in_list' => 'El turno debe ser Mañana, Tarde o Noche'
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

        if ($this->request->getVar('curso_id')) {
            $data['curso_id'] = $this->request->getVar('curso_id');
        }

        if ($this->request->getVar('nombre')) {
            $data['nombre'] = $this->request->getVar('nombre');
        }

        if ($this->request->getVar('anio_academico')) {
            $data['anio_academico'] = $this->request->getVar('anio_academico');
        }

        if ($this->request->getVar('profesor_id') !== null) {
            $data['profesor_id'] = $this->request->getVar('profesor_id');
        }

        if ($this->request->getVar('capacidad_maxima') !== null) {
            $data['capacidad_maxima'] = $this->request->getVar('capacidad_maxima');
        }

        if ($this->request->getVar('aula') !== null) {
            $data['aula'] = $this->request->getVar('aula');
        }

        if ($this->request->getVar('turno') !== null) {
            $data['turno'] = $this->request->getVar('turno');
        }

        if (empty($data)) {
            return $this->respond([
                'status' => 'error',
                'message' => 'No hay datos para actualizar'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $updated = $this->grupoModel->update($id, $data);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al actualizar el grupo',
                'errors' => $this->grupoModel->errors()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $grupoActualizado = $this->grupoModel->getGrupoCompleto($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Grupo actualizado exitosamente',
            'data' => $grupoActualizado
        ], ResponseInterface::HTTP_OK);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $deleted = $this->grupoModel->delete($id);

        if (!$deleted) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al eliminar el grupo'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Grupo eliminado exitosamente'
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstudiantes($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estadoInscripcion = $this->request->getVar('estado');
        $estudiantes = $this->grupoModel->getEstudiantes($id, $estadoInscripcion);

        return $this->respond([
            'status' => 'success',
            'data' => $estudiantes
        ], ResponseInterface::HTTP_OK);
    }

    public function getHorarios($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $horarios = $this->grupoModel->getHorarios($id);

        return $this->respond([
            'status' => 'success',
            'data' => $horarios
        ], ResponseInterface::HTTP_OK);
    }

    public function getSesiones($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');
        $estado = $this->request->getVar('estado');

        $sesiones = $this->grupoModel->getSesiones($id, $fechaInicio, $fechaFin, $estado);

        return $this->respond([
            'status' => 'success',
            'data' => $sesiones
        ], ResponseInterface::HTTP_OK);
    }

    public function getAsistencias($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $fechaInicio = $this->request->getVar('fecha_inicio');
        $fechaFin = $this->request->getVar('fecha_fin');

        $asistencias = $this->grupoModel->getAsistencias($id, $fechaInicio, $fechaFin);

        return $this->respond([
            'status' => 'success',
            'data' => $asistencias
        ], ResponseInterface::HTTP_OK);
    }

    public function getEstadisticas($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $estadisticas = $this->grupoModel->getEstadisticas($id);

        return $this->respond([
            'status' => 'success',
            'data' => $estadisticas
        ], ResponseInterface::HTTP_OK);
    }

    public function asignarProfesor($id = null)
    {
        if (!$id) {
            return $this->respond([
                'status' => 'error',
                'message' => 'ID de grupo requerido'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $grupo = $this->grupoModel->find($id);

        if (!$grupo) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Grupo no encontrado'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }

        $rules = [
            'profesor_id' => [
                'label' => 'Profesor',
                'rules' => 'permit_empty|is_natural_no_zero',
                'errors' => [
                    'is_natural_no_zero' => 'El ID del profesor debe ser válido'
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

        $profesorId = $this->request->getVar('profesor_id');

        $updated = $this->grupoModel->asignarProfesor($id, $profesorId);

        if (!$updated) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Error al asignar el profesor'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $grupoActualizado = $this->grupoModel->getGrupoCompleto($id);

        return $this->respond([
            'status' => 'success',
            'message' => 'Profesor asignado exitosamente',
            'data' => $grupoActualizado
        ], ResponseInterface::HTTP_OK);
    }
}