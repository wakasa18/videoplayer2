<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Supabase extends BaseConfig
{
    /**
     * Your project's REST/base URL, e.g. https://xxxxxxxx.supabase.co
     * Set via the SUPABASE_URL environment variable.
     */
    public string $url;

    /**
     * The service_role key (NOT the anon key — this needs write access
     * to Storage). Keep this secret; only ever set it as a server-side
     * environment variable, never commit it.
     * Set via the SUPABASE_SERVICE_KEY environment variable.
     */
    public string $serviceKey;

    /**
     * The Storage bucket videos get uploaded into. Must be a *public*
     * bucket so the video tag can stream directly from its URL.
     * Set via the SUPABASE_BUCKET environment variable (defaults to "videos").
     */
    public string $bucket;

    /**
     * The Storage bucket Important Files get uploaded into. Unlike the
     * videos bucket, this one must be marked *private* in the Supabase
     * dashboard — files are only ever reached through short-lived signed
     * URLs generated on demand, never a permanent public link.
     * Set via the SUPABASE_FILES_BUCKET environment variable (defaults to
     * "important-files").
     */
    public string $filesBucket;

    public function __construct()
    {
        parent::__construct();

        $this->url         = (string) (getenv('SUPABASE_URL') ?: '');
        $this->serviceKey  = (string) (getenv('SUPABASE_SERVICE_KEY') ?: '');
        $this->bucket      = (string) (getenv('SUPABASE_BUCKET') ?: 'videos');
        $this->filesBucket = (string) (getenv('SUPABASE_FILES_BUCKET') ?: 'important-files');
    }
}
