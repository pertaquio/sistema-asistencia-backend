<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'rol_id' => [
                'type' => 'TINYINT',
                'unsigned' => true,
                'null' => false,
            ],
            'nombre_usuario' => [
                'type' => 'VARCHAR',
                'constraint' => '80',
                'null' => false,
                'unique' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
                'unique' => true,
            ],
            'contrasena_hash' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'nombre_completo' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'esta_activo' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => 1,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'actualizado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('rol_id', 'roles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('usuarios', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('usuarios', true);
    }
}