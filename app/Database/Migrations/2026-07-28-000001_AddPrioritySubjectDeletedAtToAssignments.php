<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrioritySubjectDeletedAtToAssignments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('assignments', [
            'priority' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'medium',
                'null'       => false,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('assignments', ['priority', 'subject', 'deleted_at']);
    }
}
