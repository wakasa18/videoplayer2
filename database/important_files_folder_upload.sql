-- Folder upload support for the already-deployed Important Files vault.
-- Run this once in Supabase SQL Editor before deploying the folder-upload files.

BEGIN;

ALTER TABLE public.important_files
  ADD COLUMN IF NOT EXISTS folder_path VARCHAR(1000);

CREATE INDEX IF NOT EXISTS idx_important_files_folder_path
  ON public.important_files (folder_path);

COMMIT;
