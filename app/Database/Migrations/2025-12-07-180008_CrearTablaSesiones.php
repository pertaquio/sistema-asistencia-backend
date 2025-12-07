<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaSesiones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'grupo_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'curso_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'fecha_programada' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'hora_inicio' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'hora_fin' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['planificada', 'realizada', 'cancelada'],
                'default' => 'planificada',
            ],
            'creada_por' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['grupo_id', 'fecha_programada'], false, false, 'idx_sesion_grupo_fecha');
        $this->forge->addForeignKey('grupo_id', 'grupos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('curso_id', 'cursos', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('creada_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('sesiones', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('sesiones', true);
    }
}