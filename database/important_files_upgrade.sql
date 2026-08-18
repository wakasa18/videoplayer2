-- Important Files Vault upgrade for the already-deployed Supabase database.
-- Run this once in Supabase SQL Editor before deploying the revised project.

BEGIN;

ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS file_extension VARCHAR(20);
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS checksum_sha256 CHAR(64);
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS upload_token_hash CHAR(64);
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS document_date DATE;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS expires_at DATE;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS reminder_days SMALLINT NOT NULL DEFAULT 30;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS expiration_reminded_at TIMESTAMP NULL;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS is_favorite BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS download_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS last_downloaded_at TIMESTAMP NULL;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL;
ALTER TABLE public.important_files ADD COLUMN IF NOT EXISTS purge_at TIMESTAMP NULL;
ALTER TABLE public.important_files ALTER COLUMN file_size TYPE BIGINT;

UPDATE public.important_files
SET file_extension = LOWER(SPLIT_PART(original_filename, '.', ARRAY_LENGTH(STRING_TO_ARRAY(original_filename, '.'), 1)))
WHERE (file_extension IS NULL OR file_extension = '')
  AND original_filename LIKE '%.%';

UPDATE public.important_files
SET finalized_at = COALESCE(finalized_at, created_at, NOW())
WHERE status = 'active';

CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_stored_filename ON public.important_files (stored_filename);
CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_file_path ON public.important_files (file_path);
CREATE UNIQUE INDEX IF NOT EXISTS uq_important_files_upload_token_hash ON public.important_files (upload_token_hash) WHERE upload_token_hash IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_important_files_created_at ON public.important_files (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_important_files_category ON public.important_files (category);
CREATE INDEX IF NOT EXISTS idx_important_files_extension ON public.important_files (file_extension);
CREATE INDEX IF NOT EXISTS idx_important_files_expires_at ON public.important_files (expires_at);
CREATE INDEX IF NOT EXISTS idx_important_files_purge_at ON public.important_files (purge_at);
CREATE INDEX IF NOT EXISTS idx_important_files_favorite ON public.important_files (is_favorite);

DO $$ BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_file_size_check') THEN
    ALTER TABLE public.important_files ADD CONSTRAINT important_files_file_size_check CHECK (file_size > 0);
  END IF;
END $$;
DO $$ BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_status_check') THEN
    ALTER TABLE public.important_files ADD CONSTRAINT important_files_status_check CHECK (status IN ('pending', 'active', 'deleted', 'failed'));
  END IF;
END $$;
DO $$ BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_reminder_days_check') THEN
    ALTER TABLE public.important_files ADD CONSTRAINT important_files_reminder_days_check CHECK (reminder_days BETWEEN 0 AND 3650);
  END IF;
END $$;
DO $$ BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'important_files_download_count_check') THEN
    ALTER TABLE public.important_files ADD CONSTRAINT important_files_download_count_check CHECK (download_count >= 0);
  END IF;
END $$;

CREATE TABLE IF NOT EXISTS public.important_file_audits (
  id BIGSERIAL PRIMARY KEY,
  file_id INTEGER NULL REFERENCES public.important_files(id) ON DELETE SET NULL,
  action VARCHAR(80) NOT NULL,
  details JSONB NULL,
  actor_ip_hash VARCHAR(32) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_file_audits_file_id ON public.important_file_audits (file_id);
CREATE INDEX IF NOT EXISTS idx_file_audits_action ON public.important_file_audits (action);
CREATE INDEX IF NOT EXISTS idx_file_audits_created_at ON public.important_file_audits (created_at DESC);

COMMIT;
