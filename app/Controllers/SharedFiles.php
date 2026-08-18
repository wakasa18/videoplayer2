<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\FileAuditModel;
use App\Models\ImportantFileModel;
use App\Models\ImportantFileShareModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Supabase as SupabaseConfig;
use Throwable;

class SharedFiles extends BaseController
{
    protected $helpers = ['url'];

    private ImportantFileShareModel $shareModel;
    private ImportantFileModel $fileModel;
    private FileAuditModel $auditModel;

    public function __construct()
    {
        $this->shareModel = new ImportantFileShareModel();
        $this->fileModel  = new ImportantFileModel();
        $this->auditModel = new FileAuditModel();
    }

    public function show(string $token)
    {
        $resolved = $this->resolve($token);
        if ($resolved === null) {
            return $this->unavailable();
        }

        [$share, $file] = $resolved;
        $this->shareModel->recordView((int) $share['id']);
        $this->audit('shared_file_opened', (int) $file['id'], ['share_id' => (int) $share['id']]);

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody(view('shares/show', [
                'share'       => $share,
                'file'        => $file,
                'token'       => $token,
                'previewKind' => ImportantFileModel::previewKind($file),
            ]));
    }

    public function preview(string $token): ResponseInterface
    {
        $resolved = $this->resolve($token);
        if ($resolved === null) {
            return $this->response
                ->setStatusCode(404)
                ->setHeader('Cache-Control', 'no-store, max-age=0')
                ->setBody('This shared file is unavailable.');
        }

        [$share, $file] = $resolved;
        $kind = ImportantFileModel::previewKind($file);
        if ($kind === 'unsupported') {
            return $this->response
                ->setStatusCode(404)
                ->setHeader('Cache-Control', 'no-store, max-age=0')
                ->setBody('Preview is not available for this file type.');
        }

        $range = trim($this->request->getHeaderLine('Range'));
        if ($range !== '' && ! preg_match('/^bytes=\d*-\d*$/', $range)) {
            $range = '';
        }
        if ($kind === 'text' && $range === '' && (int) $file['file_size'] > 2 * 1024 * 1024) {
            $range = 'bytes=0-2097151';
        }

        try {
            $bucket = config(SupabaseConfig::class)->filesBucket;
            $object = (new SupabaseStorage($bucket))->downloadObject($file['file_path'], $range !== '' ? $range : null);
        } catch (Throwable $e) {
            log_message('error', 'Shared file preview failed: {message}', ['message' => $e->getMessage()]);
            return $this->response
                ->setStatusCode(502)
                ->setHeader('Cache-Control', 'no-store, max-age=0')
                ->setBody('The shared preview could not be loaded.');
        }

        $mimeType = ImportantFileModel::previewMimeType($file, (string) ($object['contentType'] ?? ''));
        $filename = basename(str_replace('\\', '/', (string) $file['original_filename']));
        $fallback = preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename) ?: 'preview';
        $fallback = str_replace(['"', "\r", "\n"], '', $fallback);
        $disposition = 'inline; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);

        $response = $this->response
            ->setStatusCode((int) $object['status'])
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', $disposition)
            ->setHeader('Content-Length', (string) strlen($object['body']))
            ->setHeader('Accept-Ranges', $object['acceptRanges'] ?: 'bytes')
            ->setHeader('Cache-Control', 'no-store, private, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody($object['body']);

        if ($object['contentRange'] !== '') {
            $response->setHeader('Content-Range', $object['contentRange']);
        }
        if ($kind === 'text' && (int) $file['file_size'] > 2 * 1024 * 1024) {
            $response->setHeader('X-Preview-Truncated', 'true');
        }

        return $response;
    }

    public function download(string $token)
    {
        $resolved = $this->resolve($token);
        if ($resolved === null) {
            return $this->unavailable();
        }

        [$share, $file] = $resolved;

        try {
            $bucket = config(SupabaseConfig::class)->filesBucket;
            $url    = (new SupabaseStorage($bucket))->createSignedDownloadUrl($file['file_path'], 120);
        } catch (Throwable $e) {
            log_message('error', 'Shared file signed download failed: {message}', ['message' => $e->getMessage()]);
            return $this->response
                ->setStatusCode(502)
                ->setHeader('Cache-Control', 'no-store, max-age=0')
                ->setBody(view('shares/unavailable', ['message' => 'The download could not be prepared. Please try again.']));
        }

        if (! $this->shareModel->claimDownload((int) $share['id'])) {
            return $this->unavailable('This share link has reached its download limit or is no longer active.');
        }

        $this->fileModel->recordDownload((int) $file['id']);
        $this->audit('shared_file_downloaded', (int) $file['id'], ['share_id' => (int) $share['id']]);

        return redirect()->to($url);
    }

    private function resolve(string $token): ?array
    {
        $share = $this->shareModel->findUsableByToken($token);
        if (! $share) {
            return null;
        }

        $file = $this->fileModel->find((int) $share['file_id']);
        if (! $file || $file['status'] !== 'active') {
            return null;
        }

        return [$share, $file];
    }

    private function unavailable(string $message = 'This share link is invalid, expired, revoked, or has reached its download limit.')
    {
        return $this->response
            ->setStatusCode(404)
            ->setHeader('Cache-Control', 'no-store, private, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody(view('shares/unavailable', ['message' => $message]));
    }

    private function audit(string $action, ?int $fileId = null, array $details = []): void
    {
        try {
            $this->auditModel->logAction($action, $fileId, $details);
        } catch (Throwable $e) {
            log_message('error', 'Shared file audit failed: {message}', ['message' => $e->getMessage()]);
        }
    }
}
