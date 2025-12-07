<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CrearTablaInscripciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'estudiante_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'grupo_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'inscrito_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['activo', 'inactivo', 'graduado'],
                'default' => 'activo',
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['estudiante_id', 'grupo_id'], 'ux_estudiante_grupo');
        $this->forge->addForeignKey('estudiante_id', 'estudiantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('grupo_id', 'grupos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inscripciones', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('inscripciones', true);
    }
}