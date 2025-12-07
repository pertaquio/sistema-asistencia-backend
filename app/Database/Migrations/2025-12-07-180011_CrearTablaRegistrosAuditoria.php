<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaRegistrosAuditoria extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'accion' => [
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => false,
            ],
            'tipo_recurso' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'id_recurso' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'payload' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('registros_auditoria', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('registros_auditoria', true);
    }
}