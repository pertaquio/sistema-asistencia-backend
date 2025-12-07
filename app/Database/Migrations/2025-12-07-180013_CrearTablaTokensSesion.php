<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaTokensSesion extends Migration
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
                'null' => false,
            ],
            'token' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'refresh_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'expira_en' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'revocado' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'default' => 0,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['usuario_id', 'revocado'], false, false, 'idx_usuario_token');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tokens_sesion', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('tokens_sesion', true);
    }
}