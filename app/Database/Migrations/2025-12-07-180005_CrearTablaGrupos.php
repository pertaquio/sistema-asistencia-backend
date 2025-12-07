<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaGrupos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'curso_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '80',
                'null' => false,
            ],
            'anio_academico' => [
                'type' => 'YEAR',
                'null' => false,
            ],
            'profesor_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'extra' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('curso_id', 'cursos', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('profesor_id', 'profesores', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('grupos', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('grupos', true);
    }
}