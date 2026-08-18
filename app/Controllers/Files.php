<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\FileAuditModel;
use App\Models\ImportantFileModel;
use App\Models\ImportantFileShareModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Supabase as SupabaseConfig;
use Throwable;

class Files extends BaseController
{
    protected $helpers = ['url', 'form'];

    protected ImportantFileModel $fileModel;
    protected FileAuditModel $auditModel;

    public function __construct()
    {
        $this->fileModel  = new ImportantFileModel();
        $this->auditModel = new FileAuditModel();
    }

    private function isUnlocked(): bool
    {
        return (bool) session()->get('files_unlocked');
    }

    private function filesBucket(): string
    {
        return config(SupabaseConfig::class)->filesBucket;
    }

    private function maxUploadBytes(): int
    {
        $megabytes = (int) (getenv('FILES_MAX_UPLOAD_MB') ?: 50);

        return max(1, min($megabytes, 500)) * 1024 * 1024;
    }

    private function maxFolderDownloadBytes(): int
    {
        $megabytes = (int) (getenv('FILES_FOLDER_DOWNLOAD_MAX_MB') ?: 2048);

        // Standard ZIP32 archives are limited to 4 GiB. Keep a safety margin
        // for headers and for browsers that cannot stream directly to disk.
        return max(1, min($megabytes, 3800)) * 1024 * 1024;
    }

    private function maxFolderDownloadFiles(): int
    {
        $files = (int) (getenv('FILES_FOLDER_DOWNLOAD_MAX_FILES') ?: 2000);

        return max(1, min($files, 10000));
    }

    private function requireUnlockedJson(): ?ResponseInterface
    {
        if ($this->isUnlocked()) {
            return null;
        }

        return $this->response->setStatusCode(401)->setJSON(['error' => 'This vault is locked. Refresh and unlock it again.']);
    }

    private function shareTokenKey(): string
    {
        $candidates = [
            getenv('SHARE_TOKEN_ENCRYPTION_KEY') ?: '',
            function_exists('env') ? (string) env('encryption.key', '') : '',
            getenv('SITE_LOGIN_PASSWORD_HASH') ?: '',
            getenv('SITE_LOGIN_PASSWORD') ?: '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return hash('sha256', $candidate, true);
            }
        }

