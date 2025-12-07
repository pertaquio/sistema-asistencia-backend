<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModificarTablaUsuariosEstado extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('usuarios', 'esta_activo');
        
        $fields = [
            'estado_id' => [
                'type' => 'TINYINT',
                'unsigned' => true,
                'null' => false,
                'default' => 1,
                'after' => 'nombre_completo'
            ]
        ];
        
        $this->forge->addColumn('usuarios', $fields);
        
        $this->forge->addForeignKey('estado_id', 'estados', 'id', 'RESTRICT', 'CASCADE', 'usuarios');
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        
        $this->forge->dropForeignKey('usuarios', 'usuarios_estado_id_foreign');
        $this->forge->dropColumn('usuarios', 'estado_id');
        
        $fields = [
            'esta_activo' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => 1,
                'after' => 'nombre_completo'
            ]
        ];
        
        $this->forge->addColumn('usuarios', $fields);
        
        $this->db->enableForeignKeyChecks();
    }
}