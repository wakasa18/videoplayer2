<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReminderSentAtToAssignments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('assignments', [
            'reminder_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('assignments', ['reminder_sent_at']);
    }
}
