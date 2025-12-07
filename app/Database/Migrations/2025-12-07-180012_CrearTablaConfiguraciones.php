<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaConfiguraciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre_clave' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
                'unique' => true,
            ],
            'valor' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'actualizado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('configuraciones', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('configuraciones', true);
    }
}