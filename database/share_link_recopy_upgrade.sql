-- Repeat-copy support for Important Files share links
-- Run this once in Supabase SQL Editor on the existing deployed database.

BEGIN;

ALTER TABLE public.important_file_shares
    ADD COLUMN IF NOT EXISTS token_ciphertext TEXT;

COMMENT ON COLUMN public.important_file_shares.token_ciphertext IS
    'Application-encrypted share token used only to let an authenticated vault owner copy a link again.';

ALTER TABLE public.important_file_shares ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.important_file_shares FROM anon, authenticated;
REVOKE ALL ON SEQUENCE public.important_file_shares_id_seq FROM anon, authenticated;

COMMIT;
