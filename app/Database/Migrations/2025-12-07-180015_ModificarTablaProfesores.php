<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModificarTablaProfesores extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('profesores', 'extra');

        $fields = [
            'telefono' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'departamento'
            ],
            'direccion' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'telefono'
            ],
            'especialidad' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
                'after' => 'direccion'
            ],
            'fecha_contratacion' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'especialidad'
            ]
        ];

        $this->forge->addColumn('profesores', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('profesores', ['telefono', 'direccion', 'especialidad', 'fecha_contratacion']);

        $fields = [
            'extra' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'departamento'
            ]
        ];

        $this->forge->addColumn('profesores', $fields);
    }
}