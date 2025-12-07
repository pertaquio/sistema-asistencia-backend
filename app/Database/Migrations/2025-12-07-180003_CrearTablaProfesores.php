<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaProfesores extends Migration
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
            'codigo_profesor' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
                'unique' => true,
            ],
            'departamento' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'extra' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('profesores', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('profesores', true);
    }
}