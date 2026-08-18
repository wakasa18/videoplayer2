<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpgradeImportantFilesVault extends Migration
{
    public function up()
    {
        $db = $this->db;

        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS file_extension VARCHAR(20)");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS checksum_sha256 CHAR(64)");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS upload_token_hash CHAR(64)");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS document_date DATE");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS expires_at DATE");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS reminder_days SMALLINT NOT NULL DEFAULT 30");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS expiration_reminded_at TIMESTAMP NULL");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS is_favorite BOOLEAN NOT NULL DEFAULT FALSE");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS download_count INTEGER NOT NULL DEFAULT 0");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS last_downloaded_at TIMESTAMP NULL");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL");
        $db->query("ALTER TABLE important_files ADD COLUMN IF NOT EXISTS purge_at TIMESTAMP NULL");
        $db->query("ALTER TABLE important_files ALTER COLUMN file_size TYPE BIGINT");

        $db->query("UPDATE important_files SET file_extension = LOWER(SPLIT_PART(original_filename, '.', ARRAY_LENGTH(STRING_TO_ARRAY(original_filename, '.'), 1))) WHERE (file_extension IS NULL OR file_extension = '') AND original_filename LIKE '%.%'");
        $db->query("UPDATE important_files SET finalized_at = COALESCE(finalized_at, created_at, NOW()) WHERE status = 'active'");

        $db->query("CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_stored_filename ON important_files (stored_filename)");
        $db->query("CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_file_path ON important_files (file_path)");
        $db->query("CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_upload_token_hash ON important_files (upload_token_hash) WHERE upload_token_hash IS NOT NULL");
        $db->query("CREATE INDEX IF NOT EXISTS idx_important_files_created_at ON important_files (created_at DESC)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_important_files_category ON important_files (category)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_important_files_extension ON important_files (file_extension)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_important_files_expires_at ON important_files (expires_at)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_important_files_purge_at ON important_files (purge_at)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_important_files_favorite ON important_files (is_favorite)");

        $db->query("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_file_size_check') THEN ALTER TABLE important_files ADD CONSTRAINT important_files_file_size_check CHECK (file_size > 0); END IF; END $$");
        $db->query("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_status_check') THEN ALTER TABLE important_files ADD CONSTRAINT important_files_status_check CHECK (status IN ('pending', 'active', 'deleted', 'failed')); END IF; END $$");
        $db->query("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_reminder_days_check') THEN ALTER TABLE important_files ADD CONSTRAINT important_files_reminder_days_check CHECK (reminder_days BETWEEN 0 AND 3650); END IF; END $$");
        $db->query("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_download_count_check') THEN ALTER TABLE important_files ADD CONSTRAINT important_files_download_count_check CHECK (download_count >= 0); END IF; END $$");

        $db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS important_file_audits (
    id BIGSERIAL PRIMARY KEY,
    file_id INTEGER NULL REFERENCES important_files(id) ON DELETE SET NULL,
    action VARCHAR(80) NOT NULL,
    details JSONB NULL,
    actor_ip_hash VARCHAR(32) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
)
SQL);
        $db->query("CREATE INDEX IF NOT EXISTS idx_file_audits_file_id ON important_file_audits (file_id)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_file_audits_action ON important_file_audits (action)");
        $db->query("CREATE INDEX IF NOT EXISTS idx_file_audits_created_at ON important_file_audits (created_at DESC)");
    }

    public function down()
    {
        $this->forge->dropTable('important_file_audits', true);
        // Existing file data is intentionally preserved. Added columns are not dropped.
    }
}
