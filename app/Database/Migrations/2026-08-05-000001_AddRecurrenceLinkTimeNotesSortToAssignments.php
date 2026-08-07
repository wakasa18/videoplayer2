<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRecurrenceLinkTimeNotesSortToAssignments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('assignments', [
            // 'weekly' | 'biweekly' | 'monthly' | null (no repeat)
            'recurrence' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'link_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            // Stored as 'HH:MM' (24h) text rather than a native TIME
            // column, kept deliberately simple/portable across drivers.
            'due_time' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            'notes_log' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('assignments', ['recurrence', 'link_url', 'due_time', 'notes_log', 'sort_order']);
    }
}
