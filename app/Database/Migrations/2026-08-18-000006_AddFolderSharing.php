<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFolderSharing extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS share_type VARCHAR(10) NOT NULL DEFAULT 'file'");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS folder_path VARCHAR(1000) NULL");
        $this->db->query("UPDATE important_file_shares SET share_type = 'file' WHERE share_type IS NULL OR share_type = ''");
        $this->db->query('ALTER TABLE important_file_shares ALTER COLUMN file_id DROP NOT NULL');
        $this->db->query(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'important_file_shares_target_check'
          AND conrelid = 'important_file_shares'::regclass
    ) THEN
        ALTER TABLE important_file_shares
            ADD CONSTRAINT important_file_shares_target_check CHECK (
                (share_type = 'file' AND file_id IS NOT NULL AND folder_path IS NULL)
                OR
                (share_type = 'folder' AND file_id IS NULL AND folder_path IS NOT NULL AND folder_path <> '')
            );
    END IF;
END $$
SQL);
        $this->db->query(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'important_file_shares_type_check'
          AND conrelid = 'important_file_shares'::regclass
    ) THEN
        ALTER TABLE important_file_shares
            ADD CONSTRAINT important_file_shares_type_check CHECK (share_type IN ('file', 'folder'));
    END IF;
END $$
SQL);
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_important_file_shares_folder_path ON important_file_shares (folder_path)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_important_file_shares_folder_active ON important_file_shares (share_type, folder_path, revoked_at, expires_at)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_important_file_shares_folder_active');
        $this->db->query('DROP INDEX IF EXISTS idx_important_file_shares_folder_path');
        $this->db->query('ALTER TABLE important_file_shares DROP CONSTRAINT IF EXISTS important_file_shares_target_check');
        $this->db->query('ALTER TABLE important_file_shares DROP CONSTRAINT IF EXISTS important_file_shares_type_check');
        $this->db->query("DELETE FROM important_file_shares WHERE share_type = 'folder'");
        $this->db->query('ALTER TABLE important_file_shares ALTER COLUMN file_id SET NOT NULL');
        $this->db->query('ALTER TABLE important_file_shares DROP COLUMN IF EXISTS folder_path');
        $this->db->query('ALTER TABLE important_file_shares DROP COLUMN IF EXISTS share_type');
    }
}
