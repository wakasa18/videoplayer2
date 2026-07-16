<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\VideoModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class Video extends BaseController
{
    protected $helpers = ['url', 'form'];

    protected VideoModel $videoModel;

    protected array $allowedExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];

    public function __construct()
    {
        $this->videoModel = new VideoModel();
    }

    /**
     * Main page: upload form + video list + player.
     */
    public function index()
    {
        $data = [
            'videos' => $this->videoModel->getActiveVideos(),
        ];

        return view('video/index', $data);
    }

    /**
     * Step 1 of the upload flow: the browser asks us for a place to upload
     * to. We generate a random, collision-safe filename and hand back a
     * short-lived Supabase signed upload URL. The actual file bytes never
     * touch this backend — the browser PUTs them straight to Supabase next.
     * This is what keeps uploads working on Vercel (4.5MB request body cap)
     * for files far larger than that.
     */
    public function signUpload(): ResponseInterface
    {
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
            $storage = new SupabaseStorage();
            $signed  = $storage->createSignedUploadUrl($storedName);
        } catch (Throwable $e) {
            log_message('error', 'Supabase signed upload URL failed: {msg}', ['msg' => $e->getMessage()]);

            return $this->response->setStatusCode(502)->setJSON(['error' => 'Could not prepare the upload. Please try again.']);
        }

        return $this->response->setJSON([
            'uploadUrl'    => $signed['uploadUrl'],
            'publicUrl'    => $signed['publicUrl'],
            'storedName'   => $storedName,
            'originalName' => $originalName,
            'mimeType'     => $mimeType,
        ]);
    }

    /**
     * Step 2 of the upload flow: after the browser has successfully PUT the
     * file directly to Supabase, it sends us just the small JSON metadata
     * to record in the database. No file bytes pass through here.
     */
    public function store(): ResponseInterface
    {
        $title       = trim((string) $this->request->getJsonVar('title'));
        $description = trim((string) $this->request->getJsonVar('description'));
        $storedName  = (string) $this->request->getJsonVar('storedName');
        $originalName = (string) $this->request->getJsonVar('originalName');
        $mimeType    = (string) $this->request->getJsonVar('mimeType');
        $publicUrl   = (string) $this->request->getJsonVar('publicUrl');
        $fileSize    = (int) $this->request->getJsonVar('fileSize');

        if ($title === '' || $storedName === '' || $publicUrl === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing required fields.']);
        }

        if (mb_strlen($title) > 255) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Title is too long.']);
        }

        $this->videoModel->insert([
            'title'              => $title,
            'description'        => $description,
            'filename'           => $storedName,
            'original_filename'  => $originalName,
            'file_path'          => $publicUrl,
            'mime_type'          => $mimeType !== '' ? $mimeType : 'application/octet-stream',
            'file_size'          => $fileSize,
            'status'             => 'active',
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Soft-delete a video record and remove the underlying file.
     */
    public function destroy(int $id): RedirectResponse
    {
        $video = $this->videoModel->find($id);

        if (! $video) {
            return redirect()->to('/videos')->with('error', 'Video not found.');
        }

        try {
            (new SupabaseStorage())->delete($video['filename']);
        } catch (Throwable $e) {
            // Don't block the DB soft-delete just because storage cleanup failed.
            log_message('error', 'Supabase delete failed: {msg}', ['msg' => $e->getMessage()]);
        }

        $this->videoModel->softDeleteVideo($id);

        return redirect()->to('/videos')->with('success', 'Video deleted.');
    }
}
