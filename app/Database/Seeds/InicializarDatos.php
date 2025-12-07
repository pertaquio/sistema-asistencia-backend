<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InicializarDatos extends Seeder
{
    public function run()
    {
        $roles = [
            ['id' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['id' => 2, 'nombre' => 'Profesor', 'descripcion' => 'Acceso a gestión de cursos y asistencias'],
            ['id' => 3, 'nombre' => 'Estudiante', 'descripcion' => 'Acceso a consulta de información']
        ];

        foreach ($roles as $rol) {
            $existe = $this->db->table('roles')->where('nombre', $rol['nombre'])->get()->getRow();
            
            if (!$existe) {
                $this->db->table('roles')->insert([
                    'nombre' => $rol['nombre'],
                    'descripcion' => $rol['descripcion'],
                    'creado_en' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $usuarios = [
            [
                'rol_id' => 1,
                'nombre_usuario' => 'admin@sistema.com',
                'email' => 'admin@sistema.com',
                'contrasena' => 'admin123',
                'nombre_completo' => 'Administrador del Sistema',
                'estado_id' => 1
            ],
            [
                'rol_id' => 2,
                'nombre_usuario' => 'profesor@sistema.com',
                'email' => 'profesor@sistema.com',
                'contrasena' => 'prof123',
                'nombre_completo' => 'Profesor de Prueba',
                'estado_id' => 1
            ],
            [
                'rol_id' => 3,
                'nombre_usuario' => 'estudiante@sistema.com',
                'email' => 'estudiante@sistema.com',
                'contrasena' => 'est123',
                'nombre_completo' => 'Estudiante de Prueba',
                'estado_id' => 2
            ],
            [
                'rol_id' => 3,
                'nombre_usuario' => 'suspendido@sistema.com',
                'email' => 'suspendido@sistema.com',
                'contrasena' => 'susp123',
                'nombre_completo' => 'Usuario Suspendido',
                'estado_id' => 3
            ]
        ];

        foreach ($usuarios as $usuario) {
            $existe = $this->db->table('usuarios')
                ->where('nombre_usuario', $usuario['nombre_usuario'])
                ->get()
                ->getRow();
            
            if (!$existe) {
                $this->db->table('usuarios')->insert([
                    'rol_id' => $usuario['rol_id'],
                    'nombre_usuario' => $usuario['nombre_usuario'],
                    'email' => $usuario['email'],
                    'contrasena_hash' => password_hash($usuario['contrasena'], PASSWORD_DEFAULT),
                    'nombre_completo' => $usuario['nombre_completo'],
                    'estado_id' => $usuario['estado_id'],
                    'creado_en' => date('Y-m-d H:i:s'),
                    'actualizado_en' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
}