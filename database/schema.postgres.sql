-- ============================================================
--  Video Player - Database Schema (PostgreSQL / Neon / Supabase)
--  Run this in the Neon SQL editor, Supabase SQL editor,
--  or via `psql "<connection-string>" -f schema.postgres.sql`
-- ============================================================

CREATE TABLE IF NOT EXISTS videos (
  id                 SERIAL PRIMARY KEY,
  title              VARCHAR(255) NOT NULL,
  description        TEXT NULL,
  filename           VARCHAR(255) NOT NULL,               -- randomized name stored on disk
  original_filename  VARCHAR(255) NOT NULL,               -- original uploaded filename
  file_path          VARCHAR(500) NOT NULL,               -- path relative to /public, e.g. uploads/videos/xxx.mp4
  mime_type          VARCHAR(100) NOT NULL,
  file_size          INTEGER NOT NULL CHECK (file_size >= 0),  -- size in bytes
  duration_seconds   INTEGER NULL CHECK (duration_seconds >= 0),
  thumbnail_path     VARCHAR(500) NULL,
  status             VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'deleted')),
  created_at         TIMESTAMP NULL,
  updated_at         TIMESTAMP NULL,
  deleted_at         TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS idx_videos_status     ON videos (status);
CREATE INDEX IF NOT EXISTS idx_videos_created_at ON videos (created_at);


-- ============================================================
--  Important Files vault (private Supabase Storage metadata)
-- ============================================================

CREATE TABLE IF NOT EXISTS important_files (
  id                     SERIAL PRIMARY KEY,
  title                  VARCHAR(255) NOT NULL,
  description            TEXT NULL,
  category               VARCHAR(100) NULL,
  stored_filename        VARCHAR(255) NOT NULL,
  original_filename      VARCHAR(255) NOT NULL,
  file_path              VARCHAR(500) NOT NULL,
  file_extension         VARCHAR(20) NULL,
  mime_type              VARCHAR(150) NOT NULL,
  file_size              BIGINT NOT NULL CHECK (file_size > 0),
  checksum_sha256        CHAR(64) NULL,
  upload_token_hash      CHAR(64) NULL,
  status                 VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'deleted', 'failed')),
  document_date          DATE NULL,
  expires_at             DATE NULL,
  reminder_days          SMALLINT NOT NULL DEFAULT 30 CHECK (reminder_days BETWEEN 0 AND 3650),
  expiration_reminded_at TIMESTAMP NULL,
  is_favorite            BOOLEAN NOT NULL DEFAULT FALSE,
  download_count         INTEGER NOT NULL DEFAULT 0 CHECK (download_count >= 0),
  last_downloaded_at     TIMESTAMP NULL,
  finalized_at           TIMESTAMP NULL,
  created_at             TIMESTAMP NULL,
  updated_at             TIMESTAMP NULL,
  deleted_at             TIMESTAMP NULL,
  purge_at               TIMESTAMP NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_stored_filename ON important_files (stored_filename);
CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_file_path ON important_files (file_path);
CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_upload_token_hash ON important_files (upload_token_hash) WHERE upload_token_hash IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_important_files_status ON important_files (status);
CREATE INDEX IF NOT EXISTS idx_important_files_created_at ON important_files (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_important_files_category ON important_files (category);
CREATE INDEX IF NOT EXISTS idx_important_files_extension ON important_files (file_extension);
CREATE INDEX IF NOT EXISTS idx_important_files_expires_at ON important_files (expires_at);
CREATE INDEX IF NOT EXISTS idx_important_files_purge_at ON important_files (purge_at);
CREATE INDEX IF NOT EXISTS idx_important_files_favorite ON important_files (is_favorite);

CREATE TABLE IF NOT EXISTS important_file_audits (
  id            BIGSERIAL PRIMARY KEY,
  file_id       INTEGER NULL REFERENCES important_files(id) ON DELETE SET NULL,
  action        VARCHAR(80) NOT NULL,
  details       JSONB NULL,
  actor_ip_hash VARCHAR(32) NULL,
  user_agent    VARCHAR(255) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_file_audits_file_id ON important_file_audits (file_id);
CREATE INDEX IF NOT EXISTS idx_file_audits_action ON important_file_audits (action);
CREATE INDEX IF NOT EXISTS idx_file_audits_created_at ON important_file_audits (created_at DESC);

-- ============================================================
--  Sessions table — required when deployed on Vercel, since its
--  filesystem is read-only and can't use CodeIgniter's default
--  file-based session driver. See app/Config/Session.php.
-- ============================================================

CREATE TABLE IF NOT EXISTS ci_sessions (
  id         VARCHAR(128) NOT NULL PRIMARY KEY,
  ip_address VARCHAR(45)  NOT NULL,
  timestamp  TIMESTAMP    DEFAULT NOW() NOT NULL,
  data       BYTEA        DEFAULT '' NOT NULL
);

CREATE INDEX IF NOT EXISTS ci_sessions_timestamp ON ci_sessions (timestamp);

-- Optional sample row — replace file_path with a real uploaded file, or delete this.
-- INSERT INTO videos (title, description, filename, original_filename, file_path, mime_type, file_size, status, created_at, updated_at)
-- VALUES ('Sample Clip', 'Test upload', 'sample.mp4', 'sample.mp4', 'uploads/videos/sample.mp4', 'video/mp4', 1048576, 'active', NOW(), NOW());

-- Public-link metadata for individually shared vault files and folders.
CREATE TABLE IF NOT EXISTS important_file_shares (
  id              BIGSERIAL PRIMARY KEY,
  share_type      VARCHAR(10) NOT NULL DEFAULT 'file' CHECK (share_type IN ('file', 'folder')),
  file_id         INTEGER NULL REFERENCES important_files(id) ON DELETE CASCADE,
  folder_path     VARCHAR(1000) NULL,
  token_hash      CHAR(64) NOT NULL,
  token_ciphertext TEXT NULL,
  expires_at      TIMESTAMP NULL,
  max_downloads   INTEGER NULL CHECK (max_downloads IS NULL OR max_downloads BETWEEN 1 AND 10000),
  share_title     VARCHAR(255) NULL,
  share_message   TEXT NULL,
  display_name    VARCHAR(100) NULL,
  notify_first_open BOOLEAN NOT NULL DEFAULT FALSE,
  notify_download_limit BOOLEAN NOT NULL DEFAULT FALSE,
  notify_expiring BOOLEAN NOT NULL DEFAULT FALSE,
  first_open_notified_at TIMESTAMP NULL,
  limit_notified_at TIMESTAMP NULL,
  expiring_notified_at TIMESTAMP NULL,
  view_count      INTEGER NOT NULL DEFAULT 0 CHECK (view_count >= 0),
  download_count  INTEGER NOT NULL DEFAULT 0 CHECK (download_count >= 0),
  last_accessed_at TIMESTAMP NULL,
  revoked_at      TIMESTAMP NULL,
  created_by      VARCHAR(100) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMP NOT NULL DEFAULT NOW(),
  CONSTRAINT important_file_shares_target_check CHECK (
    (share_type = 'file' AND file_id IS NOT NULL AND folder_path IS NULL) OR
    (share_type = 'folder' AND file_id IS NULL AND folder_path IS NOT NULL AND folder_path <> '')
  )
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_important_file_shares_token_hash ON important_file_shares (token_hash);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_file_id ON important_file_shares (file_id);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_expires_at ON important_file_shares (expires_at);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_active ON important_file_shares (file_id, revoked_at, expires_at);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_folder_path ON important_file_shares (folder_path);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_folder_active ON important_file_shares (share_type, folder_path, revoked_at, expires_at);
ALTER TABLE important_file_shares ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE important_file_shares FROM anon, authenticated;
REVOKE ALL ON SEQUENCE important_file_shares_id_seq FROM anon, authenticated;


-- Public-share activity and pending archive sessions.
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
);
CREATE INDEX IF NOT EXISTS idx_share_events_share_created ON important_file_share_events (share_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_share_events_type ON important_file_share_events (event_type);
CREATE INDEX IF NOT EXISTS idx_share_events_notification ON important_file_share_events (share_id, is_notification, read_at);
ALTER TABLE important_file_share_events ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE important_file_share_events FROM anon, authenticated;
REVOKE ALL ON SEQUENCE important_file_share_events_id_seq FROM anon, authenticated;

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
);
CREATE INDEX IF NOT EXISTS idx_share_download_sessions_share ON important_file_share_download_sessions (share_id, status, expires_at);
ALTER TABLE important_file_share_download_sessions ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE important_file_share_download_sessions FROM anon, authenticated;
REVOKE ALL ON SEQUENCE important_file_share_download_sessions_id_seq FROM anon, authenticated;
