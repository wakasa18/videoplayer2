<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFolderPathToImportantFiles extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS folder_path VARCHAR(1000)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_important_files_folder_path ON important_files (folder_path)");
    }

    public function down()
    {
        $this->db->query("DROP INDEX IF EXISTS idx_important_files_folder_path");
        $this->db->query("ALTER TABLE important_files DROP COLUMN IF EXISTS folder_path");
    }
}
