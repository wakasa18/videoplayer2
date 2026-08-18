# Important Files Vault Upgrade

## Deploy in this order

1. Open the Supabase SQL Editor.
2. Run `database/important_files_upgrade.sql` once.
3. Confirm that the private Storage bucket configured by `SUPABASE_FILES_BUCKET` exists and is not public.
4. Add or confirm these Vercel environment variables:
   - `SUPABASE_FILES_BUCKET=important-files`
   - `FILES_ACCESS_PASSWORD=<your private password>`
   - `FILES_MAX_UPLOAD_MB=50`
   - `CRON_SECRET=<random secret>`
5. Deploy the revised project.
6. Open `/files`, unlock the vault, and test a small PDF or image first.
7. Test Preview, Edit Details, Move to Recycle Bin, Restore, and Permanent Delete.

## Added features

- CSRF protection for all Important Files routes
- Server-created pending upload records and one-time upload tokens
- Extension, MIME, size, stored-object, and file-signature validation
- Multiple-file upload with progress, cancel, retry, and in-page success updates
- Search, category/type/expiration filters, sorting, favorites, and pagination
- PDF/image/text preview modal
- Metadata editing, document date, expiration date, and reminder period
- 30-day Recycle Bin with restore and safe permanent deletion
- Activity log with privacy-preserving hashed IP values
- Daily cleanup of abandoned uploads and expired recycle records
- Email expiration reminders when SMTP settings are configured

## Daily Vercel cron

`/cron/files-maintenance` runs at `0 0 * * *` UTC. It:

- Removes pending or failed uploads older than one hour
- Permanently deletes Recycle Bin items whose 30-day retention has ended
- Sends a digest for expired or soon-to-expire documents

## Notes

- The real `.env` was removed from the revised ZIP. Use `.env.example` locally and Vercel Environment Variables in production.
- The supplied `schema_updated.sql` is the original exported database followed by the vault upgrade. For an existing deployment, the smaller `database/important_files_upgrade.sql` is safer and faster to run.
