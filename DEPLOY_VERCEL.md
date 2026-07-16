# Deploying this project to Vercel

This CI4 app runs on Vercel via the community PHP runtime. Two things had to
change from a normal Apache/UniServer setup:

1. **Routing** — `api/index.php` + `vercel.json` route all requests through
   a single PHP function (Vercel's model), instead of Apache's `.htaccess`.
2. **File storage** — uploaded videos go to **Supabase Storage** instead of
   `public/uploads/videos/`, because Vercel's filesystem is read-only at
   request time (only `/tmp` is writable, and it doesn't persist between
   requests).

## 1. Push to GitHub

Commit and push this project (including the new `api/`, `vercel.json`,
`.vercelignore` files) to a GitHub repo.

## 2. Set up Supabase Storage

In your Supabase dashboard:

1. Go to **Storage** → **Create a new bucket**.
2. Name it `videos`, and toggle **Public bucket** ON (the video player
   streams directly from the public URL).
3. Go to **Project Settings → API** and copy:
   - **Project URL** (e.g. `https://xxxxxxxx.supabase.co`)
   - **service_role key** (⚠️ not the `anon` key — this needs write access)

## 3. Run the database schema

In the Supabase SQL Editor, run `database/schema.postgres.sql` from this repo
if you haven't already.

## 4. Import the repo into Vercel

In the Vercel dashboard: **Add New → Project**, import your GitHub repo.
Vercel will detect `vercel.json` and use the PHP runtime automatically.

## 5. Set environment variables

In **Project → Settings → Environment Variables**, add:

```
CI_ENVIRONMENT=production

database.default.hostname=aws-0-<region>.pooler.supabase.com
database.default.database=postgres
database.default.username=postgres.<project-ref>
database.default.password=<your-db-password>
database.default.DBDriver=Postgre
database.default.port=6543
database.default.sslmode=require

SUPABASE_URL=https://xxxxxxxx.supabase.co
SUPABASE_SERVICE_KEY=<your service_role key>
SUPABASE_BUCKET=videos
```

Note: `database.default.port=6543` is the **Transaction pooler** port — use
this, not 5432, since serverless functions open many short-lived DB
connections and the pooler handles that gracefully.

## 6. Deploy

Trigger a deploy (push to GitHub, or click Deploy in the dashboard). Once
live, uploading a video will:

1. Land in Vercel's `/tmp` scratch space temporarily (via the file upload).
2. Get pushed to your Supabase `videos` bucket over the Storage REST API.
3. Store the resulting public Supabase URL in the `videos.file_path` column.

## Known limitations of this setup

- **200MB upload cap**: Vercel serverless functions have a request body
  size limit (varies by plan, historically 4.5MB–~250MB depending on
  config). Very large video uploads may need a different upload path
  (e.g. uploading directly from the browser to Supabase Storage using a
  signed URL) if you hit this limit in practice.
- **Function duration**: `maxDuration` is set to 30s in `vercel.json`.
  Slow uploads on a large file + slow connection could time out; raise
  this if needed (subject to your Vercel plan's limits).
