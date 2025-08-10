<?php

namespace App\Http\Controllers;

use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImageController extends Controller
{
    protected $imageStorageService;

    public function __construct(ImageStorageService $imageStorageService)
    {
        $this->imageStorageService = $imageStorageService;
    }

    /**
     * Serve image from database
     */
    public function show(Request $request, $path)
    {
        $imageData = $this->imageStorageService->serveImage($path);

        if (!$imageData) {
            abort(404, 'Image not found');
        }

        return response($imageData['data'])
            ->header('Content-Type', $imageData['mime_type'])
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Content-Disposition', 'inline; filename="' . $imageData['filename'] . '"');
    }

    /**
     * Get image info
     */
    public function info(Request $request, $path)
    {
        $imageStorage = $this->imageStorageService->getImageByPath($path);

        if (!$imageStorage) {
            abort(404, 'Image not found');
        }

        return response()->json([
            'filename' => $imageStorage->filename,
            'mime_type' => $imageStorage->mime_type,
            'size' => $imageStorage->size,
            'path' => $imageStorage->path,
            'created_at' => $imageStorage->created_at,
            'url' => $imageStorage->image_url
        ]);
    }
}
