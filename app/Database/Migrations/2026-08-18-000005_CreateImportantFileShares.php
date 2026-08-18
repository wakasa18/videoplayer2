<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateImportantFileShares extends Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS important_file_shares (
    id BIGSERIAL PRIMARY KEY,
    file_id INTEGER NOT NULL REFERENCES important_files(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL,
    expires_at TIMESTAMP NULL,
    max_downloads INTEGER NULL,
    view_count INTEGER NOT NULL DEFAULT 0,
    download_count INTEGER NOT NULL DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    revoked_at TIMESTAMP NULL,
    created_by VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT important_file_shares_max_downloads_check CHECK (max_downloads IS NULL OR max_downloads BETWEEN 1 AND 10000),
    CONSTRAINT important_file_shares_view_count_check CHECK (view_count >= 0),
    CONSTRAINT important_file_shares_download_count_check CHECK (download_count >= 0)
)
SQL);

        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS uq_important_file_shares_token_hash ON important_file_shares (token_hash)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_important_file_shares_file_id ON important_file_shares (file_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_important_file_shares_expires_at ON important_file_shares (expires_at)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_important_file_shares_active ON important_file_shares (file_id, revoked_at, expires_at)');
    }

    public function down()
    {
        $this->forge->dropTable('important_file_shares', true);
    }
}
