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
