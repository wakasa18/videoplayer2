<?php

namespace App\Libraries;

use Config\Supabase as SupabaseConfig;
use RuntimeException;

class SupabaseStorage
{
    protected string $baseUrl;
    protected string $serviceKey;
    protected string $bucket;

    public function __construct(?string $bucketOverride = null)
    {
        /** @var SupabaseConfig $config */
        $config = config(SupabaseConfig::class);

        $this->baseUrl    = rtrim($config->url, '/');
        $this->serviceKey = $config->serviceKey;
        $this->bucket     = $bucketOverride ?? $config->bucket;

        if ($this->baseUrl === '' || $this->serviceKey === '' || $this->bucket === '') {
            throw new RuntimeException('Supabase storage is not configured.');
        }
    }

    public function upload(string $localPath, string $remotePath, string $mimeType): string
    {
        $fileContents = file_get_contents($localPath);
        if ($fileContents === false) {
            throw new RuntimeException("Could not read local file: {$localPath}");
        }

        $url = $this->objectUrl($remotePath);
        [$status, $response, $error] = $this->request('POST', $url, $fileContents, [
            'Content-Type: ' . $mimeType,
            'x-upsert: true',
        ]);

        if ($error !== '' || $status < 200 || $status >= 300) {
            throw new RuntimeException("Supabase upload failed with status {$status}: " . ($error ?: $response));
        }

        return $this->publicUrl($remotePath);
    }

    public function delete(string $remotePath): bool
    {
        [$status, , $error] = $this->request('DELETE', $this->objectUrl($remotePath));

        return $error === '' && $status >= 200 && $status < 300;
    }

    public function publicUrl(string $remotePath): string
    {
        return sprintf(
            '%s/storage/v1/object/public/%s/%s',
            $this->baseUrl,
            rawurlencode($this->bucket),
            $this->encodePath($remotePath)
        );
    }

    public function createSignedUploadUrl(string $remotePath): array
    {
        $url = sprintf(
            '%s/storage/v1/object/upload/sign/%s/%s',
            $this->baseUrl,
            rawurlencode($this->bucket),
            $this->encodePath($remotePath)
        );

        [$status, $response, $error] = $this->request('POST', $url, '{}', ['Content-Type: application/json']);
        if ($error !== '' || $status < 200 || $status >= 300) {
            throw new RuntimeException("Supabase signed upload URL request failed with status {$status}: " . ($error ?: $response));
        }

        $decoded = json_decode($response, true);
        if (! is_array($decoded) || ! isset($decoded['url'])) {
            throw new RuntimeException('Supabase signed upload URL response was missing the expected URL.');
        }

        return [
            'uploadUrl' => $this->baseUrl . '/storage/v1' . $decoded['url'],
            'publicUrl' => $this->publicUrl($remotePath),
        ];
    }

    public function createSignedDownloadUrl(string $remotePath, int $expiresInSeconds = 120): string
    {
        $url = sprintf(
            '%s/storage/v1/object/sign/%s/%s',
            $this->baseUrl,
            rawurlencode($this->bucket),
            $this->encodePath($remotePath)
        );

        [$status, $response, $error] = $this->request(
            'POST',
            $url,
            json_encode(['expiresIn' => max(1, $expiresInSeconds)]),
            ['Content-Type: application/json']
        );

        if ($error !== '' || $status < 200 || $status >= 300) {
            throw new RuntimeException("Supabase signed download URL request failed with status {$status}: " . ($error ?: $response));
        }

        $decoded = json_decode($response, true);
        if (! is_array($decoded) || ! isset($decoded['signedURL'])) {
            throw new RuntimeException('Supabase signed download URL response was missing the expected signedURL.');
        }

        return $this->baseUrl . '/storage/v1' . $decoded['signedURL'];
    }

    /**
     * Create signed URLs for several private objects in one Storage API call.
     *
     * @return array<int, array{path:string,signedUrl:?string,error:?string}>
     */
    public function createSignedDownloadUrls(array $remotePaths, int $expiresInSeconds = 7200): array
    {
        $paths = array_values(array_unique(array_filter(array_map(
            static fn ($path): string => trim((string) $path),
            $remotePaths
        ), static fn (string $path): bool => $path !== '')));

        if ($paths === []) {
            return [];
        }

        $url = sprintf(
            '%s/storage/v1/object/sign/%s',
            $this->baseUrl,
            rawurlencode($this->bucket)
        );

        $body = json_encode([
            'expiresIn' => max(1, $expiresInSeconds),
            'paths'     => $paths,
        ], JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new RuntimeException('Could not encode the signed URL request.');
        }

        [$status, $response, $error] = $this->request('POST', $url, $body, ['Content-Type: application/json']);
        if ($error !== '' || $status < 200 || $status >= 300) {
            throw new RuntimeException("Supabase batch signed URL request failed with status {$status}: " . ($error ?: $response));
        }

        $decoded = json_decode($response, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Supabase batch signed URL response was invalid.');
        }

        $results = [];
        foreach ($decoded as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $path      = (string) ($item['path'] ?? ($paths[$index] ?? ''));
            $signedURL = (string) ($item['signedURL'] ?? $item['signedUrl'] ?? '');
            $message   = trim((string) ($item['error'] ?? ''));
            $fullUrl   = null;

            if ($signedURL !== '') {
                $fullUrl = preg_match('~^https?://~i', $signedURL)
                    ? $signedURL
                    : $this->baseUrl . '/storage/v1' . $signedURL;
            }

            $results[] = [
                'path'      => $path,
                'signedUrl' => $fullUrl,
                'error'     => $message !== '' ? $message : null,
            ];
        }

        return $results;
    }

