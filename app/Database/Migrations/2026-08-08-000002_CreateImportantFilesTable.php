<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateImportantFilesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'stored_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Randomized name the file is stored under in the private bucket',
            ],
            'original_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Original filename as uploaded by the user',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'comment'    => 'Object key within the private Supabase bucket',
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'file_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Size in bytes',
            ],
            // VARCHAR instead of ENUM so this migration runs on both MySQL and Postgres.
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('important_files', true);
    }

    public function down()
    {
        $this->forge->dropTable('important_files', true);
    }
}
