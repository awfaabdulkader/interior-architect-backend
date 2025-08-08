<?php

namespace App\Services;

use App\Models\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageStorageService
{
    /**
     * Store an image in the database
     */
    public function storeImage(UploadedFile $file, string $path): ImageStorage
    {
        // Read file content and encode to base64
        $imageData = base64_encode(file_get_contents($file));

        // Create image storage record
        $imageStorage = ImageStorage::create([
            'filename' => $file->getClientOriginalName(),
            'image_data' => $imageData,
            'mime_type' => $file->getMimeType(),
            'path' => $path,
            'size' => $file->getSize()
        ]);

        return $imageStorage;
    }

    /**
     * Store multiple images in the database
     */
    public function storeMultipleImages(array $files, string $basePath): array
    {
        $storedImages = [];

        foreach ($files as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $basePath . '/' . $filename;

            $storedImages[] = $this->storeImage($file, $path);
        }

        return $storedImages;
    }

    /**
     * Get image from database and serve it
     */
    public function serveImage(string $path)
    {
        $imageStorage = ImageStorage::where('path', $path)->first();

        if (!$imageStorage) {
            return null;
        }

        return [
            'data' => base64_decode($imageStorage->image_data),
            'mime_type' => $imageStorage->mime_type,
            'filename' => $imageStorage->filename
        ];
    }

    /**
     * Delete image from database
     */
    public function deleteImage(string $path): bool
    {
        $imageStorage = ImageStorage::where('path', $path)->first();

        if ($imageStorage) {
            return $imageStorage->delete();
        }

        return false;
    }

    /**
     * Check if image exists in database
     */
    public function imageExists(string $path): bool
    {
        return ImageStorage::where('path', $path)->exists();
    }

    /**
     * Get image storage by path
     */
    public function getImageByPath(string $path): ?ImageStorage
    {
        return ImageStorage::where('path', $path)->first();
    }
}
