<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaMotivosAusencia extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'TINYINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'codigo' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
                'unique' => true,
            ],
            'descripcion' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('motivos_ausencia', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('motivos_ausencia', true);
    }
}