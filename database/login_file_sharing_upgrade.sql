-- Damon Archive: website login + Important Files share links
-- Run this ONCE in Supabase SQL Editor on the existing deployed database.
-- Website login itself uses Vercel environment variables and does not require a users table.

BEGIN;

CREATE TABLE IF NOT EXISTS public.important_file_shares (
    id BIGSERIAL PRIMARY KEY,
    file_id INTEGER NOT NULL REFERENCES public.important_files(id) ON DELETE CASCADE,
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
    CONSTRAINT important_file_shares_max_downloads_check
        CHECK (max_downloads IS NULL OR max_downloads BETWEEN 1 AND 10000),
    CONSTRAINT important_file_shares_view_count_check CHECK (view_count >= 0),
    CONSTRAINT important_file_shares_download_count_check CHECK (download_count >= 0)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_important_file_shares_token_hash
    ON public.important_file_shares (token_hash);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_file_id
    ON public.important_file_shares (file_id);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_expires_at
    ON public.important_file_shares (expires_at);
CREATE INDEX IF NOT EXISTS idx_important_file_shares_active
    ON public.important_file_shares (file_id, revoked_at, expires_at);

-- The application accesses this table through the server-side PostgreSQL
-- connection. Anonymous Supabase REST clients must not read share records.
ALTER TABLE public.important_file_shares ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.important_file_shares FROM anon, authenticated;
REVOKE ALL ON SEQUENCE public.important_file_shares_id_seq FROM anon, authenticated;

COMMIT;
