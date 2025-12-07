<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaEstados extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'TINYINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => false,
                'unique' => true,
            ],
            'descripcion' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('estados', true, ['ENGINE' => 'InnoDB']);
        
        $this->db->table('estados')->insertBatch([
            [
                'id' => 1,
                'nombre' => 'Activo',
                'descripcion' => 'Usuario activo en el sistema',
                'creado_en' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'nombre' => 'Inactivo',
                'descripcion' => 'Usuario inactivo',
                'creado_en' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'nombre' => 'Suspendido',
                'descripcion' => 'Usuario suspendido temporalmente',
                'creado_en' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('estados', true);
    }
}