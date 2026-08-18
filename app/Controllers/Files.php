<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\FileAuditModel;
use App\Models\ImportantFileModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Supabase as SupabaseConfig;
use Throwable;

class Files extends BaseController
{
    protected $helpers = ['url', 'form'];

    protected ImportantFileModel $fileModel;
    protected FileAuditModel $auditModel;

    /** @var array<string, list<string>> */
    protected array $allowedMimeTypes = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/x-ole-storage'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'heic' => ['image/heic', 'image/heif', 'application/octet-stream'],
    ];

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

    private function requireUnlockedJson(): ?ResponseInterface
    {
        if ($this->isUnlocked()) {
            return null;
        }

        return $this->response->setStatusCode(401)->setJSON(['error' => 'This vault is locked. Refresh and unlock it again.']);
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
        session()->remove(['files_unlocked', 'files_pending_uploads']);

        return redirect()->to('/files/gate')->with('success', 'Locked.');
    }

    public function index()
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $filters = [
            'q'        => trim((string) $this->request->getGet('q')),
            'category' => trim((string) $this->request->getGet('category')),
            'folder'   => trim((string) $this->request->getGet('folder')),
            'type'     => strtolower(trim((string) $this->request->getGet('type'))),
            'expiry'   => trim((string) $this->request->getGet('expiry')),
            'favorite' => trim((string) $this->request->getGet('favorite')),
            'sort'     => trim((string) ($this->request->getGet('sort') ?: 'newest')),
        ];

        $queryModel = new ImportantFileModel();
        $files      = $queryModel->getFilteredActive($filters, 10);

        return view('files/index', [
            'files'      => $files,
            'pager'      => $queryModel->pager,
            'filters'    => $filters,
            'categories' => (new ImportantFileModel())->getCategories(),
            'folders'    => (new ImportantFileModel())->getFolders(),
            'extensions' => (new ImportantFileModel())->getExtensions(),
            'summary'    => (new ImportantFileModel())->getVaultSummary(),
            'maxBytes'   => $this->maxUploadBytes(),
            'maxMb'      => (int) ($this->maxUploadBytes() / 1024 / 1024),
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
        $basename  = bin2hex(random_bytes(20)) . '.' . $extension;
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
            'expires_at'        => $clean['expiresAt'],
            'reminder_days'     => $clean['reminderDays'],
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
            if (! $this->mimeAllowed((string) $file['file_extension'], (string) $info['contentType'])) {
                throw new \RuntimeException('The stored content type does not match the selected file type.');
            }

            $prefix = $storage->readObjectPrefix($file['file_path'], 96);
            if (! $this->signatureMatches((string) $file['file_extension'], $prefix)) {
                throw new \RuntimeException('The file contents do not match the filename extension.');
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

    public function preview(int $id): ResponseInterface
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active' || ! ImportantFileModel::isPreviewable($file)) {
            return $this->response
                ->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store, max-age=0')
                ->setBody('Preview is not available for that file.');
        }

        $range = trim($this->request->getHeaderLine('Range'));
        if ($range !== '' && ! preg_match('/^bytes=\d*-\d*$/', $range)) {
            $range = '';
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

        if ($range === '') {
            $this->audit('file_previewed', $id);
        }

        $previewMimeTypes = [
            'pdf'  => 'application/pdf',
            'txt'  => 'text/plain; charset=UTF-8',
            'csv'  => 'text/csv; charset=UTF-8',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
        ];
        $extension = strtolower((string) ($file['file_extension'] ?? pathinfo((string) $file['original_filename'], PATHINFO_EXTENSION)));
        $mimeType  = $previewMimeTypes[$extension]
            ?? trim((string) ($object['contentType'] ?: $file['mime_type']))
            ?: 'application/octet-stream';

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

    public function update(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return redirect()->to('/files')->with('error', 'File not found.');
        }

        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $category    = trim((string) $this->request->getPost('category'));
        $folderRaw   = (string) $this->request->getPost('folder_path');
        $folderPath  = $this->cleanFolderPath($folderRaw);
        $document    = $this->cleanDate((string) $this->request->getPost('document_date'));
        $expires     = $this->cleanDate((string) $this->request->getPost('expires_at'));
        $reminder    = max(0, min(3650, (int) $this->request->getPost('reminder_days')));

        if ($title === '' || mb_strlen($title) > 255 || mb_strlen($description) > 5000 || mb_strlen($category) > 100) {
            return redirect()->to('/files')->with('error', 'Please check the title, description, and category lengths.');
        }
        if ($folderPath === false) {
            return redirect()->to('/files')->with('error', 'The folder path is invalid or too long.');
        }

        $this->fileModel->update($id, [
            'title'                  => $title,
            'description'            => $description !== '' ? $description : null,
            'category'               => $category !== '' ? $category : null,
            'folder_path'            => $folderPath,
            'document_date'          => $document,
            'expires_at'             => $expires,
            'reminder_days'          => $reminder,
            'expiration_reminded_at' => $expires === $file['expires_at'] ? $file['expiration_reminded_at'] : null,
        ]);
        $this->audit('metadata_updated', $id);

        return redirect()->to('/files')->with('success', 'File details updated.');
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

        $file = $this->fileModel->find($id);
        if (! $file || $file['status'] !== 'active') {
            return redirect()->to('/files')->with('error', 'File not found.');
        }

        if (! $this->fileModel->markDeleted($id, 30)) {
            return redirect()->to('/files')->with('error', 'Could not move the file to the Recycle Bin.');
        }

        $this->audit('file_deleted', $id, ['purge_after_days' => 30]);

        return redirect()->to('/files')->with('success', 'File moved to the Recycle Bin for 30 days.');
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

    private function validateUploadRequest(array $payload): array
    {
        $original = basename(str_replace('\\', '/', trim((string) ($payload['filename'] ?? ''))));
        $original = preg_replace('/[\x00-\x1F\x7F]/u', '', $original) ?: '';
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $title      = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $category    = trim((string) ($payload['category'] ?? ''));
        $folderPath  = $this->cleanFolderPath((string) ($payload['folderPath'] ?? ''));
        $mimeType    = strtolower(trim(explode(';', (string) ($payload['mimetype'] ?? ''))[0]));
        $fileSize    = (int) ($payload['filesize'] ?? 0);
        $checksum    = strtolower(trim((string) ($payload['checksum'] ?? '')));
        $reminder    = max(0, min(3650, (int) ($payload['reminderDays'] ?? 30)));

        if ($original === '' || mb_strlen($original) > 255) {
            return ['error' => 'The original filename is missing or too long.'];
        }
        if (! isset($this->allowedMimeTypes[$extension])) {
            return ['error' => 'Unsupported file extension.'];
        }
        if ($fileSize <= 0 || $fileSize > $this->maxUploadBytes()) {
            return ['error' => 'Each file must be larger than 0 bytes and no more than ' . ($this->maxUploadBytes() / 1024 / 1024) . ' MB.'];
        }
        if (! $this->mimeAllowed($extension, $mimeType)) {
            return ['error' => 'The selected file type does not match its extension.'];
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

        return [
            'title'        => $title,
            'description'  => $description !== '' ? $description : null,
            'category'     => $category !== '' ? $category : null,
            'folderPath'   => $folderPath,
            'originalName' => $original,
            'extension'    => $extension,
            'mimeType'     => $mimeType !== '' ? $mimeType : 'application/octet-stream',
            'fileSize'     => $fileSize,
            'checksum'     => $checksum !== '' ? $checksum : null,
            'documentDate' => $this->cleanDate((string) ($payload['documentDate'] ?? '')),
            'expiresAt'    => $this->cleanDate((string) ($payload['expiresAt'] ?? '')),
            'reminderDays' => $reminder,
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

    private function mimeAllowed(string $extension, string $mimeType): bool
    {
        $mimeType = strtolower(trim(explode(';', $mimeType)[0] ?? ''));
        if ($mimeType === '' || $mimeType === 'application/octet-stream') {
            return true;
        }

        return in_array($mimeType, $this->allowedMimeTypes[$extension] ?? [], true);
    }

    private function signatureMatches(string $extension, string $prefix): bool
    {
        $hex = strtoupper(bin2hex($prefix));

        return match ($extension) {
            'pdf'                    => str_starts_with($prefix, '%PDF-'),
            'png'                    => str_starts_with($hex, '89504E470D0A1A0A'),
            'jpg', 'jpeg'            => str_starts_with($hex, 'FFD8FF'),
            'gif'                    => str_starts_with($prefix, 'GIF87a') || str_starts_with($prefix, 'GIF89a'),
            'webp'                   => substr($prefix, 0, 4) === 'RIFF' && substr($prefix, 8, 4) === 'WEBP',
            'zip', 'docx', 'xlsx', 'pptx' => str_starts_with($hex, '504B0304') || str_starts_with($hex, '504B0506') || str_starts_with($hex, '504B0708'),
            'doc', 'xls', 'ppt'      => str_starts_with($hex, 'D0CF11E0A1B11AE1'),
            'heic'                   => substr($prefix, 4, 4) === 'ftyp' && (bool) preg_match('/(heic|heix|hevc|hevx|mif1|msf1)/', substr($prefix, 8, 24)),
            'txt', 'csv'             => ! str_contains($prefix, "\0"),
            default                  => false,
        };
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