        throw new \RuntimeException('Set SHARE_TOKEN_ENCRYPTION_KEY in Vercel before creating recoverable share links.');
    }

    private function encryptShareToken(string $rawToken): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $rawToken,
            'aes-256-gcm',
            $this->shareTokenKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'important-file-share-v1',
            16
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new \RuntimeException('The share token could not be encrypted.');
        }

        return rtrim(strtr(base64_encode("\x01" . $iv . $tag . $ciphertext), '+/', '-_'), '=');
    }

    private function decryptShareToken(string $encoded): string
    {
        $encoded = trim($encoded);
        if ($encoded === '') {
            throw new \RuntimeException('This link was created before repeat-copy support was added.');
        }

        $padding = strlen($encoded) % 4;
        if ($padding !== 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $payload = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($payload === false || strlen($payload) < 30 || ord($payload[0]) !== 1) {
            throw new \RuntimeException('The saved share token is invalid.');
        }

        $iv         = substr($payload, 1, 12);
        $tag        = substr($payload, 13, 16);
        $ciphertext = substr($payload, 29);
        $rawToken   = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->shareTokenKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'important-file-share-v1'
        );

        if (! is_string($rawToken) || ! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            throw new \RuntimeException('The saved share token could not be decrypted.');
        }

        return $rawToken;
    }

    private function audit(string $action, ?int $fileId = null, array $details = []): void
    {
        try {
            $this->auditModel->logAction($action, $fileId, $details);
        } catch (Throwable $e) {
            log_message('error', 'Important Files audit failed: {message}', ['message' => $e->getMessage()]);
        }
    }

    public function gate()
    {
        if ($this->isUnlocked()) {
            return redirect()->to('/files');
        }

        return view('files/gate');
    }

    public function unlock(): RedirectResponse
    {
        $expected = getenv('FILES_ACCESS_PASSWORD');
        if ($expected === false || $expected === '') {
            return redirect()->to('/files/gate')->with('error', "This section isn't configured yet.");
        }

        $given = (string) $this->request->getPost('password');
        if (! hash_equals($expected, $given)) {
            $this->audit('unlock_failed');
            return redirect()->to('/files/gate')->with('error', 'Incorrect password.');
        }

        session()->regenerate(true);
        session()->set('files_unlocked', true);
        $this->audit('vault_unlocked');

        return redirect()->to('/files');
    }

    public function lock(): RedirectResponse
    {
        $this->audit('vault_locked');
        session()->remove(['files_unlocked', 'files_pending_uploads', 'files_pending_folder_downloads']);

        return redirect()->to('/files/gate')->with('success', 'Locked.');
    }

    public function index()
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $pathValue = $this->cleanFolderPath((string) $this->request->getGet('path'));
        if ($pathValue === false) {
            return redirect()->to('/files')->with('error', 'That folder path is invalid.');
        }
        $currentPath = $pathValue ?: null;

        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'category' => trim((string) $this->request->getGet('category')),
            'type'     => strtolower(trim((string) $this->request->getGet('type'))),
            'favorite' => trim((string) $this->request->getGet('favorite')),
            'sort'     => trim((string) ($this->request->getGet('sort') ?: 'name_asc')),
        ];

        $queryModel = new ImportantFileModel();
        $files      = $queryModel->getFolderFiles($currentPath, $filters, 20);
        $page       = $queryModel->pager->getDetails('files');

        $hasFileFilters = $filters['q'] !== ''
            || $filters['category'] !== ''
            || $filters['type'] !== '';

        return view('files/index', [
            'files'          => $files,
            'pager'          => $queryModel->pager,
            'page'           => $page,
            'filters'        => $filters,
            'hasFileFilters' => $hasFileFilters,
            'currentPath'    => $currentPath,
            'breadcrumbs'    => $this->buildBreadcrumbs($currentPath),
            // Hide unrelated folder cards while file filters are active. The
            // previous layout could look non-empty even when no files matched.
            'childFolders'   => ($filters['favorite'] === '1' || $hasFileFilters)
                ? []
                : (new ImportantFileModel())->getChildFolders($currentPath),
            'categories'     => (new ImportantFileModel())->getCategories(),
            'extensions'     => (new ImportantFileModel())->getExtensions(),
            'summary'        => (new ImportantFileModel())->getVaultSummary(),
            'maxBytes'       => $this->maxUploadBytes(),
            'maxMb'          => (int) ($this->maxUploadBytes() / 1024 / 1024),
        ]);
    }

    public function recycle()
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $model = new ImportantFileModel();

        return view('files/recycle', [
            'files' => $model->getDeletedFiles(10),
            'pager' => $model->pager,
        ]);
    }

    public function activity()
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $model = new FileAuditModel();

        return view('files/activity', [
            'events' => $model->recent(20),
            'pager'  => $model->pager,
        ]);
    }

    public function signUpload(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $payload = $this->request->getJSON(true) ?: [];
        $clean   = $this->validateUploadRequest($payload);
        if (isset($clean['error'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => $clean['error']]);
        }

        $extension = $clean['extension'];
        $basename  = bin2hex(random_bytes(20)) . ($extension !== '' ? '.' . $extension : '');
        $filePath  = date('Y/m') . '/' . $basename;
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $fileId = $this->fileModel->insert([
            'title'             => $clean['title'],
            'description'       => $clean['description'],
            'category'          => $clean['category'],
            'folder_path'       => $clean['folderPath'],
            'stored_filename'   => $basename,
            'original_filename' => $clean['originalName'],
            'file_path'         => $filePath,
            'file_extension'    => $extension,
            'mime_type'         => $clean['mimeType'],
            'file_size'         => $clean['fileSize'],
            'checksum_sha256'   => $clean['checksum'],
            'upload_token_hash' => $tokenHash,
            'status'            => 'pending',
            'document_date'     => $clean['documentDate'],
            'is_favorite'       => false,
        ], true);

        if (! $fileId) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => implode(' ', $this->fileModel->errors()) ?: 'Could not create the pending upload.',
            ]);
        }

        try {
            $signed = (new SupabaseStorage($this->filesBucket()))->createSignedUploadUrl($filePath);
        } catch (Throwable $e) {
            $this->fileModel->delete((int) $fileId, true);
            log_message('error', 'Important file signed upload failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Could not prepare the upload. Please try again.']);
        }

        $pending            = (array) session()->get('files_pending_uploads');
        $pending[$rawToken] = ['id' => (int) $fileId, 'created' => time()];
        session()->set('files_pending_uploads', $pending);

        $this->audit('upload_prepared', (int) $fileId, [
            'name' => $clean['originalName'],
            'size' => $clean['fileSize'],
        ]);

        return $this->response->setJSON([
            'uploadUrl'  => $signed['uploadUrl'],
            'uploadToken' => $rawToken,
            'fileId'      => (int) $fileId,
        ]);
    }

    public function store(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $payload = $this->request->getJSON(true) ?: [];
        $token   = trim((string) ($payload['uploadToken'] ?? ''));
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid upload token.']);
        }

        $pending = (array) session()->get('files_pending_uploads');
        if (! isset($pending[$token]['id'])) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'This upload session has expired. Please upload the file again.']);
        }

        $file = $this->fileModel->where('upload_token_hash', hash('sha256', $token))->first();
        if (! $file || $file['status'] !== 'pending' || (int) $file['id'] !== (int) $pending[$token]['id']) {
            unset($pending[$token]);
            session()->set('files_pending_uploads', $pending);
            return $this->response->setStatusCode(409)->setJSON(['error' => 'The pending upload could not be verified.']);
        }

        try {
            $storage = new SupabaseStorage($this->filesBucket());
            $info    = $storage->getObjectInfo($file['file_path']);

            if (! $info['exists']) {
                throw new \RuntimeException('The uploaded object was not found in storage.');
            }
            if ((int) $info['size'] !== (int) $file['file_size']) {
                throw new \RuntimeException('The uploaded file size does not match the signed request.');
            }
            $updated = $this->fileModel->update((int) $file['id'], [
                'status'            => 'active',
                'mime_type'         => $info['contentType'] !== '' ? $info['contentType'] : $file['mime_type'],
                'upload_token_hash' => null,
                'finalized_at'      => date('Y-m-d H:i:s'),
            ]);

            if (! $updated) {
                throw new \RuntimeException('The database could not finalize the upload.');
            }
        } catch (Throwable $e) {
            try {
                (new SupabaseStorage($this->filesBucket()))->delete($file['file_path']);
            } catch (Throwable) {
                // The scheduled cleanup will retry later.
            }
            $this->fileModel->update((int) $file['id'], ['status' => 'failed']);
            unset($pending[$token]);
            session()->set('files_pending_uploads', $pending);
            $this->audit('upload_failed', (int) $file['id'], ['reason' => $e->getMessage()]);

            return $this->response->setStatusCode(422)->setJSON(['error' => $e->getMessage()]);
        }

        unset($pending[$token]);
        session()->set('files_pending_uploads', $pending);
        $file = $this->fileModel->find((int) $file['id']);
        $this->audit('file_uploaded', (int) $file['id'], ['name' => $file['original_filename']]);

        return $this->response->setJSON([
            'success'  => true,
            'message'  => 'File added to the vault.',
            'cardHtml' => view('files/_file_card', ['f' => $file]),
            'fileSize' => (int) $file['file_size'],
        ]);
    }

    public function cancelUpload(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $token   = trim((string) (($this->request->getJSON(true)['uploadToken'] ?? '')));
        $pending = (array) session()->get('files_pending_uploads');
        if (! isset($pending[$token]['id'])) {
            return $this->response->setJSON(['success' => true]);
        }

        $file = $this->fileModel->find((int) $pending[$token]['id']);
        if ($file && $file['status'] === 'pending') {
            try {
                (new SupabaseStorage($this->filesBucket()))->delete($file['file_path']);
            } catch (Throwable) {
            }
            $this->audit('upload_cancelled', (int) $file['id']);
            $this->fileModel->delete((int) $file['id'], true);
        }

        unset($pending[$token]);
        session()->set('files_pending_uploads', $pending);

        return $this->response->setJSON(['success' => true]);
    }

    public function folderDownloadManifest(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $payload   = $this->request->getJSON(true) ?: [];
        $pathValue = $this->cleanFolderPath((string) ($payload['path'] ?? ''));
        if ($pathValue === false) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'That folder path is invalid.']);
        }
        $rootPath = $pathValue ?: null;

        $maxFiles = $this->maxFolderDownloadFiles();
        $files    = (new ImportantFileModel())->getFolderTreeFiles($rootPath, $maxFiles + 1);
        if (count($files) > $maxFiles) {
            return $this->response->setStatusCode(413)->setJSON([
                'error' => 'This folder contains more than ' . number_format($maxFiles) . ' files. Download a smaller subfolder instead.',
            ]);
        }
        if ($files === []) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'This folder does not contain any downloadable files.']);
        }

        $requestedBytes = array_sum(array_map(static fn (array $file): int => (int) ($file['file_size'] ?? 0), $files));
        if ($requestedBytes > $this->maxFolderDownloadBytes()) {
            return $this->response->setStatusCode(413)->setJSON([
                'error' => 'This folder is larger than the configured folder-download limit of '
                    . ImportantFileModel::formatBytes($this->maxFolderDownloadBytes())
                    . '. Download smaller subfolders instead.',
            ]);
        }

        $storage      = new SupabaseStorage($this->filesBucket());
        $signedByPath = [];

        try {
            foreach (array_chunk($files, 100) as $chunk) {
                $paths   = array_values(array_map(static fn (array $file): string => (string) $file['file_path'], $chunk));
                $results = $storage->createSignedDownloadUrls($paths, 7200);

                foreach ($results as $result) {
                    if (($result['signedUrl'] ?? null) !== null && ($result['path'] ?? '') !== '') {
                        $signedByPath[(string) $result['path']] = (string) $result['signedUrl'];
                    }
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Folder signed URL preparation failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'The folder download could not be prepared. Please try again.',
            ]);
        }

        $entries  = [];
        $used     = [];
        $fileIds  = [];
        $total    = 0;
        $skipped  = 0;

        foreach ($files as $file) {
            $signedUrl = $signedByPath[(string) $file['file_path']] ?? null;
            if (! $signedUrl) {
                $skipped++;
                continue;
            }

            $relativeFolder = $this->archiveRelativeFolder(
                (string) ($file['folder_path'] ?? ''),
                $rootPath
            );
            $archiveFolder = $this->cleanArchiveFolder($relativeFolder);
            $filename      = $this->cleanArchiveSegment((string) ($file['original_filename'] ?? 'file'));
            $archivePath   = $this->uniqueArchivePath($archiveFolder, $filename, $used);
            $size          = max(0, (int) ($file['file_size'] ?? 0));

            $entries[] = [
                'id'           => (int) $file['id'],
                'name'         => $archivePath,
                'size'         => $size,
                'url'          => $signedUrl,
                'lastModified' => (string) ($file['updated_at'] ?: $file['created_at'] ?: ''),
            ];
            $fileIds[] = (int) $file['id'];
            $total    += $size;
        }

        if ($entries === []) {
            return $this->response->setStatusCode(409)->setJSON([
                'error' => 'The files are listed in the database, but none are currently available in storage.',
            ]);
        }

        $downloadToken = bin2hex(random_bytes(32));
        $pending        = (array) session()->get('files_pending_folder_downloads');
        $now            = time();
        foreach ($pending as $token => $download) {
            if ((int) ($download['expires'] ?? 0) < $now) {
                unset($pending[$token]);
            }
        }
        $pending[$downloadToken] = [
            'ids'        => $fileIds,
            'path'       => $rootPath,
            'file_count' => count($entries),
            'bytes'      => $total,
            'expires'    => $now + 7200,
        ];
        session()->set('files_pending_folder_downloads', $pending);

        $this->audit('folder_download_prepared', null, [
            'path'       => $rootPath ?: 'My Drive',
            'file_count' => count($entries),
            'bytes'      => $total,
            'skipped'    => $skipped,
        ]);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setJSON([
                'archiveName'  => $this->folderArchiveName($rootPath),
                'files'        => $entries,
                'fileCount'    => count($entries),
                'totalBytes'   => $total,
                'skippedCount' => $skipped,
                'downloadToken'=> $downloadToken,
            ]);
    }

    public function folderDownloadComplete(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $payload = $this->request->getJSON(true) ?: [];
        $token   = trim((string) ($payload['downloadToken'] ?? ''));
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid folder download token.']);
        }

        $pending = (array) session()->get('files_pending_folder_downloads');
        $download = $pending[$token] ?? null;
        unset($pending[$token]);
        session()->set('files_pending_folder_downloads', $pending);

        if (! is_array($download) || (int) ($download['expires'] ?? 0) < time()) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'This folder download session has expired.']);
        }

        $this->fileModel->recordBulkDownload((array) ($download['ids'] ?? []));
        $this->audit('folder_downloaded', null, [
            'path'       => $download['path'] ?: 'My Drive',
            'file_count' => (int) ($download['file_count'] ?? 0),
            'bytes'      => (int) ($download['bytes'] ?? 0),
        ]);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON(['success' => true]);
    }

    public function preview(int $id): ResponseInterface
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        $kind = $file ? ImportantFileModel::previewKind($file) : 'unsupported';
        if (! $file || $file['status'] !== 'active' || $kind === 'unsupported') {
            return $this->response
                ->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setBody('Preview is not available for that file.');
        }

        $range = trim($this->request->getHeaderLine('Range'));
        if ($range !== '' && ! preg_match('/^bytes=\d*-\d*$/', $range)) {
            $range = '';
        }

        // Text and source files are shown as plain text. Limit very large text
        // previews so opening a log or database export does not exhaust a
        // serverless function's memory.
        if ($kind === 'text' && $range === '' && (int) $file['file_size'] > 2 * 1024 * 1024) {
            $range = 'bytes=0-2097151';
        }

        try {
            $object = (new SupabaseStorage($this->filesBucket()))
                ->downloadObject($file['file_path'], $range !== '' ? $range : null);
        } catch (Throwable $e) {
            log_message('error', 'Important file preview failed: {message}', ['message' => $e->getMessage()]);

            return $this->response
                ->setStatusCode(502)
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setBody('The preview could not be loaded. Please close this window and try again.');
        }

        if ($range === '' || $range === 'bytes=0-2097151') {
            $this->audit('file_previewed', $id);
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
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setBody($object['body']);

        if ($object['contentRange'] !== '') {
            $response->setHeader('Content-Range', $object['contentRange']);
        }
        if ($kind === 'text' && (int) $file['file_size'] > 2 * 1024 * 1024) {
            $response->setHeader('X-Preview-Truncated', 'true');
        }

        return $response;
    }

    public function download(int $id)
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return redirect()->to('/files')->with('error', 'File not found.');
        }

        try {
            $url = (new SupabaseStorage($this->filesBucket()))->createSignedDownloadUrl($file['file_path'], 120);
        } catch (Throwable $e) {
            return redirect()->to('/files')->with('error', 'Could not generate a download link.');
        }

        $this->fileModel->recordDownload($id);
        $this->audit('file_downloaded', $id);

        return redirect()->to($url);
    }

    public function shares(int $id): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'File not found.']);
        }

        $rows = (new ImportantFileShareModel())->recentForFile($id);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON(['shares' => $this->formatShareRows($rows)]);
    }

    public function createShare(int $id): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'File not found.']);
        }

        $payload = $this->request->getJSON(true) ?: [];
        $duration = strtolower(trim((string) ($payload['duration'] ?? '7d')));
        $durationMap = [
            '1d'    => '+1 day',
            '7d'    => '+7 days',
            '30d'   => '+30 days',
            '90d'   => '+90 days',
            'never' => null,
        ];
        if (! array_key_exists($duration, $durationMap)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Choose a valid link expiration.']);
        }

        $maxDownloads = (int) ($payload['maxDownloads'] ?? 0);
        if ($maxDownloads < 0 || $maxDownloads > 10000) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Download limit must be between 1 and 10,000, or 0 for unlimited.']);
        }

        $rawToken = bin2hex(random_bytes(32));
        $expiresAt = $durationMap[$duration] === null
            ? null
            : date('Y-m-d H:i:s', strtotime($durationMap[$duration]));

        try {
            $tokenCiphertext = $this->encryptShareToken($rawToken);
        } catch (Throwable $e) {
            log_message('error', 'Share-token encryption failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Share links are not configured for repeat copying. Add SHARE_TOKEN_ENCRYPTION_KEY in Vercel and redeploy.',
            ]);
        }

        $shareModel = new ImportantFileShareModel();
        $shareId = $shareModel->insert([
            'share_type'     => 'file',
            'file_id'        => $id,
            'folder_path'    => null,
            'token_hash'       => hash('sha256', $rawToken),
            'token_ciphertext' => $tokenCiphertext,
            'expires_at'     => $expiresAt,
            'max_downloads'  => $maxDownloads > 0 ? $maxDownloads : null,
            'view_count'     => 0,
            'download_count' => 0,
            'created_by'     => mb_substr((string) session()->get('site_username'), 0, 100),
        ], true);

        if (! $shareId) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => implode(' ', $shareModel->errors()) ?: 'The share link could not be created.',
            ]);
        }

        $this->audit('share_link_created', $id, [
            'share_id'      => (int) $shareId,
            'expires_at'    => $expiresAt,
            'max_downloads' => $maxDownloads > 0 ? $maxDownloads : null,
        ]);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON([
                'success'   => true,
                'shareId'   => (int) $shareId,
                'shareUrl'  => base_url('share/' . $rawToken),
                'expiresAt' => $expiresAt,
                'message'   => 'Share link created. You can copy it again later from Link History.',
            ]);
    }

    public function folderShares(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $path = $this->cleanFolderPath((string) $this->request->getGet('path'));
        if (! is_string($path) || $path === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Choose a valid folder to share.']);
        }
        if (! (new ImportantFileModel())->folderExists($path)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Folder not found or empty.']);
        }

        $rows = (new ImportantFileShareModel())->recentForFolder($path);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON(['shares' => $this->formatShareRows($rows)]);
    }

    public function createFolderShare(): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $payload = $this->request->getJSON(true) ?: [];
        $path = $this->cleanFolderPath((string) ($payload['path'] ?? ''));
        if (! is_string($path) || $path === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Choose a valid folder to share.']);
        }
        if (! (new ImportantFileModel())->folderExists($path)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Folder not found or empty.']);
        }

        $settings = $this->validateShareSettings($payload);
        if (isset($settings['error'])) {
            return $this->response->setStatusCode(422)->setJSON(['error' => $settings['error']]);
        }

        $rawToken = bin2hex(random_bytes(32));
        try {
            $tokenCiphertext = $this->encryptShareToken($rawToken);
        } catch (Throwable $e) {
            log_message('error', 'Folder share-token encryption failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Share links are not configured for repeat copying. Add SHARE_TOKEN_ENCRYPTION_KEY in Vercel and redeploy.',
            ]);
        }

        $shareModel = new ImportantFileShareModel();
        $shareId = $shareModel->insert([
            'share_type'     => 'folder',
            'file_id'        => null,
            'folder_path'    => $path,
            'token_hash'       => hash('sha256', $rawToken),
            'token_ciphertext' => $tokenCiphertext,
            'expires_at'     => $settings['expiresAt'],
            'max_downloads'  => $settings['maxDownloads'],
            'view_count'     => 0,
            'download_count' => 0,
            'created_by'     => mb_substr((string) session()->get('site_username'), 0, 100),
        ], true);

        if (! $shareId) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => implode(' ', $shareModel->errors()) ?: 'The folder share link could not be created.',
            ]);
        }

        $this->audit('folder_share_link_created', null, [
            'share_id'      => (int) $shareId,
            'folder_path'   => $path,
            'expires_at'    => $settings['expiresAt'],
            'max_downloads' => $settings['maxDownloads'],
        ]);

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON([
                'success'   => true,
                'shareId'   => (int) $shareId,
                'shareUrl'  => base_url('share/' . $rawToken),
                'expiresAt' => $settings['expiresAt'],
                'message'   => 'Folder share link created. You can copy it again later from Link History.',
            ]);
    }

    public function shareLink(int $shareId): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $shareModel = new ImportantFileShareModel();
        $share = $shareModel->find($shareId);
        if (! $share) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Share link not found.']);
        }

        $status = ImportantFileShareModel::status($share);
        if ($status['key'] !== 'active') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Only active share links can be copied.']);
        }

        if (empty($share['token_ciphertext'])) {
            return $this->response->setStatusCode(409)->setJSON([
                'error'     => 'This older link cannot be recovered. Replace it once to enable repeat copying.',
                'canRotate' => true,
            ]);
        }

        try {
            $rawToken = $this->decryptShareToken((string) $share['token_ciphertext']);
            if (! hash_equals((string) $share['token_hash'], hash('sha256', $rawToken))) {
                throw new \RuntimeException('The saved share token does not match this link.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Share-token recovery failed for share {id}: {message}', [
                'id' => $shareId,
                'message' => $e->getMessage(),
            ]);
            return $this->response->setStatusCode(409)->setJSON([
                'error'     => 'This link cannot be recovered with the current encryption key. Replace it to create a new copyable link.',
                'canRotate' => true,
            ]);
        }

        $this->audit(
            ($share['share_type'] ?? 'file') === 'folder' ? 'folder_share_link_copied' : 'share_link_copied',
            ($share['share_type'] ?? 'file') === 'file' ? (int) ($share['file_id'] ?? 0) : null,
            ['share_id' => $shareId, 'folder_path' => $share['folder_path'] ?? null]
        );

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON([
                'success'  => true,
                'shareUrl' => base_url('share/' . $rawToken),
            ]);
    }

    public function rotateShareToken(int $shareId): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $shareModel = new ImportantFileShareModel();
        $share = $shareModel->find($shareId);
        if (! $share) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Share link not found.']);
        }

        $status = ImportantFileShareModel::status($share);
        if ($status['key'] !== 'active') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Only active share links can be replaced.']);
        }

        $rawToken = bin2hex(random_bytes(32));
        try {
            $tokenCiphertext = $this->encryptShareToken($rawToken);
        } catch (Throwable $e) {
            log_message('error', 'Share-token rotation failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'Add SHARE_TOKEN_ENCRYPTION_KEY in Vercel and redeploy before replacing this link.',
            ]);
        }

        $updated = $shareModel->update($shareId, [
            'token_hash'       => hash('sha256', $rawToken),
            'token_ciphertext' => $tokenCiphertext,
        ]);
        if (! $updated) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'The share link could not be replaced.']);
        }

        $this->audit(
            ($share['share_type'] ?? 'file') === 'folder' ? 'folder_share_link_rotated' : 'share_link_rotated',
            ($share['share_type'] ?? 'file') === 'file' ? (int) ($share['file_id'] ?? 0) : null,
            ['share_id' => $shareId, 'folder_path' => $share['folder_path'] ?? null]
        );

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON([
                'success'  => true,
                'shareUrl' => base_url('share/' . $rawToken),
                'message'  => 'The old link was replaced. The new link can be copied again later.',
            ]);
    }

    public function revokeShare(int $shareId): ResponseInterface
    {
        if ($response = $this->requireUnlockedJson()) {
            return $response;
        }

        $shareModel = new ImportantFileShareModel();
        $share = $shareModel->find($shareId);
        if (! $share) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Share link not found.']);
        }

        $shareType = (string) ($share['share_type'] ?? 'file');
        if ($shareType === 'file') {
            $file = $this->fileModel->find((int) ($share['file_id'] ?? 0));
            if (! $file) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'File not found.']);
            }
        } elseif ($shareType !== 'folder' || trim((string) ($share['folder_path'] ?? '')) === '') {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Share target not found.']);
        }

        if (! empty($share['revoked_at'])) {
            return $this->response->setJSON(['success' => true, 'message' => 'This link is already disabled.']);
        }

        if (! $shareModel->revoke($shareId)) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'The share link could not be disabled.']);
        }

        $this->audit(
            $shareType === 'folder' ? 'folder_share_link_revoked' : 'share_link_revoked',
            $shareType === 'file' ? (int) $share['file_id'] : null,
            ['share_id' => $shareId, 'folder_path' => $share['folder_path'] ?? null]
        );

        return $this->response
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setJSON(['success' => true, 'message' => 'Share link disabled.']);
    }

    public function update(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $returnTo = $this->vaultReturnPath();
        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return redirect()->to($returnTo)->with('error', 'File not found.');
        }

        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $category    = trim((string) $this->request->getPost('category'));
        $folderRaw   = (string) $this->request->getPost('folder_path');
        $folderPath  = $this->cleanFolderPath($folderRaw);
        $document    = $this->cleanDate((string) $this->request->getPost('document_date'));

        if ($title === '' || mb_strlen($title) > 255 || mb_strlen($description) > 5000 || mb_strlen($category) > 100) {
            return redirect()->to($returnTo)->with('error', 'Please check the title, description, and category lengths.');
        }
        if ($folderPath === false) {
            return redirect()->to($returnTo)->with('error', 'The folder path is invalid or too long.');
        }

        $updated = $this->fileModel->update($id, [
            'title'                  => $title,
            'description'            => $description !== '' ? $description : null,
            'category'               => $category !== '' ? $category : null,
            'folder_path'            => $folderPath,
            'document_date'          => $document,
        ]);

        if (! $updated) {
            return redirect()->to($returnTo)->with('error', implode(' ', $this->fileModel->errors()) ?: 'File details could not be updated.');
        }

        $this->audit('metadata_updated', $id);

        return redirect()->to($returnTo)->with('success', 'File details updated.');
    }

    public function toggleFavorite(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return redirect()->to('/files')->with('error', 'File not found.');
        }

        $favorite = ! (bool) $file['is_favorite'];
        $this->fileModel->update($id, ['is_favorite' => $favorite]);
        $this->audit($favorite ? 'favorite_added' : 'favorite_removed', $id);

        return redirect()->back()->with('success', $favorite ? 'Added to favorites.' : 'Removed from favorites.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $returnTo = $this->vaultReturnPath();
        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return redirect()->to($returnTo)->with('error', 'File not found.');
        }

        if (! $this->fileModel->markDeleted($id, 30)) {
            return redirect()->to($returnTo)->with('error', 'Could not move the file to the Recycle Bin.');
        }

        (new ImportantFileShareModel())->revokeForFile($id);
        $this->audit('file_deleted', $id, ['purge_after_days' => 30, 'share_links_revoked' => true]);

        return redirect()->to($returnTo)->with('success', 'File moved to the Recycle Bin for 30 days.');
    }

    public function restore(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'deleted') {
            return redirect()->to('/files/recycle')->with('error', 'Deleted file not found.');
        }

        $this->fileModel->restoreFile($id);
        $this->audit('file_restored', $id);

        return redirect()->to('/files/recycle')->with('success', 'File restored.');
    }

    public function purge(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'deleted') {
            return redirect()->to('/files/recycle')->with('error', 'Deleted file not found.');
        }

        try {
            $deleted = (new SupabaseStorage($this->filesBucket()))->delete($file['file_path']);
        } catch (Throwable) {
            $deleted = false;
        }

        if (! $deleted) {
            return redirect()->to('/files/recycle')->with('error', 'Storage deletion failed. The record was kept so it can be retried safely.');
        }

        $this->audit('file_permanently_deleted', $id, ['title' => $file['title']]);
        $this->fileModel->delete($id, true);

        return redirect()->to('/files/recycle')->with('success', 'File permanently deleted.');
    }

    private function folderArchiveName(?string $rootPath): string
    {
        $name = $rootPath ? basename(str_replace('\\', '/', $rootPath)) : 'Important Files';
        $name = $this->cleanArchiveSegment($name);

        return str_ends_with(strtolower($name), '.zip') ? $name : $name . '.zip';
    }

    private function archiveRelativeFolder(string $fileFolder, ?string $rootPath): string
    {
        $fileFolder = trim(str_replace('\\', '/', $fileFolder), '/');
        if ($rootPath === null || $rootPath === '') {
            return $fileFolder;
        }

        $rootPath = trim(str_replace('\\', '/', $rootPath), '/');
        if ($fileFolder === $rootPath) {
            return '';
        }

        $prefix = $rootPath . '/';

        return str_starts_with($fileFolder, $prefix) ? substr($fileFolder, strlen($prefix)) : '';
    }

    private function cleanArchiveFolder(string $folder): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $folder)) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = $this->cleanArchiveSegment($segment);
        }

        return implode('/', $segments);
    }

    private function cleanArchiveSegment(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F<>:"\\\\|?*]/u', '_', $value) ?? '';
        $value = trim($value, " .\t\n\r\0\x0B");
        if ($value === '') {
            $value = 'unnamed';
        }
        if (preg_match('/^(con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\.|$)/i', $value)) {
            $value = '_' . $value;
        }

        return mb_substr($value, 0, 180);
    }

    /**
     * Keep duplicate original filenames usable by adding " (2)", " (3)",
     * and so on inside the generated ZIP archive.
     */
    private function uniqueArchivePath(string $folder, string $filename, array &$used): string
    {
        $directory = $folder !== '' ? trim($folder, '/') . '/' : '';
        $candidate = $directory . $filename;
        $key       = mb_strtolower($candidate);
        if (! isset($used[$key])) {
            $used[$key] = true;

            return $candidate;
        }

        $dot       = mb_strrpos($filename, '.');
        $hasExt    = $dot !== false && $dot > 0;
        $stem      = $hasExt ? mb_substr($filename, 0, $dot) : $filename;
        $extension = $hasExt ? mb_substr($filename, $dot) : '';
        $counter   = 2;

        do {
            $candidate = $directory . $stem . ' (' . $counter . ')' . $extension;
            $key       = mb_strtolower($candidate);
            $counter++;
        } while (isset($used[$key]));

        $used[$key] = true;

        return $candidate;
    }

    private function validateUploadRequest(array $payload): array
    {
        $original = basename(str_replace('\\', '/', trim((string) ($payload['filename'] ?? ''))));
        $original = preg_replace('/[\x00-\x1F\x7F]/u', '', $original) ?: '';
        $rawExtension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        // Keep a short, safe suffix only for the randomized storage key. The
        // complete original filename is always retained in the database.
        $extension = preg_match('/^[a-z0-9][a-z0-9._+-]{0,19}$/', $rawExtension) ? $rawExtension : '';
        $title       = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $category    = trim((string) ($payload['category'] ?? ''));
        $folderPath  = $this->cleanFolderPath((string) ($payload['folderPath'] ?? ''));
        $mimeType    = strtolower(trim(explode(';', (string) ($payload['mimetype'] ?? ''))[0]));
        $fileSize    = (int) ($payload['filesize'] ?? 0);
        $checksum    = strtolower(trim((string) ($payload['checksum'] ?? '')));

        if ($original === '' || mb_strlen($original) > 255) {
            return ['error' => 'The original filename is missing or too long.'];
        }
        if ($fileSize <= 0 || $fileSize > $this->maxUploadBytes()) {
            return ['error' => 'Each file must be larger than 0 bytes and no more than ' . ($this->maxUploadBytes() / 1024 / 1024) . ' MB.'];
        }
        if ($title === '' || mb_strlen($title) > 255) {
            return ['error' => 'A title of up to 255 characters is required.'];
        }
        if (mb_strlen($description) > 5000 || mb_strlen($category) > 100) {
            return ['error' => 'The description or category is too long.'];
        }
        if ($folderPath === false) {
            return ['error' => 'The folder path is invalid or too long.'];
        }
        if ($checksum !== '' && ! preg_match('/^[a-f0-9]{64}$/', $checksum)) {
            return ['error' => 'The file checksum is invalid.'];
        }

        $mimeType = preg_replace('/[^a-z0-9!#$&^_.+\-\/]/', '', $mimeType) ?: 'application/octet-stream';
        $mimeType = mb_substr($mimeType, 0, 150);

        return [
            'title'        => $title,
            'description'  => $description !== '' ? $description : null,
            'category'     => $category !== '' ? $category : null,
            'folderPath'   => $folderPath,
            'originalName' => $original,
            'extension'    => $extension,
            'mimeType'     => $mimeType,
            'fileSize'     => $fileSize,
            'checksum'     => $checksum !== '' ? $checksum : null,
            'documentDate' => $this->cleanDate((string) ($payload['documentDate'] ?? '')),
        ];
    }

    private function formatShareRows(array $rows): array
    {
        return array_map(static function (array $share): array {
            $status = ImportantFileShareModel::status($share);

            return [
                'id'             => (int) $share['id'],
                'status'         => $status['key'],
                'statusLabel'    => $status['label'],
                'createdAt'      => (string) $share['created_at'],
                'expiresAt'      => $share['expires_at'] ?: null,
                'maxDownloads'   => $share['max_downloads'] !== null ? (int) $share['max_downloads'] : null,
                'downloadCount'  => (int) ($share['download_count'] ?? 0),
                'viewCount'      => (int) ($share['view_count'] ?? 0),
                'revokedAt'      => $share['revoked_at'] ?: null,
                'canCopy'        => $status['key'] === 'active' && ! empty($share['token_ciphertext']),
                'canRotate'      => $status['key'] === 'active' && empty($share['token_ciphertext']),
            ];
        }, $rows);
    }

    private function validateShareSettings(array $payload): array
    {
        $duration = strtolower(trim((string) ($payload['duration'] ?? '7d')));
        $durationMap = [
            '1d'    => '+1 day',
            '7d'    => '+7 days',
            '30d'   => '+30 days',
            '90d'   => '+90 days',
            'never' => null,
        ];
        if (! array_key_exists($duration, $durationMap)) {
            return ['error' => 'Choose a valid link expiration.'];
        }

        $maxDownloads = (int) ($payload['maxDownloads'] ?? 0);
        if ($maxDownloads < 0 || $maxDownloads > 10000) {
            return ['error' => 'Download limit must be between 1 and 10,000, or 0 for unlimited.'];
        }

        return [
            'expiresAt'    => $durationMap[$duration] === null ? null : date('Y-m-d H:i:s', strtotime($durationMap[$duration])),
            'maxDownloads' => $maxDownloads > 0 ? $maxDownloads : null,
        ];
    }

    /**
     * Normalize a browser-provided relative folder path for metadata storage.
     * The value is never used as a Supabase object key, but traversal-like
     * segments and excessive lengths are still rejected.
     *
     * @return string|false|null
     */
    private function cleanFolderPath(string $value)
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', str_replace('\\', '/', $value)) ?? '';
        $value = trim(preg_replace('#/+#', '/', $value) ?? '', "/ \t\n\r\0\x0B");

        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > 1000) {
            return false;
        }

        $segments = [];
        foreach (explode('/', $value) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            if ($segment === '.' || $segment === '..' || mb_strlen($segment) > 150) {
                return false;
            }
            $segments[] = $segment;
        }

        return $segments === [] ? null : implode('/', $segments);
    }

    /** @return array<int, array{label:string,path:string}> */
    private function buildBreadcrumbs(?string $path): array
    {
        if ($path === null || $path === '') {
            return [];
        }

        $crumbs = [];
        $parts  = explode('/', $path);
        $built  = [];
        foreach ($parts as $part) {
            $built[]  = $part;
            $crumbs[] = ['label' => $part, 'path' => implode('/', $built)];
        }

        return $crumbs;
    }

    /**
     * Keep edit/delete actions in the folder and filter view that opened the
     * modal. Only the vault index is accepted, preventing open redirects.
     */
    private function vaultReturnPath(): string
    {
        $value = trim((string) $this->request->getPost('return_to'));
        if ($value === '') {
            return '/files';
        }

        $parts = parse_url($value);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return '/files';
        }

        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        if ($path !== '/files') {
            return '/files';
        }

        $query = trim((string) ($parts['query'] ?? ''));

        return $path . ($query !== '' ? '?' . $query : '');
    }

    private function cleanDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
