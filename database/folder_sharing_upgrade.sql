-- Damon Archive: Important Files folder sharing
-- Run this ONCE in Supabase SQL Editor after login_file_sharing_upgrade.sql.

BEGIN;

ALTER TABLE public.important_file_shares
    ADD COLUMN IF NOT EXISTS share_type VARCHAR(10) NOT NULL DEFAULT 'file',
    ADD COLUMN IF NOT EXISTS folder_path VARCHAR(1000) NULL;

-- Existing share rows are individual-file links.
UPDATE public.important_file_shares
SET share_type = 'file'
WHERE share_type IS NULL OR share_type = '';

-- Folder shares have no single file_id, so this column must be nullable.
ALTER TABLE public.important_file_shares
    ALTER COLUMN file_id DROP NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'important_file_shares_target_check'
          AND conrelid = 'public.important_file_shares'::regclass
    ) THEN
        ALTER TABLE public.important_file_shares
            ADD CONSTRAINT important_file_shares_target_check CHECK (
                (share_type = 'file' AND file_id IS NOT NULL AND folder_path IS NULL)
                OR
                (share_type = 'folder' AND file_id IS NULL AND folder_path IS NOT NULL AND folder_path <> '')
            );
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'important_file_shares_type_check'
          AND conrelid = 'public.important_file_shares'::regclass
    ) THEN
        ALTER TABLE public.important_file_shares
            ADD CONSTRAINT important_file_shares_type_check
            CHECK (share_type IN ('file', 'folder'));
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_important_file_shares_folder_path
    ON public.important_file_shares (folder_path);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_folder_active
    ON public.important_file_shares (share_type, folder_path, revoked_at, expires_at);

-- Keep the table private from Supabase REST clients.
ALTER TABLE public.important_file_shares ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.important_file_shares FROM anon, authenticated;
REVOKE ALL ON SEQUENCE public.important_file_shares_id_seq FROM anon, authenticated;

COMMIT;
