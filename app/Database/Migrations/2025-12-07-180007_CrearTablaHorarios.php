<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaHorarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'grupo_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'dia_semana' => [
                'type' => 'TINYINT',
                'unsigned' => true,
                'null' => false,
            ],
            'hora_inicio' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'hora_fin' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'ubicacion' => [
                'type' => 'VARCHAR',
                'constraint' => '120',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('grupo_id', 'grupos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('horarios', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('horarios', true);
    }
}