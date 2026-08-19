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
        $share = $this->resolveShare($token);
        if ($share === null) {
            return $this->unavailable();
        }

        if (($share['share_type'] ?? 'file') === 'folder') {
            return $this->showFolder($token, $share);
        }

        $file = $this->resolveDirectFile($share);
        if ($file === null) {
            return $this->unavailable();
        }

        $this->shareModel->recordView((int) $share['id']);
        $this->audit('shared_file_opened', (int) $file['id'], ['share_id' => (int) $share['id']]);

        return $this->renderFilePage($token, $share, $file);
    }

    public function preview(string $token): ResponseInterface
    {
        $share = $this->resolveShare($token);
        $file  = $share ? $this->resolveDirectFile($share) : null;
        if ($share === null || $file === null) {
            return $this->plainUnavailable();
        }

        return $this->streamPreview($file);
    }

    public function download(string $token)
    {
        $share = $this->resolveShare($token);
        $file  = $share ? $this->resolveDirectFile($share) : null;
        if ($share === null || $file === null) {
            return $this->unavailable();
        }

        return $this->redirectDownload($share, $file, 'shared_file_downloaded');
    }

    public function folderFile(string $token, int $fileId)
    {
        $resolved = $this->resolveFolderFile($token, $fileId);
        if ($resolved === null) {
            return $this->unavailable('This file is not available in the shared folder.');
        }

        [$share, $file] = $resolved;
        $this->shareModel->recordView((int) $share['id']);
        $this->audit('shared_folder_file_opened', (int) $file['id'], [
            'share_id'    => (int) $share['id'],
            'folder_path' => (string) $share['folder_path'],
        ]);

        $root = trim((string) $share['folder_path'], '/');
        $fileFolder = trim((string) ($file['folder_path'] ?? ''), '/');
        $relativeFolder = $fileFolder === $root ? '' : ltrim(substr($fileFolder, strlen($root)), '/');
        $backUrl = base_url('share/' . $token) . ($relativeFolder !== '' ? '?path=' . rawurlencode($relativeFolder) : '');

        return $this->renderFilePage(
            $token,
            $share,
            $file,
            base_url('share/' . $token . '/file/' . $file['id'] . '/preview'),
            base_url('share/' . $token . '/file/' . $file['id'] . '/download'),
            $backUrl
        );
    }

    public function folderFilePreview(string $token, int $fileId): ResponseInterface
    {
        $resolved = $this->resolveFolderFile($token, $fileId);
        if ($resolved === null) {
            return $this->plainUnavailable('This file is not available in the shared folder.');
        }

        return $this->streamPreview($resolved[1]);
    }

    public function folderFileDownload(string $token, int $fileId)
    {
        $resolved = $this->resolveFolderFile($token, $fileId);
        if ($resolved === null) {
            return $this->unavailable('This file is not available in the shared folder.');
        }

        return $this->redirectDownload($resolved[0], $resolved[1], 'shared_folder_file_downloaded');
    }

    public function folderManifest(string $token): ResponseInterface
    {
        $share = $this->resolveShare($token);
        if ($share === null || ($share['share_type'] ?? '') !== 'folder') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'This shared folder is unavailable.']);
        }

        $relative = $this->cleanFolderPath((string) $this->request->getGet('path'));
        if ($relative === false) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'That folder path is invalid.']);
        }

        $root = trim((string) $share['folder_path'], '/');
        $absolute = $relative === '' ? $root : $root . '/' . $relative;
        if (! $this->fileModel->folderExists($absolute)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'This folder is empty or unavailable.']);
        }

        $maxFiles = max(1, min((int) (getenv('FILES_FOLDER_DOWNLOAD_MAX_FILES') ?: 2000), 10000));
        $files = (new ImportantFileModel())->getFolderTreeFiles($absolute, $maxFiles + 1);
        if (count($files) > $maxFiles) {
            return $this->response->setStatusCode(413)->setJSON([
                'error' => 'This folder contains more than ' . number_format($maxFiles) . ' files. Download a smaller subfolder instead.',
            ]);
        }
        if ($files === []) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'This folder does not contain any downloadable files.']);
        }

        $maxBytes = max(1, min((int) (getenv('FILES_FOLDER_DOWNLOAD_MAX_MB') ?: 2048), 3800)) * 1024 * 1024;
        $requestedBytes = array_sum(array_map(static fn (array $file): int => (int) ($file['file_size'] ?? 0), $files));
        if ($requestedBytes > $maxBytes) {
            return $this->response->setStatusCode(413)->setJSON([
                'error' => 'This folder is larger than the allowed folder-download limit. Download a smaller subfolder instead.',
            ]);
        }

        try {
            $storage = new SupabaseStorage(config(SupabaseConfig::class)->filesBucket);
            $signedByPath = [];
            foreach (array_chunk($files, 100) as $chunk) {
                $paths = array_values(array_map(static fn (array $file): string => (string) $file['file_path'], $chunk));
                foreach ($storage->createSignedDownloadUrls($paths, 7200) as $result) {
                    if (($result['signedUrl'] ?? null) !== null && ($result['path'] ?? '') !== '') {
                        $signedByPath[(string) $result['path']] = (string) $result['signedUrl'];
                    }
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Shared folder signed URLs failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(502)->setJSON(['error' => 'The folder download could not be prepared.']);
        }

        $entries = [];
        $used = [];
        $ids = [];
        $total = 0;
        foreach ($files as $file) {
            $url = $signedByPath[(string) $file['file_path']] ?? null;
            if (! $url) {
                continue;
            }

            $folder = trim((string) ($file['folder_path'] ?? ''), '/');
            $relativeFolder = $folder === $absolute ? '' : ltrim(substr($folder, strlen($absolute)), '/');
            $archivePath = $this->uniqueArchivePath(
                $this->cleanArchiveFolder($relativeFolder),
                $this->cleanArchiveSegment((string) ($file['original_filename'] ?? 'file')),
                $used
            );
            $size = max(0, (int) ($file['file_size'] ?? 0));
            $entries[] = [
                'id'           => (int) $file['id'],
                'name'         => $archivePath,
                'size'         => $size,
                'url'          => $url,
                'lastModified' => (string) ($file['updated_at'] ?: $file['created_at'] ?: ''),
            ];
            $ids[] = (int) $file['id'];
            $total += $size;
        }

        if ($entries === []) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'No files in this folder are currently available in storage.']);
        }

        if (! $this->shareModel->claimDownload((int) $share['id'])) {
            return $this->response->setStatusCode(410)->setJSON(['error' => 'This share link has reached its download limit or is no longer active.']);
        }

        $this->fileModel->recordBulkDownload($ids);
        $this->audit('shared_folder_downloaded', null, [
            'share_id'    => (int) $share['id'],
            'folder_path' => $absolute,
            'file_count'  => count($entries),
            'bytes'       => $total,
        ]);

        $folderName = basename(str_replace('\\', '/', $absolute)) ?: 'shared-folder';

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private, no-transform, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setJSON([
                'archiveName' => $this->cleanArchiveSegment($folderName) . '.zip',
                'files'       => $entries,
                'fileCount'   => count($entries),
                'totalBytes'  => $total,
            ]);
    }

    private function showFolder(string $token, array $share)
    {
        $root = trim((string) ($share['folder_path'] ?? ''), '/');
        if ($root === '' || ! $this->fileModel->folderExists($root)) {
            return $this->unavailable('This shared folder is empty or no longer available.');
        }

        $relative = $this->cleanFolderPath((string) $this->request->getGet('path'));
        if ($relative === false) {
            return redirect()->to(base_url('share/' . $token));
        }
        $current = $relative === '' ? $root : $root . '/' . $relative;
        if (! $this->fileModel->folderExists($current)) {
            return $this->unavailable('This folder is empty or no longer available.');
        }

        $files = (new ImportantFileModel())->getActiveFilesInFolder($current, 1000);
        $children = (new ImportantFileModel())->getChildFolders($current);
        foreach ($children as &$child) {
            $absolutePath = (string) $child['path'];
            $child['relativePath'] = $absolutePath === $root ? '' : ltrim(substr($absolutePath, strlen($root)), '/');
        }
        unset($child);

        $treeFiles = (new ImportantFileModel())->getFolderTreeFiles($current, 10001);
        $summary = [
            'files' => count($treeFiles),
            'bytes' => array_sum(array_map(static fn (array $file): int => (int) ($file['file_size'] ?? 0), $treeFiles)),
        ];

        $this->shareModel->recordView((int) $share['id']);
        $this->audit('shared_folder_opened', null, [
            'share_id'    => (int) $share['id'],
            'folder_path' => $current,
        ]);

        return $this->securePage(view('shares/folder', [
            'share'        => $share,
            'token'        => $token,
            'rootPath'     => $root,
            'rootName'     => basename(str_replace('\\', '/', $root)),
            'relativePath' => $relative,
            'currentPath'  => $current,
            'currentName'  => basename(str_replace('\\', '/', $current)),
            'breadcrumbs'  => $this->folderBreadcrumbs($relative),
            'folders'      => $children,
            'files'        => $files,
            'summary'      => $summary,
        ]));
    }

    private function renderFilePage(
        string $token,
        array $share,
        array $file,
        ?string $previewUrl = null,
        ?string $downloadUrl = null,
        ?string $backUrl = null
    ) {
        return $this->securePage(view('shares/show', [
            'share'       => $share,
            'file'        => $file,
            'token'       => $token,
            'previewKind' => ImportantFileModel::previewKind($file),
            'previewUrl'  => $previewUrl ?: base_url('share/' . $token . '/preview'),
            'downloadUrl' => $downloadUrl ?: base_url('share/' . $token . '/download'),
            'backUrl'     => $backUrl,
        ]));
    }

    private function streamPreview(array $file): ResponseInterface
    {
        $kind = ImportantFileModel::previewKind($file);
        if ($kind === 'unsupported') {
            return $this->plainUnavailable('Preview is not available for this file type.');
        }

        $forceFull = $this->request->getGet('full') === '1';
        $range = $forceFull ? '' : trim($this->request->getHeaderLine('Range'));
        if ($range !== '' && ! preg_match('/^bytes=\d*-\d*$/', $range)) {
            $range = '';
        }
        if ($kind === 'text' && $range === '' && (int) $file['file_size'] > 768 * 1024) {
            $range = 'bytes=0-786431';
        }

        try {
            $bucket = config(SupabaseConfig::class)->filesBucket;
            $object = (new SupabaseStorage($bucket))->downloadObject($file['file_path'], $range !== '' ? $range : null);
        } catch (Throwable $e) {
            log_message('error', 'Shared file preview failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(502)->setHeader('Cache-Control', 'no-store, max-age=0')->setBody('The shared preview could not be loaded.');
        }

        $mimeType = ImportantFileModel::previewMimeType($file, (string) ($object['contentType'] ?? ''));
        $filename = basename(str_replace('\\', '/', (string) $file['original_filename']));
        $fallback = preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename) ?: 'preview';
        $fallback = str_replace(['"', "\r", "\n"], '', $fallback);
        $disposition = 'inline; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);

        $response = $this->response
            ->setStatusCode((int) $object['status'])
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('X-Preview-Kind', $kind)
            ->setHeader('Content-Disposition', $disposition)
            ->setHeader('Content-Length', (string) strlen($object['body']))
            ->setHeader('Accept-Ranges', $object['acceptRanges'] ?: 'bytes')
            ->setHeader('Cache-Control', 'no-store, private, no-transform, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody($object['body']);

        if ($object['contentRange'] !== '') {
            $response->setHeader('Content-Range', $object['contentRange']);
        }
        if ($kind === 'text' && (int) $file['file_size'] > 768 * 1024) {
            $response->setHeader('X-Preview-Truncated', 'true');
        }

        return $response;
    }

    private function redirectDownload(array $share, array $file, string $auditAction)
    {
        try {
            $bucket = config(SupabaseConfig::class)->filesBucket;
            $url = (new SupabaseStorage($bucket))->createSignedDownloadUrl(
                $file['file_path'],
                120,
                (string) $file['original_filename']
            );
        } catch (Throwable $e) {
            log_message('error', 'Shared file signed download failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(502)->setHeader('Cache-Control', 'no-store, max-age=0')->setBody(view('shares/unavailable', ['message' => 'The download could not be prepared. Please try again.']));
        }

        if (! $this->shareModel->claimDownload((int) $share['id'])) {
            return $this->unavailable('This share link has reached its download limit or is no longer active.');
        }

        $this->fileModel->recordDownload((int) $file['id']);
        $this->audit($auditAction, (int) $file['id'], ['share_id' => (int) $share['id']]);

        return redirect()->to($url);
    }

    private function resolveShare(string $token): ?array
    {
        return $this->shareModel->findUsableByToken($token);
    }

    private function resolveDirectFile(array $share): ?array
    {
        if (($share['share_type'] ?? 'file') !== 'file' || empty($share['file_id'])) {
            return null;
        }
        $file = $this->fileModel->find((int) $share['file_id']);

        return $file && $file['status'] === 'active' ? $file : null;
    }

    private function resolveFolderFile(string $token, int $fileId): ?array
    {
        $share = $this->resolveShare($token);
        if ($share === null || ($share['share_type'] ?? '') !== 'folder') {
            return null;
        }

        $file = $this->fileModel->find($fileId);
        if (! $file || $file['status'] !== 'active') {
            return null;
        }

        $root = trim((string) ($share['folder_path'] ?? ''), '/');
        $folder = trim((string) ($file['folder_path'] ?? ''), '/');
        if ($root === '' || ($folder !== $root && ! str_starts_with($folder, $root . '/'))) {
            return null;
        }

        return [$share, $file];
    }

    private function folderBreadcrumbs(string $relative): array
    {
        $crumbs = [['label' => 'Shared folder', 'path' => '']];
        if ($relative === '') {
            return $crumbs;
        }

        $parts = explode('/', $relative);
        $path = [];
        foreach ($parts as $part) {
            $path[] = $part;
            $crumbs[] = ['label' => $part, 'path' => implode('/', $path)];
        }

        return $crumbs;
    }

    private function cleanFolderPath(string $path): string|false
    {
        $path = trim(str_replace('\\', '/', $path), '/ ');
        if ($path === '') {
            return '';
        }
        if (strlen($path) > 1000 || str_contains($path, "\0")) {
            return false;
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.' || $part === '..' || strlen($part) > 255) {
                return false;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function cleanArchiveFolder(string $folder): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $folder)) as $segment) {
            $clean = $this->cleanArchiveSegment($segment);
            if ($clean !== '') {
                $parts[] = $clean;
            }
        }

        return implode('/', $parts);
    }

    private function cleanArchiveSegment(string $value): string
    {
        $value = preg_replace('/[<>:"\\|?*\x00-\x1F\x7F]/u', '_', trim($value)) ?? '';
        $value = trim($value, ". \t\n\r\0\x0B");

        return mb_substr($value !== '' ? $value : 'file', 0, 180);
    }

    private function uniqueArchivePath(string $folder, string $filename, array &$used): string
    {
        $directory = $folder !== '' ? trim($folder, '/') . '/' : '';
        $candidate = $directory . $filename;
        $key = mb_strtolower($candidate);
        if (! isset($used[$key])) {
            $used[$key] = true;
            return $candidate;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $stem = $extension !== '' ? substr($filename, 0, -strlen($extension) - 1) : $filename;
        for ($i = 2; $i < 10000; $i++) {
            $next = $directory . $stem . ' (' . $i . ')' . ($extension !== '' ? '.' . $extension : '');
            $key = mb_strtolower($next);
            if (! isset($used[$key])) {
                $used[$key] = true;
                return $next;
            }
        }

        return $directory . bin2hex(random_bytes(5)) . '-' . $filename;
    }

    private function securePage(string $body): ResponseInterface
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store, private, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setBody($body);
    }

    private function plainUnavailable(string $message = 'This shared file is unavailable.'): ResponseInterface
    {
        return $this->response
            ->setStatusCode(404)
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setBody($message);
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
