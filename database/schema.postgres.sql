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
