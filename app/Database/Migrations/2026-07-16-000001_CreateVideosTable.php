<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVideosTable extends Migration
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
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Randomized name the file is stored under on disk',
            ],
            'original_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Original filename as uploaded by the user',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'comment'    => 'Path relative to /public, e.g. uploads/videos/xxx.mp4',
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_size' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Size in bytes',
            ],
            'duration_seconds' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'thumbnail_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
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
        $this->forge->createTable('videos', true);
    }

    public function down()
    {
        $this->forge->dropTable('videos', true);
    }
}
