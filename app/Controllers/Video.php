<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\VideoModel;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

class Video extends BaseController
{
    protected $helpers = ['url', 'form'];

    protected VideoModel $videoModel;

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
     * Handle the upload form submission.
     */
    public function store(): RedirectResponse
    {
        $validationRules = [
            'title' => 'required|min_length[1]|max_length[255]',
            'video' => [
                'label' => 'Video file',
                'rules' => [
                    'uploaded[video]',
                    'max_size[video,204800]',      // 200 MB (in KB)
                    'ext_in[video,mp4,webm,ogg,mov,avi,mkv]',
                    'mime_in[video,video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo,video/x-matroska,application/octet-stream]',
                ],
            ],
        ];

        if (! $this->validate($validationRules)) {
            return redirect()->to('/videos')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $file = $this->request->getFile('video');

        if (! $file->isValid() || $file->hasMoved()) {
            return redirect()->to('/videos')
                ->with('error', 'Something went wrong with the upload. Please try again.');
        }

        $originalName = $file->getClientName();
        $newName      = $file->getRandomName(); // collision-safe generated name
        $mimeType     = $file->getClientMimeType();
        $fileSize     = $file->getSize();

        // Upload straight from the temp path Vercel already wrote it to
        // (its /tmp scratch space) into Supabase Storage. We never write
        // to the app's own disk since Vercel's filesystem is read-only
        // at request time.
        try {
            $storage   = new SupabaseStorage();
            $publicUrl = $storage->upload($file->getTempName(), $newName, $mimeType);
        } catch (Throwable $e) {
            log_message('error', 'Supabase upload failed: {msg}', ['msg' => $e->getMessage()]);

            return redirect()->to('/videos')
                ->with('error', 'Upload to storage failed. Please try again.');
        }

        $this->videoModel->insert([
            'title'              => $this->request->getPost('title'),
            'description'        => $this->request->getPost('description'),
            'filename'           => $newName,
            'original_filename'  => $originalName,
            'file_path'          => $publicUrl,
            'mime_type'          => $mimeType,
            'file_size'          => $fileSize,
            'status'             => 'active',
        ]);

        return redirect()->to('/videos')->with('success', 'Video uploaded successfully.');
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
