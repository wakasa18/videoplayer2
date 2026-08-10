<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Config\Session already switches to a Postgres-backed session handler
 * whenever the app runs on Vercel (see app/Config/Session.php), since
 * Vercel's filesystem can't support the default file-based sessions.
 * That handler needs this exact table to exist — without it, every
 * session write (including flash messages like "Assignment added.")
 * silently fails.
 */
class CreateCiSessionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => 128,
                'null'       => false,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => false,
            ],
            'timestamp' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'data' => [
                'type' => 'BLOB',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions', true);
    }

    public function down()
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}
