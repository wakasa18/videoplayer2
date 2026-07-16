<?php

namespace App\Libraries;

use Config\Supabase as SupabaseConfig;
use RuntimeException;

/**
 * Minimal client for the Supabase Storage REST API.
 *
 * We talk to the REST API directly over cURL instead of pulling in an SDK,
 * since this needs to stay light and dependency-free to deploy cleanly on
 * Vercel's PHP runtime (and Vercel's filesystem is read-only at request
 * time, so we can't rely on writing uploads to local disk like the
 * original UniServer-hosted version did).
 */
class SupabaseStorage
{
    protected string $baseUrl;
    protected string $serviceKey;
    protected string $bucket;

    public function __construct()
    {
        /** @var SupabaseConfig $config */
        $config = config(SupabaseConfig::class);

        $this->baseUrl    = rtrim($config->url, '/');
        $this->serviceKey = $config->serviceKey;
        $this->bucket     = $config->bucket;

        if ($this->baseUrl === '' || $this->serviceKey === '') {
            throw new RuntimeException(
                'Supabase storage is not configured. Set the SUPABASE_URL and SUPABASE_SERVICE_KEY environment variables.'
            );
        }
    }

    /**
     * Upload a local file into the bucket and return its public URL.
     */
    public function upload(string $localPath, string $remotePath, string $mimeType): string
    {
        $fileContents = file_get_contents($localPath);
        if ($fileContents === false) {
            throw new RuntimeException("Could not read local file: {$localPath}");
        }

        $url = sprintf(
            '%s/storage/v1/object/%s/%s',
            $this->baseUrl,
            $this->bucket,
            ltrim($remotePath, '/')
        );

        [$status, $response, $error] = $this->request('POST', $url, $fileContents, [
            'Content-Type: ' . $mimeType,
            'x-upsert: true',
        ]);

        if ($error !== '') {
            throw new RuntimeException("Supabase upload failed: {$error}");
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Supabase upload failed with status {$status}: {$response}");
        }

        return $this->publicUrl($remotePath);
    }

    /**
     * Remove a file from the bucket. Returns true on success; false (not an
     * exception) if it fails, since callers generally shouldn't block a DB
     * delete just because the storage cleanup had an issue.
     */
    public function delete(string $remotePath): bool
    {
        $url = sprintf(
            '%s/storage/v1/object/%s/%s',
            $this->baseUrl,
            $this->bucket,
            ltrim($remotePath, '/')
        );

        [$status, , $error] = $this->request('DELETE', $url);

        return $error === '' && $status >= 200 && $status < 300;
    }

    /**
     * Build the public URL for a stored object. Requires the bucket to be
     * marked "public" in the Supabase dashboard.
     */
    public function publicUrl(string $remotePath): string
    {
        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            $this->baseUrl,
            $this->bucket,
            ltrim($remotePath, '/')
        );
    }

    /**
     * Create a short-lived signed URL the browser can upload directly to,
     * bypassing our own backend entirely for the actual file bytes. This is
     * required on Vercel (and just generally a good idea everywhere): Vercel
     * serverless functions hard-cap request bodies at 4.5MB, which any real
     * video file blows past instantly. The browser talks straight to
     * Supabase instead; our backend only ever handles small JSON metadata.
     *
     * @return array{uploadUrl: string, publicUrl: string}
     */
    public function createSignedUploadUrl(string $remotePath): array
    {
        $url = sprintf(
            '%s/storage/v1/object/upload/sign/%s/%s',
            $this->baseUrl,
            $this->bucket,
            ltrim($remotePath, '/')
        );

        [$status, $response, $error] = $this->request('POST', $url, '{}', [
            'Content-Type: application/json',
        ]);

        if ($error !== '') {
            throw new RuntimeException("Supabase signed upload URL request failed: {$error}");
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Supabase signed upload URL request failed with status {$status}: {$response}");
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded) || ! isset($decoded['url'])) {
            throw new RuntimeException('Supabase signed upload URL response was missing the expected "url" field.');
        }

        return [
            // $decoded['url'] is a path like "/object/upload/sign/videos/xxx.mp4?token=...".
            // The browser needs the full absolute URL to PUT to.
            'uploadUrl' => $this->baseUrl . '/storage/v1' . $decoded['url'],
            'publicUrl' => $this->publicUrl($remotePath),
        ];
    }

    /**
     * @return array{0: int, 1: string, 2: string} [httpStatus, body, curlError]
     */
    protected function request(string $method, string $url, ?string $body = null, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Authorization: Bearer ' . $this->serviceKey,
        ], $extraHeaders);

        $ch = curl_init($url);

        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return [$status, $response === false ? '' : $response, $error];
    }
}
