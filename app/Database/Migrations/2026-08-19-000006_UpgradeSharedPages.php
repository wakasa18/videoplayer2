<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpgradeSharedPages extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS share_title VARCHAR(255) NULL");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS share_message TEXT NULL");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS display_name VARCHAR(100) NULL");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS notify_first_open BOOLEAN NOT NULL DEFAULT FALSE");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS notify_download_limit BOOLEAN NOT NULL DEFAULT FALSE");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS notify_expiring BOOLEAN NOT NULL DEFAULT FALSE");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS first_open_notified_at TIMESTAMP NULL");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS limit_notified_at TIMESTAMP NULL");
        $this->db->query("ALTER TABLE important_file_shares ADD COLUMN IF NOT EXISTS expiring_notified_at TIMESTAMP NULL");

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS important_file_share_events (
  id BIGSERIAL PRIMARY KEY,
  share_id BIGINT NOT NULL REFERENCES important_file_shares(id) ON DELETE CASCADE,
  file_id INTEGER NULL REFERENCES important_files(id) ON DELETE SET NULL,
  event_type VARCHAR(60) NOT NULL,
  session_hash CHAR(64) NULL,
  details JSONB NULL,
  is_notification BOOLEAN NOT NULL DEFAULT FALSE,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT NOW()
)
SQL);
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_share_events_share_created ON important_file_share_events (share_id, created_at DESC)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_share_events_type ON important_file_share_events (event_type)");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_share_events_notification ON important_file_share_events (share_id, is_notification, read_at)");

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS important_file_share_download_sessions (
  id BIGSERIAL PRIMARY KEY,
  session_token_hash CHAR(64) NOT NULL UNIQUE,
  share_id BIGINT NOT NULL REFERENCES important_file_shares(id) ON DELETE CASCADE,
  folder_path VARCHAR(1000) NULL,
  file_ids JSONB NOT NULL DEFAULT '[]'::jsonb,
  file_count INTEGER NOT NULL DEFAULT 0 CHECK (file_count >= 0),
  total_bytes BIGINT NOT NULL DEFAULT 0 CHECK (total_bytes >= 0),
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','completed','cancelled','expired')),
  expires_at TIMESTAMP NOT NULL,
  completed_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT NOW()
)
SQL);
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_share_download_sessions_share ON important_file_share_download_sessions (share_id, status, expires_at)");
        $this->db->query("ALTER TABLE important_file_share_events ENABLE ROW LEVEL SECURITY");
        $this->db->query("ALTER TABLE important_file_share_download_sessions ENABLE ROW LEVEL SECURITY");
        $this->db->query("REVOKE ALL ON TABLE important_file_share_events FROM anon, authenticated");
        $this->db->query("REVOKE ALL ON TABLE important_file_share_download_sessions FROM anon, authenticated");
        $this->db->query("REVOKE ALL ON SEQUENCE important_file_share_events_id_seq FROM anon, authenticated");
        $this->db->query("REVOKE ALL ON SEQUENCE important_file_share_download_sessions_id_seq FROM anon, authenticated");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS important_file_share_download_sessions CASCADE');
        $this->db->query('DROP TABLE IF EXISTS important_file_share_events CASCADE');
        foreach ([
            'share_title','share_message','display_name','notify_first_open','notify_download_limit','notify_expiring',
            'first_open_notified_at','limit_notified_at','expiring_notified_at',
        ] as $column) {
            $this->db->query('ALTER TABLE important_file_shares DROP COLUMN IF EXISTS ' . $column);
        }
    }
}
