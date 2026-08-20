<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Media;
use App\Services\Auth\AuthService;
use App\Services\Media\MediaUploadService;
use Throwable;

final class MediaController extends AdminController
{
    public function __construct(AuthService $auth, private MediaUploadService $uploads)
    {
        parent::__construct($auth);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin.media.index', [
            'media' => Media::all('created_at DESC'),
        ], 'media');
    }

    public function store(Request $request): Response
    {
        $file = $request->file('file');
        if ($file === null) {
            Session::flash('_errors', ['form' => 'Aucun fichier selectionne.']);

            return Response::redirect('/admin/media');
        }

        try {
            $this->uploads->store($file, 'other', $this->auth->user()?->id());
        } catch (Throwable $e) {
            Session::flash('_errors', ['form' => $e->getMessage()]);
        }

        return Response::redirect('/admin/media');
    }

    public function destroy(Request $request, array $params): Response
    {
        $media = Media::find((int) $params['id']);
        if ($media !== null) {
            $path = base_path('public/' . ltrim((string) $media->getAttribute('disk_path'), '/'));
            if (is_file($path)) {
                @unlink($path);
            }
            $media->delete();
        }

        return Response::redirect('/admin/media');
    }
}
