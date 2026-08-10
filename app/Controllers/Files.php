<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\ImportantFileModel;
use Config\Supabase as SupabaseConfig;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * A private file cabinet, deliberately kept locked down further than the
 * Videos section: files live in a *private* Supabase bucket (never a
 * public URL) and the whole section sits behind a shared password stored
 * in session, not just an unguessable route.
 */
class Files extends BaseController
{
    protected $helpers = ['url', 'form'];

    protected ImportantFileModel $fileModel;

    protected array $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip',
        'png', 'jpg', 'jpeg', 'webp', 'gif', 'heic',
    ];

    public function __construct()
    {
        $this->fileModel = new ImportantFileModel();
    }

    private function isUnlocked(): bool
    {
        return (bool) session()->get('files_unlocked');
    }

    private function filesBucket(): string
    {
        return config(SupabaseConfig::class)->filesBucket;
    }

    /**
     * Password prompt. If already unlocked this session, skip straight in.
     */
    public function gate()
    {
        if ($this->isUnlocked()) {
            return redirect()->to('/files');
        }

        return view('files/gate');
    }

    /**
     * Check the submitted password against FILES_ACCESS_PASSWORD and, if
     * correct, mark this browser session unlocked. The comparison uses
     * hash_equals() for timing-safe matching, same reasoning as the cron
     * secret check.
     */
    public function unlock(): RedirectResponse
    {
        $expected = getenv('FILES_ACCESS_PASSWORD');

        if ($expected === false || $expected === '') {
            log_message('error', 'FILES_ACCESS_PASSWORD is not set — Important Files cannot be unlocked.');

            return redirect()->to('/files/gate')->with('error', "This section isn't configured yet.");
        }

        $given = (string) $this->request->getPost('password');

        if (! hash_equals($expected, $given)) {
            return redirect()->to('/files/gate')->with('error', 'Incorrect password.');
        }

        session()->set('files_unlocked', true);

        return redirect()->to('/files');
    }

    /**
     * Lock the section back up for this session.
     */
    public function lock(): RedirectResponse
    {
        session()->remove('files_unlocked');

        return redirect()->to('/files/gate')->with('success', 'Locked.');
    }

    /**
     * Main page: upload form + file list. Redirects to the password gate
     * if this session hasn't unlocked yet.
     */
    public function index()
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        return view('files/index', [
            'files' => $this->fileModel->getActiveFiles(),
        ]);
    }

    /**
     * Step 1 of the upload flow, same shape as Video::signUpload() — the
     * browser gets a short-lived signed URL and uploads straight to
     * Supabase, keeping large files off this backend entirely (Vercel
     * caps request bodies at 4.5MB). The bucket used here is the private
     * files bucket, not the public videos one.
     */
    public function signUpload(): ResponseInterface
    {
        if (! $this->isUnlocked()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Locked.']);
        }

        $originalName = (string) $this->request->getJsonVar('filename');
        $mimeType     = (string) $this->request->getJsonVar('mimetype');

        if ($originalName === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing filename.']);
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, $this->allowedExtensions, true)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Unsupported file type.']);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;

        try {
            $storage = new SupabaseStorage($this->filesBucket());
            $signed  = $storage->createSignedUploadUrl($storedName);
        } catch (Throwable $e) {
            log_message('error', 'Supabase signed upload URL failed: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'Could not prepare the upload. Please try again.']);
        }

        // Deliberately no publicUrl here (unlike Video::signUpload) — the
        // bucket is private, so there isn't a directly usable URL until a
        // signed download link is generated on demand.
        return $this->response->setJSON([
            'uploadUrl'    => $signed['uploadUrl'],
            'storedName'   => $storedName,
            'originalName' => $originalName,
            'mimeType'     => $mimeType,
        ]);
    }

    /**
     * Step 2: after the browser PUTs the file straight to Supabase, it
     * sends us just the small JSON metadata to record. file_path stores
     * the object's key within the private bucket, not a usable URL —
     * that only ever gets generated on demand by download().
     */
    public function store(): ResponseInterface
    {
        if (! $this->isUnlocked()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Locked.']);
        }

        $title        = trim((string) $this->request->getJsonVar('title'));
        $description  = trim((string) $this->request->getJsonVar('description'));
        $category     = trim((string) $this->request->getJsonVar('category'));
        $storedName   = (string) $this->request->getJsonVar('storedName');
        $originalName = (string) $this->request->getJsonVar('originalName');
        $mimeType     = (string) $this->request->getJsonVar('mimeType');
        $fileSize     = (int) $this->request->getJsonVar('fileSize');

        if ($title === '' || $storedName === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing required fields.']);
        }

        if (mb_strlen($title) > 255) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Title is too long.']);
        }

        $this->fileModel->insert([
            'title'             => $title,
            'description'       => $description !== '' ? $description : null,
            'category'          => $category !== '' ? $category : null,
            'stored_filename'   => $storedName,
            'original_filename' => $originalName,
            'file_path'         => $storedName,
            'mime_type'         => $mimeType !== '' ? $mimeType : 'application/octet-stream',
            'file_size'         => $fileSize,
            'status'            => 'active',
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Generate a short-lived signed URL for this file and redirect the
     * browser straight to it — the actual bytes stream from Supabase, not
     * through this backend. The link expires in 2 minutes, so it's not
     * something worth bookmarking or sharing.
     */
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
            $storage   = new SupabaseStorage($this->filesBucket());
            $signedUrl = $storage->createSignedDownloadUrl($file['file_path'], 120);
        } catch (Throwable $e) {
            log_message('error', 'Supabase signed download URL failed: {msg}', ['msg' => $e->getMessage()]);

            return redirect()->to('/files')->with('error', 'Could not generate a download link. Please try again.');
        }

        return redirect()->to($signedUrl);
    }

    /**
     * Soft-delete a file record and remove the underlying object.
     */
    public function destroy(int $id): RedirectResponse
    {
        if (! $this->isUnlocked()) {
            return redirect()->to('/files/gate');
        }

        $file = $this->fileModel->find($id);

        if (! $file) {
            return redirect()->to('/files')->with('error', 'File not found.');
        }

        try {
            (new SupabaseStorage($this->filesBucket()))->delete($file['file_path']);
        } catch (Throwable $e) {
            // Don't block the DB soft-delete just because storage cleanup failed.
            log_message('error', 'Supabase delete failed: {msg}', ['msg' => $e->getMessage()]);
        }

        $this->fileModel->softDeleteFile($id);

        return redirect()->to('/files')->with('success', 'File deleted.');
    }
}
