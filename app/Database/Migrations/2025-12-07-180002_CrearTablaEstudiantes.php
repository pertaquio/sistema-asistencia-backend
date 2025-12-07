<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaEstudiantes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
                'unique' => true,
            ],
            'codigo_estudiante' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'unique' => true,
            ],
            'fecha_nacimiento' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'genero' => [
                'type' => 'ENUM',
                'constraint' => ['M', 'F', 'O'],
                'default' => 'O',
            ],
            'extra' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('estudiantes', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('estudiantes', true);
    }
}