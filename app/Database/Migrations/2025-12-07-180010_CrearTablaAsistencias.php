<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaAsistencias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'sesion_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
            ],
            'estudiante_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['presente', 'ausente', 'tarde', 'justificado'],
                'default' => 'presente',
            ],
            'hora_registro' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'motivo_id' => [
                'type' => 'TINYINT',
                'unsigned' => true,
                'null' => true,
            ],
            'nota' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'registrado_por' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'registrado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['sesion_id', 'estudiante_id'], 'ux_sesion_estudiante');
        $this->forge->addKey(['estudiante_id', 'estado'], false, false, 'idx_estudiante_estado');
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('estudiante_id', 'estudiantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('motivo_id', 'motivos_ausencia', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('registrado_por', 'usuarios', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('asistencias', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('asistencias', true);
    }
}