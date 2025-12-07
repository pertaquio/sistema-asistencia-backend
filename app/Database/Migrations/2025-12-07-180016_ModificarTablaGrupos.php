<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModificarTablaGrupos extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('grupos', 'extra');

        $fields = [
            'capacidad_maxima' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'after' => 'profesor_id'
            ],
            'aula' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'capacidad_maxima'
            ],
            'turno' => [
                'type' => 'ENUM',
                'constraint' => ['Mañana', 'Tarde', 'Noche'],
                'null' => true,
                'after' => 'aula'
            ]
        ];

        $this->forge->addColumn('grupos', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('grupos', ['capacidad_maxima', 'aula', 'turno']);

        $fields = [
            'extra' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'profesor_id'
            ]
        ];

        $this->forge->addColumn('grupos', $fields);
    }
}