    /**
     * Verify that a private object exists and return its stored metadata.
     * A short-lived signed URL is used so no service key leaves the server.
     *
     * @return array{exists: bool, size: int, contentType: string, status: int}
     */
    public function getObjectInfo(string $remotePath): array
    {
        $signedUrl = $this->createSignedDownloadUrl($remotePath, 60);
        $headers   = [];
        $status    = 0;
        $error     = '';

        // Prefer HEAD because it does not transfer the object body.
        $ch = curl_init($signedUrl);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                $length = strlen($line);
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }
                return $length;
            },
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        $size = isset($headers['content-length']) ? (int) $headers['content-length'] : 0;

        // Some storage/CDN layers omit Content-Length on HEAD. Fall back to
        // a one-byte range request and read the total from Content-Range.
        if ($error !== '' || $status < 200 || $status >= 300 || $size <= 0) {
            $headers  = [];
            $received = 0;
            $ch = curl_init($signedUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_RANGE          => '0-0',
                CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$headers): int {
                    $length = strlen($line);
                    if (str_contains($line, ':')) {
                        [$name, $value] = explode(':', $line, 2);
                        $headers[strtolower(trim($name))] = trim($value);
                    }
                    return $length;
                },
                CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$received): int {
                    $received += strlen($chunk);
                    return strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error  = curl_error($ch);
            curl_close($ch);

            if (isset($headers['content-range']) && preg_match('~/([0-9]+)$~', $headers['content-range'], $match)) {
                $size = (int) $match[1];
            } elseif (isset($headers['content-length'])) {
                $size = (int) $headers['content-length'];
            }
        }

        if ($error !== '') {
            throw new RuntimeException('Could not verify the uploaded object: ' . $error);
        }

        return [
            'exists'      => $status >= 200 && $status < 300,
            'size'        => $size,
            'contentType' => strtolower(trim(explode(';', $headers['content-type'] ?? '')[0])),
            'status'      => $status,
        ];
    }

    /**
     * Read only the first few bytes of a private object for file-signature validation.
     */
    public function readObjectPrefix(string $remotePath, int $bytes = 64): string
    {
        $bytes     = max(8, min($bytes, 512));
        $signedUrl = $this->createSignedDownloadUrl($remotePath, 60);
        $data      = '';
        $ch        = curl_init($signedUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_RANGE          => '0-' . ($bytes - 1),
            CURLOPT_WRITEFUNCTION  => static function ($curl, string $chunk) use (&$data, $bytes): int {
                $remaining = $bytes - strlen($data);
                if ($remaining <= 0) {
                    return 0;
                }
                $data .= substr($chunk, 0, $remaining);
                return strlen($chunk) > $remaining ? 0 : strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ((! in_array($status, [200, 206], true)) || ($error !== '' && strlen($data) < $bytes)) {
            throw new RuntimeException('Could not inspect the uploaded object.');
        }

        return substr($data, 0, $bytes);
    }

    /**
     * Fetch a private object through the authenticated Storage API.
     * Supports a single HTTP byte range so browser PDF viewers can request
     * only the portions they need instead of downloading the whole file again.
     *
     * @return array{status:int,body:string,contentType:string,contentRange:string,acceptRanges:string}
     */
    public function downloadObject(string $remotePath, ?string $range = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey,
            'apikey: ' . $this->serviceKey,
        ];

        if ($range !== null && preg_match('/^bytes=\d*-\d*$/', trim($range))) {
            $headers[] = 'Range: ' . trim($range);
        }

        $responseHeaders = [];
        $ch = curl_init($this->objectUrl($remotePath));
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                $trimmed = trim($line);

                if (str_starts_with($trimmed, 'HTTP/')) {
                    $responseHeaders = [];
                    return $length;
                }

                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }

                return $length;
            },
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '' || ! in_array($status, [200, 206], true)) {
            throw new RuntimeException(
                'Could not load the private file preview' . ($status > 0 ? " (status {$status})" : '') . '.'
            );
        }

        return [
            'status'       => $status,
            'body'         => $body,
            'contentType'  => strtolower(trim(explode(';', $responseHeaders['content-type'] ?? '')[0])),
            'contentRange' => (string) ($responseHeaders['content-range'] ?? ''),
            'acceptRanges' => (string) ($responseHeaders['accept-ranges'] ?? 'bytes'),
        ];
    }

    protected function objectUrl(string $remotePath): string
    {
        return sprintf(
            '%s/storage/v1/object/%s/%s',
            $this->baseUrl,
            rawurlencode($this->bucket),
            $this->encodePath($remotePath)
        );
    }

    protected function encodePath(string $remotePath): string
    {
        $segments = array_filter(explode('/', ltrim($remotePath, '/')), static fn ($part) => $part !== '');

        return implode('/', array_map('rawurlencode', $segments));
    }

    /** @return array{0:int,1:string,2:string} */
    protected function request(string $method, string $url, ?string $body = null, array $extraHeaders = []): array
    {
        $headers = array_merge([
            'Authorization: Bearer ' . $this->serviceKey,
            'apikey: ' . $this->serviceKey,
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
